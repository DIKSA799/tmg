<?php

namespace App\Actions;

use App\Jobs\SendAmbassadorWelcomeEmail;
use App\Models\VoterRecord;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordVoterSubmission
{
    public function __construct(private readonly GenerateVoterReference $references) {}

    /**
     * Persist a volunteer record.
     *
     * The phone number is the identity: a phone already on file returns that
     * record instead of creating a second one, while any new phone always
     * creates a new record — no matter which operator or device submits it.
     *
     * @param  array<string, mixed>  $data
     * @return array{record: VoterRecord, created: bool}
     */
    public function handle(array $data, ?string $ipAddress, ?string $userAgent): array
    {
        $existing = VoterRecord::query()
            ->where('phone', $data['phone'])
            ->first();

        if ($existing !== null) {
            return ['record' => $existing, 'created' => false];
        }

        $attributes = [
            'reference' => $this->references->handle(
                $data['state_id'],
                $data['lga_id'],
                $data['ward_id'],
                $data['polling_unit_id'],
            ),
            'idempotency_key' => $data['idempotency_key'],
            'full_name' => $data['full_name'],
            'gender' => $data['gender'],
            'age_band' => $data['age_band'],
            'phone' => $data['phone'],
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'volunteer_category' => $data['volunteer_category'],
            'occupation' => $data['occupation'],
            'has_disability' => $data['has_disability'],
            'state_id' => $data['state_id'],
            'lga_id' => $data['lga_id'],
            'ward_id' => $data['ward_id'],
            'polling_unit_id' => $data['polling_unit_id'],
            'registered_voter_status' => $data['registered_voter_status'],
            'pvc_status' => $data['pvc_status'],
            'preferred_language' => $data['preferred_language'],
            'preferred_language_other' => $data['preferred_language_other'] ?? null,
            'preferred_channel' => $data['preferred_channel'],
            'preferred_channel_other' => $data['preferred_channel_other'] ?? null,
            'consent_to_contact' => $data['consent_to_contact'],
            'consent_to_data' => $data['consent_to_data'],
            'pledge_accepted' => $data['pledge_accepted'],
            'agent_id' => $data['agent_id'] ?? null,
            'ip_address' => $ipAddress,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'user_agent' => $userAgent,
            'device' => $data['device'] ?? null,
        ];

        try {
            $record = VoterRecord::create($attributes);
        } catch (UniqueConstraintViolationException) {
            // A concurrent submission with the same phone reached the database
            // first — return the record it created rather than a second copy.
            return [
                'record' => VoterRecord::query()->where('phone', $data['phone'])->firstOrFail(),
                'created' => false,
            ];
        }

        $this->queueWelcomeEmail($record);

        return ['record' => $record, 'created' => true];
    }

    /**
     * Queue the welcome email. A mail or queue problem is logged, never thrown,
     * so a registration can never fail because of it.
     */
    private function queueWelcomeEmail(VoterRecord $record): void
    {
        if ($record->email === null || $record->email === '') {
            return;
        }

        try {
            SendAmbassadorWelcomeEmail::dispatch($record->id);
        } catch (Throwable $exception) {
            Log::error('Unable to queue the ambassador welcome email.', [
                'voter_record_id' => $record->id,
                'reference' => $record->reference,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
