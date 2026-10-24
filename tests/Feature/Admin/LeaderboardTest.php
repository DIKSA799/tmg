<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Nomination;
use App\Models\NominationVote;
use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function seedLeaderboard(): array
    {
        $alphaState = State::factory()->create(['name' => 'Alpha State']);
        $alphaDistrict = SenatorialDistrict::factory()->for($alphaState)->create(['name' => 'Alpha North']);
        $alphaZone = $this->makeZone($alphaState, $alphaDistrict);

        $betaState = State::factory()->create(['name' => 'Beta State']);
        $betaDistrict = SenatorialDistrict::factory()->for($betaState)->create(['name' => 'Beta South']);
        $betaZone = $this->makeZone($betaState, $betaDistrict);

        $alphaLeader = $this->ambassadorInZone($alphaZone, [
            'name' => 'Alpha Leader',
            'phone' => '+2348030000101',
            'email' => 'alpha.leader@example.com',
        ]);
        $alphaRunner = $this->ambassadorInZone($alphaZone, [
            'name' => 'Alpha Runner',
            'phone' => '+2348030000102',
        ]);
        $betaLeader = $this->ambassadorInZone($betaZone, [
            'name' => 'Beta Leader',
            'phone' => '+2348030000103',
        ]);
        $betaRunner = $this->ambassadorInZone($betaZone, [
            'name' => 'Beta Runner',
            'phone' => '+2348030000104',
        ]);

        return [
            'alpha_state' => $alphaState,
            'alpha_district' => $alphaDistrict,
            'alpha_zone' => $alphaZone,
            'beta_state' => $betaState,
            'beta_district' => $betaDistrict,
            'beta_zone' => $betaZone,
            'alpha_leader' => $alphaLeader,
            'alpha_runner' => $alphaRunner,
            'beta_leader' => $betaLeader,
            'beta_runner' => $betaRunner,
            'alpha_leader_nomination' => $this->nominate($alphaLeader, $alphaZone, 50),
            'alpha_runner_nomination' => $this->nominate($alphaRunner, $alphaZone, 30),
            'beta_leader_nomination' => $this->nominate($betaLeader, $betaZone, 40),
            'beta_runner_nomination' => $this->nominate($betaRunner, $betaZone, 10),
        ];
    }

    public function test_the_leaderboard_lists_nominations_in_vote_order(): void
    {
        $this->seedLeaderboard();

        $response = $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard'));

        $response->assertOk()
            ->assertSee('Full ranking')
            ->assertSee('Top 3')
            ->assertSeeInOrder(['Alpha Leader', 'Beta Leader', 'Alpha Runner', 'Beta Runner']);

        $response->assertSee('Alpha North')
            ->assertSee('Beta South');
    }

    public function test_it_filters_by_state(): void
    {
        $data = $this->seedLeaderboard();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard', ['state_id' => $data['alpha_state']->id]))
            ->assertOk()
            ->assertSee('Alpha Leader')
            ->assertSee('Alpha Runner')
            ->assertDontSee('Beta Leader')
            ->assertDontSee('Beta Runner');
    }

    public function test_it_filters_by_senatorial_district(): void
    {
        $data = $this->seedLeaderboard();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard', ['district_id' => $data['beta_district']->id]))
            ->assertOk()
            ->assertSee('Beta Leader')
            ->assertSee('Beta Runner')
            ->assertDontSee('Alpha Leader')
            ->assertDontSee('Alpha Runner');
    }

    public function test_it_searches_by_name_and_phone(): void
    {
        $this->seedLeaderboard();
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leaderboard', ['q' => 'Runner']))
            ->assertOk()
            ->assertSee('Alpha Runner')
            ->assertSee('Beta Runner')
            ->assertDontSee('Alpha Leader')
            ->assertDontSee('Beta Leader');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leaderboard', ['q' => '+2348030000103']))
            ->assertOk()
            ->assertSee('Beta Leader')
            ->assertDontSee('Alpha Leader')
            ->assertDontSee('Beta Runner');
    }

    public function test_it_filters_by_status(): void
    {
        $data = $this->seedLeaderboard();
        $data['beta_runner_nomination']->update(['status' => Nomination::STATUS_WITHDRAWN]);
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leaderboard'))
            ->assertOk()
            ->assertDontSee('Beta Runner');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.leaderboard', ['status' => 'withdrawn']))
            ->assertOk()
            ->assertSee('Beta Runner')
            ->assertDontSee('Alpha Leader');
    }

    public function test_the_states_page_shows_totals_and_the_leading_nominee(): void
    {
        $this->seedLeaderboard();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard.states'))
            ->assertOk()
            ->assertSee('States at a glance')
            ->assertSee('Alpha State')
            ->assertSee('Beta State')
            ->assertSee('Alpha Leader')
            ->assertSee('Beta Leader');
    }

    public function test_the_states_page_shows_the_district_breakdown(): void
    {
        $data = $this->seedLeaderboard();

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard.states', ['state_id' => $data['alpha_state']->id]))
            ->assertOk()
            ->assertSee('Alpha State senatorial districts')
            ->assertSee('Alpha North')
            ->assertSee('Alpha Leader');
    }

    public function test_the_detail_page_shows_full_details_and_recent_voters(): void
    {
        $state = State::factory()->create(['name' => 'Gamma State']);
        $district = SenatorialDistrict::factory()->for($state)->create(['name' => 'Gamma Central']);
        $zone = $this->makeZone($state, $district);

        $nominee = $this->ambassadorInZone($zone, [
            'name' => 'Gamma Nominee',
            'phone' => '+2348030000200',
            'email' => 'gamma@example.com',
        ]);

        $nomination = $this->nominate($nominee, $zone, 2);

        $voterOne = $this->ambassadorInZone($zone, ['name' => 'Voter One', 'phone' => '+2348030000201']);
        $voterTwo = $this->ambassadorInZone($zone, ['name' => 'Voter Two', 'phone' => '+2348030000202']);

        NominationVote::factory()->create([
            'nomination_id' => $nomination->id,
            'voter_id' => $voterOne->id,
            'senatorial_district_id' => $district->id,
        ]);

        NominationVote::factory()->create([
            'nomination_id' => $nomination->id,
            'voter_id' => $voterTwo->id,
            'senatorial_district_id' => $district->id,
        ]);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard.show', $nomination))
            ->assertOk()
            ->assertSee('Gamma Nominee')
            ->assertSee('+2348030000200')
            ->assertSee('gamma@example.com')
            ->assertSee('Gamma Central')
            ->assertSee($zone['lga']->name)
            ->assertSee($zone['ward']->name)
            ->assertSee($zone['unit']->name)
            ->assertSee('Self-nomination')
            ->assertSee('Recent voters')
            ->assertSee('Voter One')
            ->assertSee('Voter Two');
    }

    public function test_guests_cannot_open_the_leaderboard(): void
    {
        $this->get(route('admin.leaderboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.leaderboard.states'))->assertRedirect(route('admin.login'));
    }

    public function test_an_unknown_nomination_returns_404(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.leaderboard.show', 999999))
            ->assertNotFound();
    }

    private function nominate($ambassador, array $zone, int $votes): Nomination
    {
        return Nomination::factory()->create([
            'ambassador_id' => $ambassador->id,
            'state_id' => $zone['state']->id,
            'senatorial_district_id' => $zone['district']->id,
            'nominated_by' => $ambassador->id,
            'votes_count' => $votes,
            'statement' => 'For the zone.',
        ]);
    }
}
