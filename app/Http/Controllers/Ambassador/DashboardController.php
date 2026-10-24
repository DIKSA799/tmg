<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\Nomination;
use App\Models\NominationInvitation;
use App\Services\Ambassadors\Leaderboard;
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
        $search = trim((string) $request->string('q'));

        $zoneAmbassadors = $zoneAssigned
            ? Ambassador::query()
                ->inDistrict($districtId)
                ->search($search)
                ->with([
                    'lga:id,name',
                    'ward:id,name',
                    'pollingUnit:id,name',
                    'nomination:id,public_id,ambassador_id,votes_count,status',
                ])
                ->orderBy('name')
                ->paginate(18)
                ->withQueryString()
            : Ambassador::query()->whereRaw('1 = 0')->paginate(18);

        $stats = $zoneAssigned
            ? Cache::remember('tmg.zone.stats.'.$districtId, 60, fn (): array => [
                'ambassadors' => Ambassador::query()->where('senatorial_district_id', $districtId)->count(),
                'nominations' => Nomination::query()->active()->forDistrict($districtId)->count(),
                'votes' => (int) Nomination::query()->where('senatorial_district_id', $districtId)->sum('votes_count'),
            ])
            : ['ambassadors' => 0, 'nominations' => 0, 'votes' => 0];

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
            'zoneAmbassadors' => $zoneAmbassadors,
            'stats' => $stats,
            'search' => $search,
            'myVotedNomination' => $myVotedNomination,
            'invitations' => $invitations,
            'zoneTop' => $leaderboard->zone($districtId)->take(5),
            'myRank' => $leaderboard->rankOf($ambassador, $ambassador->nomination),
            'zoneAssigned' => $zoneAssigned,
        ]);
    }
}
