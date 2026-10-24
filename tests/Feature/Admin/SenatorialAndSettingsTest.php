<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\CommunityLink;
use App\Models\Setting;
use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SenatorialAndSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_senatorial_browser_lists_zones_and_their_lgas(): void
    {
        $zone = $this->makeZone();
        $zone['district']->update(['name' => 'Zone Alpha']);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.senatorial'))
            ->assertOk()
            ->assertSee('Zone Alpha')
            ->assertSee('All senatorial districts')
            ->assertSee($zone['state']->name);

        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get(route('admin.senatorial', ['state_id' => $zone['state']->id]))
            ->assertOk()
            ->assertSee('Zone Alpha')
            ->assertSee($zone['lga']->name);
    }

    public function test_settings_can_be_saved(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->post(route('admin.settings.update'), [
                'notice_enabled' => '1',
                'notice_title' => 'Heads up',
                'notice_body' => 'Notice body text.',
                'terms_title' => 'Our terms',
                'terms_body' => 'Terms body text.',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame('1', Setting::get(Setting::NOTICE_ENABLED));
        $this->assertSame('Heads up', Setting::get(Setting::NOTICE_TITLE));
        $this->assertSame('Notice body text.', Setting::get(Setting::NOTICE_BODY));
        $this->assertSame('Our terms', Setting::get(Setting::TERMS_TITLE));
        $this->assertSame('Terms body text.', Setting::get(Setting::TERMS_BODY));
    }

    public function test_the_notice_toggle_controls_what_ambassadors_see(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);
        $admin = Admin::factory()->create();

        $payload = [
            'notice_title' => 'Heads up',
            'notice_body' => 'Notice body text.',
            'terms_title' => 'Our terms',
            'terms_body' => 'Terms body text.',
        ];

        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $payload + ['notice_enabled' => '1']);

        $this->actingAs($ambassador, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertSee('Heads up');

        $this->actingAs($admin, 'admin')->post(route('admin.settings.update'), $payload + ['notice_enabled' => '0']);

        $this->actingAs($ambassador, 'ambassador')
            ->get('/tmgamb/dashboard')
            ->assertDontSee('Heads up');
    }

    public function test_community_links_can_be_added_and_removed(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.community-links.store'), [
                'platform' => 'telegram',
                'scope_type' => 'global',
                'label' => 'General Telegram',
                'url' => 'https://t.me/tmg',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $link = CommunityLink::query()->sole();

        $this->assertSame('telegram', $link->platform);
        $this->assertSame('global', $link->scope_type);
        $this->assertNull($link->scope_id);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.community-links.destroy', $link))
            ->assertRedirect();

        $this->assertDatabaseCount('community_links', 0);
    }

    public function test_a_state_scoped_link_records_the_state(): void
    {
        $state = State::factory()->create();
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.community-links.store'), [
                'platform' => 'whatsapp',
                'scope_type' => 'state',
                'state_id' => $state->id,
                'url' => 'https://chat.whatsapp.com/abc',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('community_links', [
            'platform' => 'whatsapp',
            'scope_type' => 'state',
            'scope_id' => $state->id,
        ]);
    }

    public function test_a_district_link_requires_a_district(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->from(route('admin.settings'))
            ->post(route('admin.community-links.store'), [
                'platform' => 'telegram',
                'scope_type' => 'district',
                'url' => 'https://t.me/zone',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('district_id');

        $this->assertDatabaseCount('community_links', 0);
    }

    public function test_guests_cannot_open_the_senatorial_browser(): void
    {
        $this->get(route('admin.senatorial'))->assertRedirect(route('admin.login'));
    }
}
