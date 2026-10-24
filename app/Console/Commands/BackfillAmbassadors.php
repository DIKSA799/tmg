<?php

namespace App\Console\Commands;

use App\Actions\EnsureAmbassadorAccount;
use App\Models\Ambassador;
use App\Models\VoterRecord;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tmg:backfill-ambassadors {--chunk=500 : Records to process per batch}')]
#[Description('Create ambassador logins for existing voter records (password = TMG number).')]
class BackfillAmbassadors extends Command
{
    public function handle(EnsureAmbassadorAccount $accounts): int
    {
        $chunk = max(50, (int) $this->option('chunk'));

        $total = VoterRecord::query()->whereNotNull('reference')->count();

        if ($total === 0) {
            $this->info('No voter records with a TMG number were found.');

            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        VoterRecord::query()
            ->whereNotNull('reference')
            ->orderBy('id')
            ->chunkById($chunk, function ($records) use ($accounts, &$created, &$skipped, &$failed, $bar): void {
                $existing = Ambassador::query()
                    ->whereIn('voter_record_id', $records->pluck('id'))
                    ->pluck('voter_record_id')
                    ->flip();

                foreach ($records as $record) {
                    if ($existing->has($record->id)) {
                        $skipped++;
                    } else {
                        try {
                            if ($accounts->handle($record) !== null) {
                                $created++;
                            } else {
                                $failed++;
                            }
                        } catch (\Throwable $exception) {
                            $failed++;
                            report($exception);
                        }
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            'Ambassador accounts: %d created, %d already existed, %d failed (of %d records).',
            $created,
            $skipped,
            $failed,
            $total,
        ));

        $this->line('Login identity: phone number or email. Password: the TMG number.');

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
}
