<?php

namespace App\Console\Commands;

use App\Actions\EnsureAmbassadorAccount;
use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\VoterRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Hashing\BcryptHasher;
use Throwable;

#[Signature('tmg:backfill-ambassadors {--chunk=500 : Records to process per batch} {--shard=1/1 : Process one shard of the records, e.g. 1/8} {--rounds= : Bcrypt cost for this run (4-15); defaults to the app config} {--no-progress : Suppress the progress bar (useful for parallel shards)}')]
#[Description('Create ambassador logins for existing voter records (password = TMG number).')]
class BackfillAmbassadors extends Command
{
    public function handle(EnsureAmbassadorAccount $accounts): int
    {
        $startedAt = microtime(true);
        $chunk = max(50, (int) $this->option('chunk'));

        [$index, $count] = $this->parseShard((string) $this->option('shard'));

        if ($index === null) {
            return self::FAILURE;
        }

        if (! $this->applyRounds($this->option('rounds'))) {
            return self::FAILURE;
        }

        $base = VoterRecord::query()
            ->whereNotNull('reference')
            ->when($count > 1, fn ($query) => $query->whereRaw('(id % ?) = ?', [$count, $index % $count]));

        $total = (clone $base)->count();

        if ($total === 0) {
            $this->info(sprintf('No voter records to process for shard %d/%d.', $index, $count));

            return self::SUCCESS;
        }

        $this->line(sprintf('Processing shard %d/%d · %d record(s).', $index, $count, $total));

        // One query for the whole mapping instead of one per record.
        $lgaDistricts = Lga::query()->pluck('senatorial_district_id', 'id');

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->option('no-progress') ? null : $this->output->createProgressBar($total);
        $bar?->start();

        $base->orderBy('id')->chunkById($chunk, function ($records) use ($accounts, $lgaDistricts, &$created, &$skipped, &$failed, $bar): void {
            $existing = Ambassador::query()
                ->whereIn('voter_record_id', $records->pluck('id'))
                ->pluck('voter_record_id')
                ->flip();

            foreach ($records as $record) {
                if ($existing->has($record->id)) {
                    $skipped++;
                } else {
                    try {
                        if ($accounts->handle($record, $lgaDistricts) !== null) {
                            $created++;
                        } else {
                            $failed++;
                        }
                    } catch (Throwable $exception) {
                        $failed++;
                        report($exception);
                    }
                }

                $bar?->advance();
            }
        });

        $bar?->finish();

        if ($bar !== null) {
            $this->newLine(2);
        }

        $this->info(sprintf(
            'Shard %d/%d: %d created, %d already existed, %d failed (of %d records).',
            $index,
            $count,
            $created,
            $skipped,
            $failed,
            $total,
        ));

        $this->line('Login identity: phone number or email. Password: the TMG number.');
        $this->line(sprintf('Completed in %.1f seconds.', microtime(true) - $startedAt));

        $unassigned = Ambassador::query()->whereNull('senatorial_district_id')->count();

        if ($unassigned > 0) {
            $this->newLine();
            $this->warn(sprintf(
                '%d ambassador account(s) have no senatorial district. Run: php artisan db:seed --class=SenatorialDistrictSeeder',
                $unassigned,
            ));
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{0: int|null, 1: int}
     */
    private function parseShard(string $shard): array
    {
        if (preg_match('/^(\d+)\/(\d+)$/', trim($shard), $matches) !== 1) {
            $this->error('Invalid --shard. Use a value like 1/8.');

            return [null, 1];
        }

        $index = (int) $matches[1];
        $count = (int) $matches[2];

        if ($count < 1 || $index < 1 || $index > $count) {
            $this->error('Invalid --shard. The first number must be between 1 and the second number.');

            return [null, 1];
        }

        return [$index, $count];
    }

    private function applyRounds(mixed $rounds): bool
    {
        if ($rounds === null || $rounds === '') {
            return true;
        }

        $rounds = (int) $rounds;

        if ($rounds < 4 || $rounds > 15) {
            $this->error('Invalid --rounds. Use a value between 4 and 15 (10 is a good speed/security balance).');

            return false;
        }

        $driver = app('hash')->driver();

        if (! $driver instanceof BcryptHasher) {
            $this->warn('--rounds only applies to the bcrypt driver; using the configured hasher.');

            return true;
        }

        $driver->setRounds($rounds);

        $this->line("Bcrypt cost for this run: {$rounds}.");

        return true;
    }
}
