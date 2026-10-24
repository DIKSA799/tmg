<?php

namespace App\Http\Requests;

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
            'password.required' => 'Enter your TMG number as the password.',
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

        $authenticated = Auth::guard('ambassador')->attempt([
            $field => $identity,
            'password' => $password,
        ], $this->boolean('remember'));

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
