<?php

namespace App\Actions;

use App\Models\Ambassador;
use App\Models\Nomination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CastNominationVote
{
    /**
     * Cast one vote in the voter's senatorial district.
     *
     * The unique (voter_id, senatorial_district_id) index is the source of
     * truth: insertOrIgnore returns 0 when the vote already exists (including
     * under a concurrent duplicate request), so the counter can never be
     * incremented twice. The increment is a single atomic SQL statement.
     *
     * @return bool True when the vote was counted, false when it already existed.
     */
    public function handle(Nomination $nomination, Ambassador $voter): bool
    {
        $inserted = DB::transaction(function () use ($nomination, $voter): int {
            $inserted = DB::table('nomination_votes')->insertOrIgnore([
                'nomination_id' => $nomination->id,
                'voter_id' => $voter->id,
                'senatorial_district_id' => $nomination->senatorial_district_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted > 0) {
                Nomination::query()->whereKey($nomination->id)->increment('votes_count');
            }

            return $inserted;
        });

        if ($inserted > 0) {
            Cache::forget('tmg.leaderboard.zone.'.$nomination->senatorial_district_id);
            Cache::forget('tmg.leaderboard.state.'.$nomination->state_id);
            Cache::forget('tmg.zone.stats.'.$nomination->senatorial_district_id);
        }

        return $inserted > 0;
    }
}
