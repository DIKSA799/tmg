<?php

namespace App\Actions;

use App\Mail\AmbassadorPasswordReset;
use App\Models\Ambassador;
use App\Support\AmbassadorIdentity;
use App\Support\AmbassadorPassword;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAmbassadorPasswordReset
{
    /**
     * Restore an ambassador's password to their TMG number and email it.
     *
     * The email is sent before the password is changed: if delivery fails the
     * existing password keeps working. Unknown identities and accounts without
     * an email silently do nothing so the endpoint cannot be used to enumerate
     * accounts.
     */
    public function handle(string $identity): ?Ambassador
    {
        [$field, $value] = AmbassadorIdentity::parse($identity);

        $ambassador = Ambassador::query()->where($field, $value)->first();

        if ($ambassador === null || $ambassador->email === null) {
            return null;
        }

        $reference = $ambassador->voterRecord()->value('reference');

        if ($reference === null || $reference === '') {
            return null;
        }

        try {
            Mail::to($ambassador->email, $ambassador->name)
                ->send(new AmbassadorPasswordReset($ambassador, $reference));
        } catch (Throwable $exception) {
            Log::error('Unable to send the ambassador password reset email.', [
                'ambassador_id' => $ambassador->id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        $ambassador->forceFill([
            'password' => AmbassadorPassword::normalize($reference),
        ])->save();

        return $ambassador;
    }
}
