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
        $response->assertSee('Register a voter', false);
        $response->assertSee('name="full_name"', false);
        $response->assertSee('name="polling_unit_id"', false);
        $response->assertSee('data-theme-toggle', false);
    }
}
