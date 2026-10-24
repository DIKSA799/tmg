<?php

namespace Tests\Feature\Ambassador;

use App\Models\CommunityLink;
use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CommunityLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_district_link_wins_over_state_and_general_links(): void
    {
        $state = State::factory()->create();
        $district = SenatorialDistrict::factory()->for($state)->create();

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_TELEGRAM,
            'scope_type' => CommunityLink::SCOPE_GLOBAL,
            'scope_id' => null,
            'url' => 'https://t.me/general',
        ]);

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_TELEGRAM,
            'scope_type' => CommunityLink::SCOPE_STATE,
            'scope_id' => $state->id,
            'url' => 'https://t.me/state',
        ]);

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_TELEGRAM,
            'scope_type' => CommunityLink::SCOPE_DISTRICT,
            'scope_id' => $district->id,
            'url' => 'https://t.me/district',
        ]);

        $resolved = CommunityLink::resolve(
            CommunityLink::PLATFORM_TELEGRAM,
            $state->id,
            $district->id,
        );

        $this->assertSame('https://t.me/district', $resolved?->url);
    }

    public function test_it_falls_back_to_the_state_then_the_general_link(): void
    {
        $state = State::factory()->create();
        $district = SenatorialDistrict::factory()->for($state)->create();

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_WHATSAPP,
            'scope_type' => CommunityLink::SCOPE_GLOBAL,
            'scope_id' => null,
            'url' => 'https://chat.whatsapp.com/general',
        ]);

        $stateLink = CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_WHATSAPP,
            'scope_type' => CommunityLink::SCOPE_STATE,
            'scope_id' => $state->id,
            'url' => 'https://chat.whatsapp.com/state',
        ]);

        $this->assertSame(
            'https://chat.whatsapp.com/state',
            CommunityLink::resolve(CommunityLink::PLATFORM_WHATSAPP, $state->id, $district->id)?->url,
        );

        $stateLink->update(['is_active' => false]);

        $this->assertSame(
            'https://chat.whatsapp.com/general',
            CommunityLink::resolve(CommunityLink::PLATFORM_WHATSAPP, $state->id, $district->id)?->url,
        );
    }

    public function test_the_floating_links_appear_on_the_dashboard(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_TELEGRAM,
            'scope_type' => CommunityLink::SCOPE_DISTRICT,
            'scope_id' => $zone['district']->id,
            'url' => 'https://t.me/zone-community',
        ]);

        CommunityLink::factory()->create([
            'platform' => CommunityLink::PLATFORM_WHATSAPP,
            'scope_type' => CommunityLink::SCOPE_GLOBAL,
            'scope_id' => null,
            'url' => 'https://chat.whatsapp.com/tmg',
        ]);

        $this->actingAs($ambassador, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertOk()
            ->assertSee('https://t.me/zone-community', false)
            ->assertSee('https://chat.whatsapp.com/tmg', false);
    }
}
