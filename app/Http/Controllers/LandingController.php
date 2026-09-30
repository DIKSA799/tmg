<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing');
    }
}
