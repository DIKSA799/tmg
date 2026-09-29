<?php

namespace Tests\Feature;

use App\Models\State;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_renders_the_capture_form(): void
    {
        State::factory()->create();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Capture a voter', false);
        $response->assertSee('name="full_name"', false);
        $response->assertSee('name="polling_unit_id"', false);
        $response->assertSee('name="consent_to_data"', false);
    }
}
