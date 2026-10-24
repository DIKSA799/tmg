<?php

namespace Tests\Feature\Ambassador;

use App\Mail\AmbassadorPasswordReset;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_reset_emails_the_tmg_number_and_restores_it_as_the_password(): void
    {
        Mail::fake();

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone, ['email' => 'owner@example.com']);
        $reference = $me->voterRecord->reference;

        // Simulate a password the ambassador changed and then forgot.
        $me->forceFill(['password' => 'CUSTOM PASS'])->save();

        $this->post(route('tmgamb.password.email'), ['identity' => $me->phone])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(AmbassadorPasswordReset::class, function (AmbassadorPasswordReset $mail) use ($me, $reference): bool {
            return $mail->hasTo('owner@example.com')
                && $mail->reference === $reference
                && $mail->ambassador->is($me);
        });

        $this->assertTrue(Hash::check(strtoupper($reference), $me->fresh()->password));
    }

    public function test_the_email_identity_also_works(): void
    {
        Mail::fake();

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone, ['email' => 'owner@example.com']);

        $this->post(route('tmgamb.password.email'), ['identity' => 'OWNER@EXAMPLE.COM'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(AmbassadorPasswordReset::class);
    }

    public function test_unknown_identities_get_the_same_generic_message(): void
    {
        Mail::fake();

        $this->post(route('tmgamb.password.email'), ['identity' => '+2348000000000'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
    }

    public function test_an_account_without_an_email_is_not_reset(): void
    {
        Mail::fake();

        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone, ['email' => null]);
        $original = $me->password;

        $this->post(route('tmgamb.password.email'), ['identity' => $me->phone])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingSent();
        $this->assertSame($original, $me->fresh()->password);
    }

    public function test_the_reset_email_contains_the_tmg_number(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone, ['email' => 'owner@example.com']);
        $reference = $me->voterRecord->reference;

        $mailable = new AmbassadorPasswordReset($me, $reference);

        $mailable->assertHasSubject('Your TMG Ambassador sign-in details');
        $mailable->assertSeeInHtml($reference);
        $mailable->assertSeeInText($reference);
        $mailable->assertSeeInHtml('case-sensitive');
    }

    public function test_the_forgot_password_page_renders(): void
    {
        $this->get(route('tmgamb.password.request'))
            ->assertOk()
            ->assertSee('Forgot your password?', false)
            ->assertSee('Email my TMG number', false);
    }

    public function test_a_signed_in_ambassador_is_sent_back_to_the_dashboard(): void
    {
        $zone = $this->makeZone();
        $me = $this->ambassadorInZone($zone);

        $this->actingAs($me, 'ambassador')
            ->get(route('tmgamb.password.request'))
            ->assertRedirect(route('tmgamb.dashboard'));
    }
}
