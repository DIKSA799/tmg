<?php

namespace Tests\Feature\Ambassador;

use App\Actions\NominateAmbassador;
use App\Models\Nomination;
use App\Models\NominationInvitation;
use App\Models\NominationVote;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class NominationFlowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_ambassador_can_nominate_themselves_with_one_vote(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->post(route('tmgamb.nominate'), [
                'accept_terms' => 1,
                'statement' => 'I will lead our zone well.',
            ])
            ->assertRedirect();

        $nomination = Nomination::query()->sole();

        $this->assertSame($me->id, $nomination->ambassador_id);
        $this->assertSame($zone['district']->id, $nomination->senatorial_district_id);
        $this->assertSame(1, $nomination->votes_count);
        $this->assertSame('I will lead our zone well.', $nomination->statement);
        $this->assertNotNull($me->fresh()->terms_accepted_at);
        $this->assertDatabaseHas('nomination_votes', [
            'nomination_id' => $nomination->id,
            'voter_id' => $me->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);
    }

    public function test_self_nomination_requires_terms(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.nominate'), [])
            ->assertSessionHasErrors('accept_terms');

        $this->assertDatabaseCount('nominations', 0);
    }

    public function test_self_nomination_twice_returns_the_existing_nomination(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $action = app(NominateAmbassador::class);

        $first = $action->handle($me, 'First attempt');
        $second = $action->handle($me, 'Second attempt');

        $this->assertNotNull($first['nomination']);
        $this->assertSame($first['nomination']->id, $second['nomination']?->id);
        $this->assertSame('You have already been nominated.', $second['reason']);
        $this->assertDatabaseCount('nominations', 1);
        $this->assertDatabaseCount('nomination_votes', 1);
    }

    public function test_a_declined_invitation_can_be_sent_again(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);

        $invitation = NominationInvitation::factory()->create([
            'nominee_id' => $nominee->id,
            'inviter_id' => $inviter->id,
            'senatorial_district_id' => $zone['district']->id,
            'status' => NominationInvitation::STATUS_REJECTED,
            'responded_at' => now()->subDay(),
        ]);

        $this->actingAs($inviter, 'ambassador')
            ->post(route('tmgamb.invite', $nominee), ['message' => 'Please reconsider.'])
            ->assertRedirect();

        $invitation->refresh();

        $this->assertDatabaseCount('nomination_invitations', 1);
        $this->assertSame(NominationInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame('Please reconsider.', $invitation->message);
        $this->assertNull($invitation->responded_at);
    }

    public function test_an_ambassador_who_already_voted_cannot_self_nominate(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $other = $this->ambassadorInZone($zone);

        $nomination = Nomination::factory()->create([
            'ambassador_id' => $other->id,
            'state_id' => $zone['state']->id,
            'senatorial_district_id' => $zone['district']->id,
            'nominated_by' => $other->id,
        ]);

        $this->actingAs($me, 'ambassador')
            ->post(route('tmgamb.vote', $nomination), ['accept_terms' => 1])
            ->assertRedirect();

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.nominate'), ['accept_terms' => 1])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('nominations', ['ambassador_id' => $me->id]);
    }

    public function test_an_ambassador_can_invite_a_zone_member_to_be_nominated(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone, ['name' => 'Invited Person']);

        $this->actingAs($inviter, 'ambassador')
            ->post(route('tmgamb.invite', $nominee), ['message' => 'Please stand.'])
            ->assertRedirect();

        $invitation = NominationInvitation::query()->sole();

        $this->assertSame($inviter->id, $invitation->inviter_id);
        $this->assertSame($nominee->id, $invitation->nominee_id);
        $this->assertSame(NominationInvitation::STATUS_PENDING, $invitation->status);
        $this->assertSame('Please stand.', $invitation->message);
    }

    public function test_an_invitation_cannot_cross_senatorial_zones(): void
    {
        $state = State::factory()->create();
        $zoneA = $this->makeZone($state);
        $zoneB = $this->makeZone($state);

        $inviter = $this->ambassadorInZone($zoneA);
        $nominee = $this->ambassadorInZone($zoneB);

        $this->actingAs($inviter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.invite', $nominee))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('nomination_invitations', 0);
    }

    public function test_accepting_an_invitation_adds_the_nominee_and_the_inviters_vote(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);

        $invitation = NominationInvitation::factory()->create([
            'nominee_id' => $nominee->id,
            'inviter_id' => $inviter->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);

        $this->actingAs($nominee, 'ambassador')
            ->post(route('tmgamb.invitations.accept', $invitation))
            ->assertRedirect();

        $invitation->refresh();
        $nomination = Nomination::query()->where('ambassador_id', $nominee->id)->sole();

        $this->assertSame(NominationInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertSame($inviter->id, $nomination->nominated_by);
        $this->assertSame(1, $nomination->votes_count);
        $this->assertDatabaseHas('nomination_votes', [
            'nomination_id' => $nomination->id,
            'voter_id' => $inviter->id,
        ]);
    }

    public function test_rejecting_an_invitation_does_not_create_a_nomination(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);

        $invitation = NominationInvitation::factory()->create([
            'nominee_id' => $nominee->id,
            'inviter_id' => $inviter->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);

        $this->actingAs($nominee, 'ambassador')
            ->post(route('tmgamb.invitations.reject', $invitation))
            ->assertRedirect();

        $this->assertSame(NominationInvitation::STATUS_REJECTED, $invitation->fresh()->status);
        $this->assertDatabaseCount('nominations', 0);
        $this->assertDatabaseCount('nomination_votes', 0);
    }

    public function test_only_the_invitee_can_answer_an_invitation(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $nominee = $this->ambassadorInZone($zone);
        $stranger = $this->ambassadorInZone($zone);

        $invitation = NominationInvitation::factory()->create([
            'nominee_id' => $nominee->id,
            'inviter_id' => $inviter->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);

        $this->actingAs($stranger, 'ambassador')
            ->post(route('tmgamb.invitations.accept', $invitation))
            ->assertForbidden();

        $this->assertSame(NominationInvitation::STATUS_PENDING, $invitation->fresh()->status);
        $this->assertDatabaseCount('nominations', 0);
    }

    public function test_an_ambassador_with_a_vote_cannot_invite(): void
    {
        $zone = $this->makeZone();
        $inviter = $this->ambassadorInZone($zone);
        $candidate = $this->ambassadorInZone($zone);
        $target = $this->ambassadorInZone($zone);

        $nomination = Nomination::factory()->create([
            'ambassador_id' => $candidate->id,
            'state_id' => $zone['state']->id,
            'senatorial_district_id' => $zone['district']->id,
            'nominated_by' => $candidate->id,
        ]);

        NominationVote::factory()->create([
            'nomination_id' => $nomination->id,
            'voter_id' => $inviter->id,
            'senatorial_district_id' => $zone['district']->id,
        ]);

        $this->actingAs($inviter, 'ambassador')
            ->from(route('tmgamb.dashboard'))
            ->post(route('tmgamb.invite', $target))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('nomination_invitations', 0);
    }
}
