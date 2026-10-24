<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Http\Requests\AmbassadorLoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('ambassador')->check()) {
            return redirect()->route('tmgamb.dashboard');
        }

        return view('tmgamb.login');
    }

    public function store(AmbassadorLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('tmgamb.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('ambassador')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('tmgamb.login')->with('status', 'You have been signed out.');
    }
}
