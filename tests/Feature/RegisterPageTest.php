<?php

namespace Tests\Feature;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_from_the_register_page_to_login(): void
    {
        $this->get('/register')->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_register_page(): void
    {
        State::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get('/register');

        $response->assertOk();
        $response->assertSee('Be part of #TMG', false);
        $response->assertSee('name="full_name"', false);
        $response->assertSee('name="polling_unit_id"', false);
        $response->assertSee('data-theme-toggle', false);
    }

    public function test_register_page_does_not_lock_the_record_to_a_country(): void
    {
        State::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get('/register');

        $response->assertOk();
        $response->assertDontSee('Country is fixed to Nigeria', false);
        $response->assertDontSee('name="country"', false);
    }
}
