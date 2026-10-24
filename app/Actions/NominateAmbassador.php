<?php

namespace App\Actions;

use App\Models\Ambassador;
use App\Models\Nomination;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NominateAmbassador
{
    /**
     * Self-nomination. The nominee's own vote is recorded so they enter the
     * leaderboard with one vote; the unique (voter, district) index makes this
     * safe even if the request is submitted twice.
     *
     * @return array{nomination: Nomination|null, reason: string|null}
     */
    public function handle(Ambassador $ambassador, ?string $statement = null): array
    {
        if ($ambassador->senatorial_district_id === null) {
            return ['nomination' => null, 'reason' => 'You are not assigned to a senatorial district yet.'];
        }

        return DB::transaction(function () use ($ambassador, $statement): array {
            $existing = Nomination::query()
                ->where('ambassador_id', $ambassador->id)
                ->first();

            if ($existing !== null) {
                return ['nomination' => $existing, 'reason' => 'You have already been nominated.'];
            }

            $alreadyVoted = DB::table('nomination_votes')
                ->where('voter_id', $ambassador->id)
                ->where('senatorial_district_id', $ambassador->senatorial_district_id)
                ->exists();

            if ($alreadyVoted) {
                return ['nomination' => null, 'reason' => 'You have already cast your vote in this zone, so you can no longer self-nominate.'];
            }

            try {
                $nomination = Nomination::query()->create([
                    'ambassador_id' => $ambassador->id,
                    'state_id' => $ambassador->state_id,
                    'senatorial_district_id' => $ambassador->senatorial_district_id,
                    'nominated_by' => $ambassador->id,
                    'statement' => $statement,
                    'status' => Nomination::STATUS_ACTIVE,
                    'votes_count' => 0,
                    'accepted_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent request nominated this ambassador first.
                $existing = Nomination::query()->where('ambassador_id', $ambassador->id)->first();

                return $existing !== null
                    ? ['nomination' => $existing, 'reason' => 'You have already been nominated.']
                    : ['nomination' => null, 'reason' => 'You could not be nominated. Please try again.'];
            }

            $this->recordVote($nomination, $ambassador);

            Cache::forget('tmg.leaderboard.zone.'.$nomination->senatorial_district_id);
            Cache::forget('tmg.leaderboard.state.'.$nomination->state_id);
            Cache::forget('tmg.zone.stats.'.$nomination->senatorial_district_id);

            return ['nomination' => $nomination->refresh(), 'reason' => null];
        });
    }

    private function recordVote(Nomination $nomination, Ambassador $voter): void
    {
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
    }
}
