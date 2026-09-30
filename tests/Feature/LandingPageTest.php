<?php

namespace Tests\Feature;

use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_renders_the_movement_content(): void
    {
        State::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Tinubu Must Go!', false);
        $response->assertSee('Movement information', false);
        $response->assertSee('Downloads / Brand Assets', false);
        $response->assertSee('Privacy Notice', false);
        $response->assertSee('A Civic Platform for Accountability, Public Dialogue and Democratic Participation', false);
    }

    public function test_landing_page_publishes_social_preview_metadata(): void
    {
        State::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:image" content="'.asset('t1.png').'"', false);
        $response->assertSee('twitter:card" content="summary_large_image"', false);
        $response->assertSee('rel="icon" href="'.asset('favicon-32.png').'"', false);
        $response->assertSee('rel="apple-touch-icon" href="'.asset('apple-touch-icon.png').'"', false);
    }

    public function test_landing_page_uses_the_movement_logo_and_interface_controls(): void
    {
        State::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(asset('t2.png'), false);
        $response->assertSee('data-contrast-toggle', false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('data-scramble', false);
    }

    public function test_landing_page_no_longer_hosts_the_capture_form(): void
    {
        State::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('data-capture-form', false);
    }

    public function test_landing_page_links_to_the_register_page(): void
    {
        State::factory()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('register'), false)
            ->assertSee('Be part of #TMG', false);
    }
}
