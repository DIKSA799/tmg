<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TmgUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in', false);
    }

    public function test_seeded_credentials_sign_in_and_reach_the_register_page(): void
    {
        $this->seed(TmgUserSeeder::class);

        $this->post('/login', ['username' => 'tmguser', 'password' => 'passwd20'])
            ->assertRedirect(route('register'));

        $this->assertAuthenticated();
    }

    public function test_signing_in_returns_guests_to_the_page_they_requested(): void
    {
        $this->seed(TmgUserSeeder::class);

        $this->get('/register')->assertRedirect(route('login'));

        $this->post('/login', ['username' => 'tmguser', 'password' => 'passwd20'])
            ->assertRedirect(route('register'));

        $this->assertAuthenticated();
    }

    public function test_username_is_matched_case_insensitively(): void
    {
        $this->seed(TmgUserSeeder::class);

        $this->post('/login', ['username' => 'TMGUSER', 'password' => 'passwd20'])
            ->assertRedirect(route('register'));

        $this->assertAuthenticated();
    }

    public function test_wrong_password_returns_an_error_without_authenticating(): void
    {
        $this->seed(TmgUserSeeder::class);

        $this->from('/login')
            ->post('/login', ['username' => 'tmguser', 'password' => 'not-the-password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $this->seed(TmgUserSeeder::class);

        foreach (range(1, 5) as $attempt) {
            $this->from('/login')->post('/login', ['username' => 'tmguser', 'password' => 'wrong-password']);
        }

        $this->from('/login')
            ->post('/login', ['username' => 'tmguser', 'password' => 'passwd20'])
            ->assertSessionHasErrors('username');

        $this->assertStringContainsString('Too many login attempts', session('errors')->first('username'));
        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
