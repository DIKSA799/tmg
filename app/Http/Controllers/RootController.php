<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RootController extends Controller
{
    /**
     * The root is the operator gate: guests get the sign-in form, and
     * authenticated users get the landing page.
     */
    public function __invoke(Request $request, LandingController $landing): View
    {
        return $request->user() === null
            ? view('auth.login')
            : $landing();
    }
}
