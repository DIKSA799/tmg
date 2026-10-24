<?php

namespace Tests\Feature\Ambassador;

use App\Actions\CastNominationVote;
use App\Models\Nomination;
use App\Models\NominationVote;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VotingTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_vote_is_counted_and_recorded(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);
        $nomination = $this->nominationFor($nominee, $zone);

        $this->actingAs($voter, 'ambassador')
            ->post(route('tmgamb.vote', $nomination), ['accept_terms' => 1])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, $nomination->fresh()->votes_count);
        $this->assertDatabaseHas('nomination_votes', [
            'nomination_id' => $nomination->id,
            'voter_id' => $voter->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);
        $this->assertNotNull($voter->fresh()->terms_accepted_at);
    }

    public function test_the_same_vote_cannot_be_counted_twice(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);
        $nomination = $this->nominationFor($nominee, $zone);

        $this->actingAs($voter, 'ambassador')
            ->post(route('tmgamb.vote', $nomination), ['accept_terms' => 1])
            ->assertSessionHas('status');

        $this->actingAs($voter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.vote', $nomination), ['accept_terms' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, $nomination->fresh()->votes_count);
        $this->assertSame(1, NominationVote::query()->count());
    }

    public function test_an_ambassador_can_only_vote_once_in_a_zone(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $first = $this->nominationFor($this->ambassadorInZone($zone), $zone);
        $second = $this->nominationFor($this->ambassadorInZone($zone), $zone);

        $this->actingAs($voter, 'ambassador')
            ->post(route('tmgamb.vote', $first), ['accept_terms' => 1])
            ->assertSessionHas('status');

        $this->actingAs($voter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.vote', $second), ['accept_terms' => 1])
            ->assertSessionHas('error');

        $this->assertSame(1, $first->fresh()->votes_count);
        $this->assertSame(0, $second->fresh()->votes_count);
        $this->assertSame(1, NominationVote::query()->count());
    }

    public function test_votes_cannot_cross_senatorial_zones(): void
    {
        $state = State::factory()->create();
        $zoneA = $this->makeZone($state);
        $zoneB = $this->makeZone($state);

        $voter = $this->ambassadorInZone($zoneA);
        $nomination = $this->nominationFor($this->ambassadorInZone($zoneB), $zoneB);

        $this->actingAs($voter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.vote', $nomination), ['accept_terms' => 1])
            ->assertSessionHas('error');

        $this->assertSame(0, $nomination->fresh()->votes_count);
        $this->assertDatabaseCount('nomination_votes', 0);
    }

    public function test_an_ambassador_cannot_vote_for_themselves(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $ownNomination = $this->nominationFor($voter, $zone);

        $this->actingAs($voter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.vote', $ownNomination), ['accept_terms' => 1])
            ->assertSessionHas('error');

        $this->assertSame(0, $ownNomination->fresh()->votes_count);
    }

    public function test_voting_requires_accepting_the_terms(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $nomination = $this->nominationFor($this->ambassadorInZone($zone), $zone);

        $this->actingAs($voter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.vote', $nomination))
            ->assertSessionHasErrors('accept_terms');

        $this->assertSame(0, $nomination->fresh()->votes_count);
        $this->assertDatabaseCount('nomination_votes', 0);
    }

    public function test_the_vote_action_is_idempotent_under_concurrency(): void
    {
        $zone = $this->makeZone();
        $voter = $this->ambassadorInZone($zone);
        $nomination = $this->nominationFor($this->ambassadorInZone($zone), $zone);

        $action = app(CastNominationVote::class);

        $this->assertTrue($action->handle($nomination, $voter));
        $this->assertFalse($action->handle($nomination, $voter));

        $this->assertSame(1, $nomination->fresh()->votes_count);
        $this->assertSame(1, NominationVote::query()->count());
    }

    private function nominationFor($ambassador, array $zone): Nomination
    {
        return Nomination::factory()->create([
            'ambassador_id' => $ambassador->id,
            'state_id' => $zone['state']->id,
            'senatorial_district_id' => $zone['district']->id,
            'nominated_by' => $ambassador->id,
            'votes_count' => 0,
        ]);
    }
}
