<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAmbassadorPasswordRequest;
use App\Models\Ambassador;
use App\Support\AmbassadorPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function update(UpdateAmbassadorPasswordRequest $request): RedirectResponse
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $current = AmbassadorPassword::normalize($request->validated('current_password'));

        if (! Hash::check($current, $ambassador->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is not correct.',
            ]);
        }

        $ambassador->forceFill([
            'password' => AmbassadorPassword::normalize($request->validated('password')),
        ])->save();

        $request->session()->regenerate();

        return back()->with('status', 'Your password has been updated. Passwords are not case-sensitive.');
    }
}
