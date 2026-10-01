<?php

namespace App\Jobs;

use App\Mail\AmbassadorWelcome;
use App\Models\VoterRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends the welcome email for a freshly registered ambassador.
 *
 * It always runs on the queue (never inline), so a slow or failing mail
 * provider can never hold up — or fail — a registration. Anything that still
 * fails after every retry lands in the `failed_jobs` table for a later
 * `php artisan queue:retry all`, and is logged here.
 */
class SendAmbassadorWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 1800];

    public int $timeout = 30;

    public function __construct(public int $voterRecordId) {}

    public function handle(): void
    {
        $record = VoterRecord::query()
            ->with(['state:id,name', 'lga:id,name', 'ward:id,name', 'pollingUnit:id,name'])
            ->find($this->voterRecordId);

        if ($record === null || $record->email === null || $record->email === '') {
            return;
        }

        Mail::to($record->email, $record->full_name)->send(new AmbassadorWelcome($record));
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Ambassador welcome email failed after all retries.', [
            'voter_record_id' => $this->voterRecordId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
