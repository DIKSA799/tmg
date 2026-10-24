<?php

namespace App\Http\Requests;

use App\Actions\EnsureAmbassadorAccount;
use App\Models\Ambassador;
use App\Models\VoterRecord;
use App\Support\AmbassadorIdentity;
use App\Support\AmbassadorPassword;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AmbassadorLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string', 'max:160'],
            'password' => ['required', 'string', 'max:64'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identity.required' => 'Enter your phone number or email address.',
            'password.required' => 'Enter your password.',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        [$field, $identity] = AmbassadorIdentity::parse($this->string('identity')->toString());

        // Passwords are case-insensitive: the same normalisation is applied
        // when an account is created, changed, or reset.
        $password = AmbassadorPassword::normalize($this->string('password')->toString());
        $remember = $this->boolean('remember');

        $authenticated = Auth::guard('ambassador')->attempt([
            $field => $identity,
            'password' => $password,
        ], $remember);

        // A record captured before the bulk backfill has no account yet. Let
        // it sign in with its TMG number and provision the account on the fly,
        // so nobody has to wait for a 50k-record backfill.
        if (! $authenticated) {
            $authenticated = $this->attemptFirstLogin($field, $identity, $password, $remember);
        }

        if (! $authenticated) {
            RateLimiter::hit($this->throttleKey(), (int) config('ambassadors.login.decay_seconds', 60));

            throw ValidationException::withMessages([
                'identity' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $ambassador = Auth::guard('ambassador')->user();

        if ($ambassador !== null) {
            $ambassador->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }

    /**
     * Sign in a voter record that has no ambassador account yet.
     *
     * The TMG number is accepted only when no account exists for the record.
     * Once an account exists its own password is authoritative, so a changed
     * password can never be bypassed by re-using the TMG number.
     */
    private function attemptFirstLogin(string $field, string $identity, string $password, bool $remember): bool
    {
        $record = $field === 'email'
            ? VoterRecord::query()->whereRaw('LOWER(email) = ?', [$identity])->first()
            : VoterRecord::query()->where('phone', $identity)->first();

        if ($record === null || $record->reference === null || $record->reference === '') {
            return false;
        }

        if (Ambassador::query()->where('voter_record_id', $record->id)->exists()) {
            return false;
        }

        if (! hash_equals(AmbassadorPassword::normalize($record->reference), $password)) {
            return false;
        }

        $ambassador = app(EnsureAmbassadorAccount::class)->handle($record);

        if ($ambassador === null) {
            return false;
        }

        Auth::guard('ambassador')->login($ambassador, $remember);

        return true;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $attempts = (int) config('ambassadors.login.max_attempts', 5);

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $attempts)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'identity' => "Too many sign-in attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('identity')->toString()).'|'.$this->ip());
    }
}
