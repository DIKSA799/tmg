<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\Nomination;
use App\Models\NominationInvitation;
use App\Services\Ambassadors\Leaderboard;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): View
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $ambassador->loadMissing([
            'state:id,name',
            'lga:id,name',
            'ward:id,name',
            'pollingUnit:id,name',
            'senatorialDistrict:id,name,state_id',
            'nomination',
        ]);

        $districtId = $ambassador->senatorial_district_id;
        $zoneAssigned = $districtId !== null;

        $phone = trim((string) $request->string('phone'));
        $normalizedPhone = $phone !== '' ? PhoneNumber::normalize($phone) : null;
        $phoneError = $phone !== '' && $normalizedPhone === null
            ? 'Enter a valid Nigerian phone number.'
            : null;

        // Privacy: ambassadors are not listed. An exact registered phone
        // number is required, and the lookup is scoped to the same zone.
        $foundAmbassador = $zoneAssigned && $normalizedPhone !== null
            ? Ambassador::query()
                ->inDistrict($districtId)
                ->where('phone', $normalizedPhone)
                ->with([
                    'lga:id,name',
                    'ward:id,name',
                    'pollingUnit:id,name',
                    'nomination:id,public_id,ambassador_id,votes_count,status',
                ])
                ->first()
            : null;

        // Only leaderboard-related statistics are kept on the dashboard.
        $stats = $zoneAssigned
            ? Cache::remember('tmg.zone.stats.'.$districtId, 60, fn (): array => [
                'nominations' => Nomination::query()->active()->forDistrict($districtId)->count(),
                'votes' => (int) Nomination::query()->where('senatorial_district_id', $districtId)->sum('votes_count'),
            ])
            : ['nominations' => 0, 'votes' => 0];

        $myVote = $zoneAssigned
            ? DB::table('nomination_votes')
                ->where('voter_id', $ambassador->id)
                ->where('senatorial_district_id', $districtId)
                ->first()
            : null;

        $myVotedNomination = $myVote === null
            ? null
            : Nomination::query()->with('ambassador:id,name')->find($myVote->nomination_id);

        $invitations = NominationInvitation::query()
            ->pending()
            ->where('nominee_id', $ambassador->id)
            ->with([
                'inviter:id,name,photo_path,lga_id',
                'inviter.lga:id,name',
            ])
            ->latest()
            ->get();

        return view('tmgamb.dashboard', [
            'ambassador' => $ambassador,
            'stats' => $stats,
            'phone' => $phone,
            'phoneError' => $phoneError,
            'foundAmbassador' => $foundAmbassador,
            'myVotedNomination' => $myVotedNomination,
            'invitations' => $invitations,
            'zoneTop' => $leaderboard->zone($districtId)->take(5),
            'myRank' => $leaderboard->rankOf($ambassador, $ambassador->nomination),
            'zoneAssigned' => $zoneAssigned,
        ]);
    }
}
