<?php

namespace Tests\Feature;

use App\Jobs\SendAmbassadorWelcomeEmail;
use App\Mail\AmbassadorWelcome;
use App\Models\VoterRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AmbassadorWelcomeEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_welcome_email_shows_the_member_reference_and_details(): void
    {
        $record = VoterRecord::factory()->create([
            'full_name' => 'Amina Musa',
            'reference' => 'TMG-DEL-NDW-UTO-033-K7Q2M9',
            'volunteer_category' => 'grassroots_mobilisation',
        ]);

        $record->loadMissing(['state', 'lga', 'ward', 'pollingUnit']);

        $mailable = new AmbassadorWelcome($record);

        $mailable->assertHasSubject(fn (string $subject): bool => str_contains($subject, 'TMG-DEL-NDW-UTO-033-K7Q2M9'));
        $mailable->assertSeeInHtml('TMG-DEL-NDW-UTO-033-K7Q2M9');
        $mailable->assertSeeInHtml('Amina Musa');
        $mailable->assertSeeInHtml('You are now a TMG Ambassador');
        $mailable->assertSeeInHtml('Volunteer to Save Nigeria');
        $mailable->assertSeeInHtml('Grassroots Mobilisation');
    }

    public function test_the_welcome_email_has_a_plain_text_version(): void
    {
        $record = VoterRecord::factory()->create(['reference' => 'TMG-KAN-ALB-ALC-019-AB12CD']);

        (new AmbassadorWelcome($record))->assertSeeInText('TMG-KAN-ALB-ALC-019-AB12CD');
    }

    public function test_the_job_emails_the_registered_ambassador(): void
    {
        Mail::fake();

        $record = VoterRecord::factory()->create(['email' => 'amina@example.com']);

        (new SendAmbassadorWelcomeEmail($record->id))->handle();

        Mail::assertSent(AmbassadorWelcome::class, fn (AmbassadorWelcome $mail): bool => $mail->hasTo('amina@example.com'));
    }

    public function test_the_job_does_nothing_without_an_email_address(): void
    {
        Mail::fake();

        $record = VoterRecord::factory()->create(['email' => null]);

        (new SendAmbassadorWelcomeEmail($record->id))->handle();

        Mail::assertNothingSent();
    }
}
