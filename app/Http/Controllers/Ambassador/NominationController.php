<?php

namespace App\Http\Controllers\Ambassador;

use App\Actions\NominateAmbassador;
use App\Actions\RespondToNominationInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNominationRequest;
use App\Models\Ambassador;
use App\Models\NominationInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NominationController extends Controller
{
    public function store(StoreNominationRequest $request, NominateAmbassador $action): RedirectResponse
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        if ($ambassador->terms_accepted_at === null) {
            $ambassador->forceFill(['terms_accepted_at' => now()])->save();
        }

        $result = $action->handle($ambassador, $request->validated('statement'));

        if ($result['nomination'] === null) {
            return back()->with('error', $result['reason'] ?? 'You could not be nominated.');
        }

        return back()->with(
            'status',
            $result['reason'] ?? 'You are on the leaderboard with 1 vote. Share your link to get more votes.',
        );
    }

    public function invite(Request $request, Ambassador $ambassador, RespondToNominationInvitation $action): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:280'],
        ]);

        /** @var Ambassador $inviter */
        $inviter = $request->user('ambassador');

        $result = $action->invite($inviter, $ambassador, $validated['message'] ?? null);

        if ($result['invitation'] === null) {
            return back()->with('error', $result['reason'] ?? 'The invitation could not be sent.');
        }

        return back()->with('status', $result['reason'] ?? "Invitation sent to {$ambassador->name}.");
    }

    public function accept(NominationInvitation $invitation, RespondToNominationInvitation $action): RedirectResponse
    {
        $this->authorizeNominee($invitation);

        $result = $action->accept($invitation);

        if ($result['nomination'] === null) {
            return back()->with('error', $result['reason'] ?? 'The invitation could not be accepted.');
        }

        return back()->with('status', $result['reason'] ?? 'Invitation accepted. You are now on the leaderboard.');
    }

    public function reject(NominationInvitation $invitation, RespondToNominationInvitation $action): RedirectResponse
    {
        $this->authorizeNominee($invitation);

        if ($invitation->status !== NominationInvitation::STATUS_PENDING) {
            return back()->with('error', 'This invitation has already been answered.');
        }

        $action->reject($invitation);

        return back()->with('status', 'Invitation declined.');
    }

    private function authorizeNominee(NominationInvitation $invitation): void
    {
        abort_unless($invitation->nominee_id === Auth::guard('ambassador')->id(), 403);
    }
}
