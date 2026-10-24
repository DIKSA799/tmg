<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\Nomination;
use App\Services\Ambassadors\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NomineeController extends Controller
{
    public function __invoke(Request $request, Nomination $nomination, Leaderboard $leaderboard): View
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        abort_unless($nomination->senatorial_district_id === $ambassador->senatorial_district_id, 404);

        $nomination->loadMissing([
            'ambassador.lga:id,name',
            'ambassador.ward:id,name',
            'ambassador.pollingUnit:id,name',
            'senatorialDistrict:id,name',
            'state:id,name',
        ]);

        $myVote = DB::table('nomination_votes')
            ->where('voter_id', $ambassador->id)
            ->where('senatorial_district_id', $ambassador->senatorial_district_id)
            ->first();

        return view('tmgamb.nominee', [
            'ambassador' => $ambassador,
            'nomination' => $nomination,
            'hasVoted' => $myVote !== null,
            'votedForThisNominee' => $myVote !== null && (int) $myVote->nomination_id === $nomination->id,
            'isSelf' => $nomination->ambassador_id === $ambassador->id,
            'shareUrl' => route('tmgamb.nominee', $nomination->public_id),
            'rank' => $leaderboard->rankOf($nomination->ambassador, $nomination),
        ]);
    }
}
