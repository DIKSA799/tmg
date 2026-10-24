<?php

namespace Tests\Feature\Ambassador;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_an_ambassador_can_change_their_password(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $reference = $me->voterRecord->reference;

        $this->actingAs($me, 'ambassador')
            ->post(route('tmgamb.password.update'), [
                'current_password' => strtolower($reference),
                'password' => 'a new secret',
                'password_confirmation' => 'a new secret',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $me->refresh();

        $this->assertTrue(Hash::check('A NEW SECRET', $me->password));
        $this->assertFalse(Hash::check($reference, $me->password));
    }

    public function test_the_current_password_must_match(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $original = $me->password;

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.profile'))
            ->post(route('tmgamb.password.update'), [
                'current_password' => 'not-my-password',
                'password' => 'a new secret',
                'password_confirmation' => 'a new secret',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertSame($original, $me->fresh()->password);
    }

    public function test_the_new_password_must_be_confirmed(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $reference = $me->voterRecord->reference;

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.profile'))
            ->post(route('tmgamb.password.update'), [
                'current_password' => $reference,
                'password' => 'a new secret',
                'password_confirmation' => 'something else',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check($reference, $me->fresh()->password));
    }

    public function test_the_new_password_must_be_at_least_eight_characters(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $reference = $me->voterRecord->reference;

        $this->actingAs($me, 'ambassador')
            ->from(route('tmgamb.profile'))
            ->post(route('tmgamb.password.update'), [
                'current_password' => $reference,
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_the_changed_password_signs_in_case_insensitively(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);
        $reference = $me->voterRecord->reference;

        $this->actingAs($me, 'ambassador')
            ->post(route('tmgamb.password.update'), [
                'current_password' => $reference,
                'password' => 'a new secret',
                'password_confirmation' => 'a new secret',
            ])
            ->assertRedirect();

        $this->post('/tmgamb/logout')->assertRedirect(route('tmgamb.login'));

        $this->post('/tmgamb', [
            'identity' => $me->phone,
            'password' => 'A nEw SeCrEt',
        ])->assertRedirect(route('tmgamb.dashboard'));

        $this->assertAuthenticatedAs($me->fresh(), 'ambassador');
    }

    public function test_the_profile_page_shows_the_password_form(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->get(route('tmgamb.profile'))
            ->assertOk()
            ->assertSee('Change password')
            ->assertSee('data-uppercase', false);
    }
}
