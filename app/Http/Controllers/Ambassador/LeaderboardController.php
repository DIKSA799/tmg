<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Services\Ambassadors\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __invoke(Request $request, Leaderboard $leaderboard): View
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $ambassador->loadMissing([
            'state:id,name',
            'senatorialDistrict:id,name',
            'nomination',
        ]);

        return view('tmgamb.leaderboard', [
            'ambassador' => $ambassador,
            'zoneLeaders' => $leaderboard->zone($ambassador->senatorial_district_id),
            'stateLeaders' => $leaderboard->state($ambassador->state_id),
            'myRank' => $leaderboard->rankOf($ambassador, $ambassador->nomination),
        ]);
    }
}
