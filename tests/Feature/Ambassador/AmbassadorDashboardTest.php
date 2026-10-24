<?php

namespace Tests\Feature\Ambassador;

use App\Models\Nomination;
use App\Models\Setting;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AmbassadorDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_dashboard_only_lists_ambassadors_in_the_same_district(): void
    {
        $state = State::factory()->create();
        $zoneA = $this->makeZone($state);
        $zoneB = $this->makeZone($state);

        $me = $this->ambassadorInZone($zoneA, ['name' => 'Me Myself']);
        $neighbour = $this->ambassadorInZone($zoneA, ['name' => 'Neighbour Person']);
        $outsider = $this->ambassadorInZone($zoneB, ['name' => 'Outsider Person']);

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertOk()
            ->assertSee($neighbour->name)
            ->assertDontSee($outsider->name);
    }

    public function test_the_zone_list_can_be_searched_by_name_and_phone(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone, ['name' => 'Me Myself']);
        $target = $this->ambassadorInZone($zone, [
            'name' => 'Zainab Unique',
            'phone' => '+2348099999999',
        ]);
        $other = $this->ambassadorInZone($zone, ['name' => 'Other Person']);

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/dashboard?q=Zainab')
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/dashboard?q='.urlencode('+2348099999999'))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);
    }

    public function test_terms_and_the_information_notice_appear_when_required(): void
    {
        Setting::put(Setting::NOTICE_ENABLED, '1');
        Setting::put(Setting::NOTICE_TITLE, 'Zone privacy notice');
        Setting::put(Setting::NOTICE_BODY, 'We keep your data safe.');

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertOk()
            ->assertSee('Zone privacy notice')
            ->assertSee('We keep your data safe.')
            ->assertSee('Nomination and voting terms');
    }

    public function test_acknowledged_terms_and_notice_are_not_shown_again(): void
    {
        Setting::put(Setting::NOTICE_ENABLED, '1');
        Setting::put(Setting::NOTICE_TITLE, 'Zone privacy notice');
        Setting::put(Setting::NOTICE_BODY, 'We keep your data safe.');

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $me->forceFill([
            'terms_accepted_at' => now(),
            'notice_seen_at' => now(),
        ])->save();

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertOk()
            ->assertDontSee('Zone privacy notice')
            ->assertDontSee('Nomination and voting terms');
    }

    public function test_the_leaderboard_shows_zone_badges_and_the_state_top_five(): void
    {
        $state = State::factory()->create();
        $zoneA = $this->makeZone($state);
        $zoneB = $this->makeZone($state);

        $me = $this->ambassadorInZone($zoneA, ['name' => 'Zone Admin']);

        $topNames = [];

        foreach ([100, 90, 80] as $index => $votes) {
            $ambassador = $this->ambassadorInZone($zoneA, ['name' => 'Zone Leader '.($index + 1)]);
            $topNames[] = $ambassador->name;
            $this->nominate($ambassador, $zoneA, $votes);
        }

        foreach ([70, 60, 50, 40, 30, 20] as $index => $votes) {
            $ambassador = $this->ambassadorInZone($zoneB, ['name' => 'State Nominee '.($index + 1)]);
            $topNames[] = $ambassador->name;
            $this->nominate($ambassador, $zoneB, $votes);
        }

        $lowest = 'State Nominee 6';

        $this->actingAs($me, 'ambassador')
            ->get('/tmgamb/leaderboard')
            ->assertOk()
            ->assertSee('1st')
            ->assertSee('2nd')
            ->assertSee('3rd')
            ->assertSee('Zone Leader 1')
            ->assertSee('Zone Leader 2')
            ->assertSee('Zone Leader 3')
            // State top five: 100, 90, 80, 70, 60.
            ->assertSee('State Nominee 1')
            ->assertSee('State Nominee 2')
            // 50 is the sixth highest and must not be in the state top five.
            ->assertDontSee('State Nominee 3')
            ->assertDontSee($lowest);
    }

    public function test_an_ambassador_outside_the_zone_cannot_open_a_nominee_page(): void
    {
        $state = State::factory()->create();
        $zoneA = $this->makeZone($state);
        $zoneB = $this->makeZone($state);

        $me = $this->ambassadorInZone($zoneA);
        $other = $this->ambassadorInZone($zoneB, ['name' => 'Other Zone Leader']);
        $nomination = $this->nominate($other, $zoneB, 5);

        $this->actingAs($me, 'ambassador')
            ->get(route('tmgamb.nominee', $nomination->public_id))
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
        ]);
    }
}
