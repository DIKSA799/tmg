<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcknowledgementController extends Controller
{
    public function acceptTerms(Request $request): RedirectResponse
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $ambassador->forceFill(['terms_accepted_at' => now()])->save();

        return back()->with('status', 'Terms accepted.');
    }

    public function seenNotice(Request $request): RedirectResponse
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $ambassador->forceFill(['notice_seen_at' => now()])->save();

        return back();
    }
}
