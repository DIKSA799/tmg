<?php

namespace App\Actions;

use App\Models\Ambassador;
use App\Models\Nomination;
use App\Models\NominationInvitation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RespondToNominationInvitation
{
    /**
     * Accept an invitation: the nominee joins the leaderboard and the inviter's
     * vote is recorded (if the inviter has not already voted in this zone).
     *
     * @return array{nomination: Nomination|null, reason: string|null}
     */
    public function accept(NominationInvitation $invitation): array
    {
        if ($invitation->status !== NominationInvitation::STATUS_PENDING) {
            return ['nomination' => null, 'reason' => 'This invitation has already been answered.'];
        }

        return DB::transaction(function () use ($invitation): array {
            $invitation = NominationInvitation::query()->whereKey($invitation->id)->lockForUpdate()->firstOrFail();

            if ($invitation->status !== NominationInvitation::STATUS_PENDING) {
                return ['nomination' => null, 'reason' => 'This invitation has already been answered.'];
            }

            $nominee = $invitation->nominee;
            $inviter = $invitation->inviter;

            $existing = Nomination::query()->where('ambassador_id', $nominee->id)->first();

            if ($existing !== null) {
                $invitation->update([
                    'status' => NominationInvitation::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                return ['nomination' => $existing, 'reason' => 'You are already on the leaderboard.'];
            }

            try {
                $nomination = Nomination::query()->create([
                    'ambassador_id' => $nominee->id,
                    'state_id' => $nominee->state_id,
                    'senatorial_district_id' => $nominee->senatorial_district_id,
                    'nominated_by' => $inviter->id,
                    'status' => Nomination::STATUS_ACTIVE,
                    'votes_count' => 0,
                    'accepted_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                // A concurrent acceptance already put the nominee on the ballot.
                $existing = Nomination::query()->where('ambassador_id', $nominee->id)->first();

                if ($existing === null) {
                    return ['nomination' => null, 'reason' => 'The invitation could not be accepted. Please try again.'];
                }

                $invitation->update([
                    'status' => NominationInvitation::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                return ['nomination' => $existing, 'reason' => 'You are already on the leaderboard.'];
            }

            if ($inviter->senatorial_district_id === $nomination->senatorial_district_id) {
                $inserted = DB::table('nomination_votes')->insertOrIgnore([
                    'nomination_id' => $nomination->id,
                    'voter_id' => $inviter->id,
                    'senatorial_district_id' => $nomination->senatorial_district_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted > 0) {
                    Nomination::query()->whereKey($nomination->id)->increment('votes_count');
                }
            }

            $invitation->update([
                'status' => NominationInvitation::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            Cache::forget('tmg.leaderboard.zone.'.$nomination->senatorial_district_id);
            Cache::forget('tmg.leaderboard.state.'.$nomination->state_id);
            Cache::forget('tmg.zone.stats.'.$nomination->senatorial_district_id);

            return ['nomination' => $nomination->refresh(), 'reason' => null];
        });
    }

    public function reject(NominationInvitation $invitation): void
    {
        $invitation->update([
            'status' => NominationInvitation::STATUS_REJECTED,
            'responded_at' => now(),
        ]);
    }

    /**
     * Invite another ambassador in the same district to be nominated.
     *
     * @return array{invitation: NominationInvitation|null, reason: string|null}
     */
    public function invite(Ambassador $inviter, Ambassador $nominee, ?string $message = null): array
    {
        if ($inviter->id === $nominee->id) {
            return ['invitation' => null, 'reason' => 'You cannot invite yourself — use nominate myself.'];
        }

        if ($inviter->senatorial_district_id === null || $inviter->senatorial_district_id !== $nominee->senatorial_district_id) {
            return ['invitation' => null, 'reason' => 'You can only invite ambassadors in your own senatorial zone.'];
        }

        if (Nomination::query()->where('ambassador_id', $nominee->id)->exists()) {
            return ['invitation' => null, 'reason' => 'That ambassador is already on the leaderboard.'];
        }

        if (DB::table('nomination_votes')->where('voter_id', $inviter->id)->where('senatorial_district_id', $inviter->senatorial_district_id)->exists()) {
            return ['invitation' => null, 'reason' => 'You have already used your vote in this zone.'];
        }

        $existing = NominationInvitation::query()
            ->where('nominee_id', $nominee->id)
            ->where('inviter_id', $inviter->id)
            ->first();

        if ($existing !== null) {
            if ($existing->status === NominationInvitation::STATUS_PENDING) {
                return ['invitation' => $existing, 'reason' => 'You have already invited this ambassador.'];
            }

            if ($existing->status === NominationInvitation::STATUS_ACCEPTED) {
                return ['invitation' => null, 'reason' => 'This ambassador already accepted your invitation.'];
            }

            // A declined invitation can be sent again: reuse the row so the
            // (nominee, inviter) unique index is respected.
            $existing->update([
                'status' => NominationInvitation::STATUS_PENDING,
                'message' => $message,
                'responded_at' => null,
            ]);

            return ['invitation' => $existing, 'reason' => null];
        }

        $invitation = NominationInvitation::query()->create([
            'nominee_id' => $nominee->id,
            'inviter_id' => $inviter->id,
            'senatorial_district_id' => $inviter->senatorial_district_id,
            'message' => $message,
            'status' => NominationInvitation::STATUS_PENDING,
        ]);

        return ['invitation' => $invitation, 'reason' => null];
    }
}
