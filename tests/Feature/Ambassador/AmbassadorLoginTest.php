<?php

namespace Tests\Feature\Ambassador;

use App\Models\Ambassador;
use App\Models\User;
use App\Models\VoterRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_a_record_without_an_account_can_sign_in_and_is_provisioned(): void
    {
        $zone = $this->makeZone();
        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-LAZY-001-ABC-XYZ123',
            'phone' => '+2348037777777',
            'email' => 'lazy@example.com',
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        $this->assertDatabaseCount('ambassadors', 0);

        $this->post('/tmgamb', [
            'identity' => '0803 777 7777',
            'password' => strtolower('TMG-LAZY-001-ABC-XYZ123'),
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticated('ambassador');

        $ambassador = Ambassador::query()->sole();

        $this->assertSame($record->id, $ambassador->voter_record_id);
        $this->assertSame($zone['district']->id, $ambassador->senatorial_district_id);
        $this->assertTrue(Hash::check('TMG-LAZY-001-ABC-XYZ123', $ambassador->password));
    }

    public function test_a_record_without_an_account_can_sign_in_with_a_mixed_case_email(): void
    {
        $zone = $this->makeZone();
        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-LAZY-002-DEF-ABC456',
            'email' => 'Lazy@Example.com',
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        $this->post('/tmgamb', [
            'identity' => 'LAZY@EXAMPLE.COM',
            'password' => 'tmg-lazy-002-def-abc456',
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticated('ambassador');
        $this->assertDatabaseHas('ambassadors', ['voter_record_id' => $record->id]);
    }

    public function test_the_tmg_number_cannot_bypass_a_changed_password(): void
    {
        $zone = $this->makeZone();
        $ambassador = $this->ambassadorInZone($zone);
        $reference = $ambassador->voterRecord->reference;

        $ambassador->forceFill(['password' => 'CHANGED PASSWORD'])->save();

        $this->from('/tmgamb')
            ->post('/tmgamb', [
                'identity' => $ambassador->phone,
                'password' => $reference,
            ])
            ->assertRedirect('/tmgamb')
            ->assertSessionHasErrors('identity');

        $this->assertGuest('ambassador');
    }

    public function test_a_wrong_tmg_number_does_not_provision_an_account(): void
    {
        $zone = $this->makeZone();
        $record = VoterRecord::factory()->create([
            'reference' => 'TMG-LAZY-003-GHI-XYZ789',
            'state_id' => $zone['state']->id,
            'lga_id' => $zone['lga']->id,
            'ward_id' => $zone['ward']->id,
            'polling_unit_id' => $zone['unit']->id,
        ]);

        $this->from('/tmgamb')
            ->post('/tmgamb', [
                'identity' => $record->phone,
                'password' => 'TMG-NOT-THE-NUMBER',
            ])
            ->assertRedirect('/tmgamb')
            ->assertSessionHasErrors('identity');

        $this->assertDatabaseCount('ambassadors', 0);
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
