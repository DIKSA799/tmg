<?php

namespace App\Http\Controllers\Ambassador;

use App\Actions\CastNominationVote;
use App\Http\Controllers\Controller;
use App\Http\Requests\CastVoteRequest;
use App\Models\Ambassador;
use App\Models\Nomination;
use Illuminate\Http\RedirectResponse;

class VoteController extends Controller
{
    public function store(CastVoteRequest $request, Nomination $nomination, CastNominationVote $action): RedirectResponse
    {
        /** @var Ambassador $voter */
        $voter = $request->user('ambassador');

        if ($nomination->status !== Nomination::STATUS_ACTIVE) {
            return back()->with('error', 'This nomination is no longer accepting votes.');
        }

        if ($voter->senatorial_district_id === null || $voter->senatorial_district_id !== $nomination->senatorial_district_id) {
            return back()->with('error', 'You can only vote for a nominee in your own senatorial zone.');
        }

        if ($nomination->ambassador_id === $voter->id) {
            return back()->with('error', 'You cannot vote for yourself.');
        }

        if ($voter->terms_accepted_at === null) {
            $voter->forceFill(['terms_accepted_at' => now()])->save();
        }

        $counted = $action->handle($nomination, $voter);

        if (! $counted) {
            return back()->with('error', 'You have already voted in this senatorial zone. One ambassador, one vote.');
        }

        return back()->with('status', "Your vote for {$nomination->ambassador->name} has been counted.");
    }
}
