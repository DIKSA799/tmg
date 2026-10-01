<?php

namespace Tests\Feature;

use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class RegisterPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_can_open_the_register_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Be part of #TMG', false);
    }

    public function test_authenticated_users_can_open_the_register_page(): void
    {
        State::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get('/register');

        $response->assertOk();
        $response->assertSee('Be part of #TMG', false);
        $response->assertSee('Become a TMG Ambassador', false);
        $response->assertSee('Volunteer to Save Nigeria', false);
        $response->assertSee("Support TMG's chosen candidate, Atiku Abubakar.", false);
        $response->assertSee('name="full_name"', false);
        $response->assertSee('name="whatsapp"', false);
        $response->assertSee('name="email"', false);
        $response->assertSee('name="volunteer_category"', false);
        $response->assertSee('name="occupation"', false);
        $response->assertSee('name="has_disability"', false);
        $response->assertSee('Do you have a disability?', false);
        $response->assertSee('I voluntarily join TMG and undertake to promote its objectives', false);
        $response->assertSee('name="pledge_accepted"', false);
        $response->assertSee('Grassroots Mobilisation', false);
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
