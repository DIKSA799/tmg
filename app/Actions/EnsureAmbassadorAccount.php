<?php

namespace App\Actions;

use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\VoterRecord;
use App\Support\AmbassadorPassword;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;

class EnsureAmbassadorAccount
{
    /**
     * Provision the ambassador login for a voter record.
     *
     * The TMG number (record reference) is the password, uppercased before
     * hashing so sign-in does not depend on the case the ambassador types.
     * Idempotent: an existing account is returned untouched.
     */
    public function handle(VoterRecord $record): ?Ambassador
    {
        if ($record->reference === null || $record->reference === '') {
            return null;
        }

        $existing = Ambassador::query()
            ->where('voter_record_id', $record->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $email = $record->email !== null ? Str::lower(trim((string) $record->email)) : null;
        $email = $email === '' ? null : $email;

        if ($email !== null && Ambassador::query()->where('email', $email)->exists()) {
            // The email is already someone else's identity. Keep the phone as
            // the login identity rather than breaking account creation.
            $email = null;
        }

        $districtId = Lga::query()
            ->whereKey($record->lga_id)
            ->value('senatorial_district_id');

        try {
            return Ambassador::query()->create([
                'voter_record_id' => $record->id,
                'name' => $record->full_name,
                'phone' => $record->phone,
                'email' => $email,
                'password' => AmbassadorPassword::normalize($record->reference),
                'state_id' => $record->state_id,
                'lga_id' => $record->lga_id,
                'ward_id' => $record->ward_id,
                'polling_unit_id' => $record->polling_unit_id,
                'senatorial_district_id' => $districtId,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request provisioned the same record first.
            return Ambassador::query()
                ->where('voter_record_id', $record->id)
                ->first();
        }
    }
}
