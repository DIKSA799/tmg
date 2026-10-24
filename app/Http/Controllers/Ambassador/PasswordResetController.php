<?php

namespace App\Http\Controllers\Ambassador;

use App\Actions\SendAmbassadorPasswordReset;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasswordResetController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('ambassador')->check()) {
            return redirect()->route('tmgamb.dashboard');
        }

        return view('tmgamb.forgot-password');
    }

    public function store(Request $request, SendAmbassadorPasswordReset $reset): RedirectResponse
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:160'],
        ]);

        $reset->handle($validated['identity']);

        // Always the same answer, whether or not the account exists or has an
        // email on file, so this cannot be used to enumerate ambassadors.
        return back()->with(
            'status',
            'If an account with that phone number or email has an email address on file, we have sent the TMG number to it.',
        );
    }
}
