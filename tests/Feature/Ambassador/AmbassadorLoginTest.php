<?php

namespace Tests\Feature\Ambassador;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AmbassadorLoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/tmgamb')
            ->assertOk()
            ->assertSee('Ambassador sign-in', false)
            ->assertSee('TMG number', false)
            ->assertSee('Forgot password?', false)
            ->assertSee('data-uppercase', false);
    }

    public function test_phone_and_tmg_number_sign_in(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);
        $reference = $ambassador->voterRecord->reference;

        // The TMG number is matched without regard to case.
        $this->post('/tmgamb', [
            'identity' => $ambassador->phone,
            'password' => strtolower($reference),
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticatedAs($ambassador, 'ambassador');
        $this->assertNotNull($ambassador->fresh()->last_login_at);
    }

    public function test_email_sign_in_is_case_insensitive(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone, ['email' => 'Amina@Example.com']);
        $reference = $ambassador->voterRecord->reference;

        $this->post('/tmgamb', [
            'identity' => 'AMINA@EXAMPLE.COM',
            'password' => $reference,
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticatedAs($ambassador, 'ambassador');
    }

    public function test_wrong_password_does_not_sign_in(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);

        $this->from('/tmgamb')
            ->post('/tmgamb', [
                'identity' => $ambassador->phone,
                'password' => 'TMG-WRONG-NUMBER',
            ])
            ->assertRedirect('/tmgamb')
            ->assertSessionHasErrors('identity');

        $this->assertGuest('ambassador');
    }

    public function test_guests_are_redirected_to_the_ambassador_login(): void
    {
        $this->get('/tmgamb/dashboard')->assertRedirect(route('tmgamb.login'));
        $this->get('/tmgamb/leaderboard')->assertRedirect(route('tmgamb.login'));
        $this->get('/tmgamb/profile')->assertRedirect(route('tmgamb.login'));
    }

    public function test_web_users_cannot_enter_the_ambassador_space(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/tmgamb/dashboard')
            ->assertRedirect(route('tmgamb.login'));
    }

    public function test_signed_in_ambassador_is_redirected_from_the_login_page(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);

        $this->actingAs($ambassador, 'ambassador')
            ->get('/tmgamb')
            ->assertRedirect(route('tmgamb.dashboard'));
    }

    public function test_ambassador_can_sign_out(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);

        $this->actingAs($ambassador, 'ambassador')
            ->post('/tmgamb/logout')
            ->assertRedirect(route('tmgamb.login'));

        $this->assertGuest('ambassador');
    }

    public function test_repeated_failures_are_rate_limited(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);

        foreach (range(1, 5) as $attempt) {
            $this->from('/tmgamb')->post('/tmgamb', [
                'identity' => $ambassador->phone,
                'password' => 'wrong',
            ]);
        }

        $this->from('/tmgamb')
            ->post('/tmgamb', [
                'identity' => $ambassador->phone,
                'password' => $ambassador->voterRecord->reference,
            ])
            ->assertSessionHasErrors('identity');

        $this->assertStringContainsString(
            'Too many sign-in attempts',
            (string) session('errors')?->first('identity'),
        );

        $this->assertGuest('ambassador');
    }
}
