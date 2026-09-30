<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_landing_page_is_public(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('TMG Ambassadors Space', false);
        $response->assertSee('Become a TMG Ambassador', false);
        $response->assertSee('Alhaji Atiku Abubakar', false);
        $response->assertDontSee('Sign in', false);
    }

    public function test_signed_in_users_see_the_same_landing_page(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/');

        $response->assertOk();
        $response->assertSee('TMG Ambassadors Space', false);
    }

    public function test_landing_page_publishes_social_preview_metadata(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('property="og:image" content="'.asset('assets/og-image.png').'"', false);
        $response->assertSee('twitter:card" content="summary_large_image"', false);
        $response->assertSee('rel="icon" href="'.asset('assets/favicon.png').'"', false);
    }

    public function test_landing_page_uses_the_theme_toggle_and_information_dialogs(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="themeToggle"', false);
        $response->assertSee('data-modal="information"', false);
        $response->assertSee('data-modal="downloads"', false);
        $response->assertSee('data-modal="privacy"', false);
    }

    public function test_landing_page_links_to_the_register_page_without_hosting_the_capture_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('register'), false);
        $response->assertDontSee('data-capture-form', false);
    }
}
