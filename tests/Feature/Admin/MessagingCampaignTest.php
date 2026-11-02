<?php

namespace Tests\Feature\Admin;

use App\Enums\CampaignChannel;
use App\Mail\BroadcastEmail;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\Lga;
use App\Models\State;
use App\Models\VoterRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MessagingCampaignTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function operator(): self
    {
        return $this->actingAs(Admin::factory()->create(), 'admin');
    }

    private function configureTermii(): void
    {
        config([
            'services.termii.enabled' => true,
            'services.termii.key' => 'test-api-key',
            'services.termii.sender_id' => 'TMG',
            'services.termii.base_url' => 'https://api.ng.termii.com',
        ]);
    }

    private function configureSendchamp(): void
    {
        config([
            'services.sendchamp.enabled' => true,
            'services.sendchamp.key' => 'test-sendchamp-key',
            'services.sendchamp.sender_id' => 'Sendchamp',
            'services.sendchamp.base_url' => 'https://api.sendchamp.com/api/v1',
            'services.sendchamp.route' => 'non_dnd',
        ]);
    }

    public function test_guests_are_redirected_to_the_console_login(): void
    {
        $this->get(route('admin.messaging'))->assertRedirect(route('admin.login'));
    }

    public function test_the_messaging_page_renders_for_an_admin(): void
    {
        $this->operator()
            ->get(route('admin.messaging'))
            ->assertOk()
            ->assertSee('Compose a broadcast', false)
            ->assertSee('Recent campaigns', false);
    }

    public function test_the_recipient_count_reflects_the_selected_location(): void
    {
        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create();
        VoterRecord::factory()->count(3)->create(['state_id' => $state->id, 'lga_id' => $lga->id]);
        VoterRecord::factory()->create();

        $this->operator()
            ->getJson(route('admin.messaging.count', ['channel' => 'sms', 'state_id' => $state->id, 'lga_id' => $lga->id]))
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_a_sms_broadcast_can_target_all_records_without_a_state(): void
    {
        $this->configureTermii();
        VoterRecord::factory()->count(3)->create();

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'message' => 'Hello everyone',
            ])
            ->assertOk()
            ->assertJsonPath('total', 3);

        $campaign = Campaign::query()->sole();

        $this->assertSame(CampaignChannel::Sms, $campaign->channel);
        $this->assertSame('All records', $campaign->scope_label);
        $this->assertNull($campaign->state_id);
    }

    public function test_a_location_broadcast_queues_one_pending_message_per_record(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ikeja']);
        VoterRecord::factory()->count(3)->create(['state_id' => $state->id, 'lga_id' => $lga->id]);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'message' => 'Hello Lagos',
            ])
            ->assertOk()
            ->assertJsonPath('total', 3);

        $campaign = Campaign::query()->sole();

        $this->assertSame('Lagos — Ikeja', $campaign->scope_label);
        $this->assertDatabaseCount('campaign_messages', 3);
        $this->assertSame(3, $campaign->messages()->where('status', CampaignMessage::STATUS_PENDING)->count());
    }

    public function test_the_optional_test_recipients_are_queued_first_and_deduplicated(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create();
        VoterRecord::factory()->create(['state_id' => $state->id, 'lga_id' => $lga->id, 'phone' => '+2348031111111']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'lga_id' => $lga->id, 'phone' => '+2348032222222']);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'state_id' => $state->id,
                'message' => 'Hello Lagos',
                'test_recipients' => "08127234628\n+2348031111111",
            ])
            ->assertOk()
            ->assertJsonPath('total', 3);

        $campaign = Campaign::query()->sole();

        $this->assertSame(3, $campaign->total_recipients);
        $this->assertDatabaseCount('campaign_messages', 3);
        $this->assertSame(1, $campaign->messages()->where('recipient', '+2348031111111')->count());
        $this->assertSame('+2348127234628', $campaign->messages()->orderBy('id')->value('recipient'));
    }

    public function test_a_broadcast_can_pause_after_the_test_recipients_then_continue(): void
    {
        $this->configureTermii();
        Http::preventStrayRequests();
        Http::fake([
            'api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => '301', 'message' => 'Successfully Sent'], 200),
        ]);

        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create();
        VoterRecord::factory()->count(2)->create(['state_id' => $state->id, 'lga_id' => $lga->id]);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'state_id' => $state->id,
                'message' => 'Hello Lagos',
                'test_recipients' => '08127234628',
                'pause_after_test' => 1,
            ])
            ->assertOk();

        $campaign = Campaign::query()->sole();

        $this->assertTrue($campaign->paused);
        $this->assertSame('paused', $campaign->status);

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 1)
            ->assertJsonPath('paused', true)
            ->assertJsonPath('remaining', 2);

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign), ['resume' => true])
            ->assertOk()
            ->assertJsonPath('paused', false)
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('status', 'completed');

        $this->assertSame(3, $campaign->messages()->where('status', CampaignMessage::STATUS_SENT)->count());
    }

    public function test_skipping_recent_recipients_only_considers_the_same_channel(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'phone' => '+2348031111111', 'email' => 'a@example.com']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'phone' => '+2348032222222', 'email' => 'b@example.com']);

        // A recent EMAIL must not exclude an SMS recipient.
        CampaignMessage::factory()
            ->create(['channel' => CampaignChannel::Email->value, 'recipient' => 'a@example.com', 'status' => CampaignMessage::STATUS_SENT])
            ->forceFill(['created_at' => now()->subDay()])
            ->save();

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'state_id' => $state->id,
                'message' => 'Hello',
                'skip_recent' => 1,
                'skip_days' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->assertSame(0, Campaign::query()->latest('id')->first()->skipped_count);
    }

    public function test_skipping_recent_recipients_excludes_them_for_the_same_channel(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'phone' => '+2348031111111']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'phone' => '+2348032222222']);

        CampaignMessage::factory()
            ->create(['channel' => CampaignChannel::Sms->value, 'recipient' => '+2348031111111', 'status' => CampaignMessage::STATUS_SENT])
            ->forceFill(['created_at' => now()->subDay()])
            ->save();

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'records',
                'state_id' => $state->id,
                'message' => 'Hello',
                'skip_recent' => 1,
                'skip_days' => 7,
            ])
            ->assertOk()
            ->assertJsonPath('total', 1);

        $campaign = Campaign::query()->latest('id')->first();

        $this->assertSame(1, $campaign->skipped_count);
        $this->assertDatabaseMissing('campaign_messages', ['campaign_id' => $campaign->id, 'recipient' => '+2348031111111']);
    }

    public function test_failed_messages_can_be_retried(): void
    {
        $this->configureTermii();
        Http::preventStrayRequests();
        Http::fake([
            'api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => '301', 'message' => 'Successfully Sent'], 200),
        ]);

        $campaign = Campaign::factory()->create(['total_recipients' => 1, 'failed_count' => 1, 'status' => Campaign::STATUS_PARTIAL]);
        CampaignMessage::factory()->create([
            'campaign_id' => $campaign->id,
            'recipient' => '+2348031234567',
            'status' => CampaignMessage::STATUS_FAILED,
            'error' => 'SENDER_ID_NOT_APPROVED',
            'response_status' => 422,
        ]);

        $this->operator()
            ->postJson(route('admin.messaging.retry', $campaign))
            ->assertOk()
            ->assertJsonPath('remaining', 1);

        $retried = $campaign->messages()->sole();

        $this->assertSame('pending', $retried->status);
        $this->assertNull($retried->error);

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 1);

        $this->assertSame('sent', $campaign->messages()->sole()->status);
    }

    public function test_processing_sms_records_the_exchange_and_returns_results(): void
    {
        $this->configureTermii();
        Http::preventStrayRequests();
        Http::fake([
            'api.ng.termii.com/*' => Http::response(['code' => 'ok', 'message_id' => '301', 'message' => 'Successfully Sent'], 200),
        ]);

        $campaign = Campaign::factory()->create(['message' => 'Hello', 'total_recipients' => 1]);
        CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'recipient' => '+2348031234567']);

        $response = $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('results.0.recipient', '+2348031234567')
            ->assertJsonPath('results.0.status', 'sent')
            ->assertJsonPath('results.0.response_status', 200);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.ng.termii.com/api/sms/send'
            && $request['to'] === '2348031234567');

        $this->assertSame('••••redacted', $response->json('results.0.request_body.api_key'));
    }

    public function test_a_sendchamp_broadcast_sends_through_sendchamp(): void
    {
        $this->configureSendchamp();
        Http::preventStrayRequests();
        Http::fake([
            'api.sendchamp.com/*' => Http::response([
                'status' => 'success',
                'code' => 200,
                'message' => 'processing',
                'data' => ['id' => 'MN-SMS-1', 'reference' => 'MN-SMS-1', 'status' => 'processing'],
            ], 200),
        ]);

        $campaign = Campaign::factory()->create([
            'provider' => 'sendchamp',
            'sender_id' => 'Sendchamp',
            'total_recipients' => 1,
        ]);
        CampaignMessage::factory()->create(['campaign_id' => $campaign->id, 'recipient' => '+2348031234567']);

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 1);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.sendchamp.com/api/v1/sms/send'
            && $request->hasHeader('Authorization', 'Bearer test-sendchamp-key')
            && $request['to'] === ['2348031234567']);

        $this->assertSame('MN-SMS-1', $campaign->messages()->sole()->provider_message_id);
    }

    public function test_an_unknown_provider_is_rejected(): void
    {
        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'telegram',
                'mode' => 'list',
                'manual_recipients' => '08031234567',
                'message' => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    public function test_a_broadcast_is_rejected_when_the_selected_provider_is_not_configured(): void
    {
        config(['services.termii.key' => null, 'services.sendchamp.key' => null]);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'sms',
                'provider' => 'termii',
                'mode' => 'list',
                'manual_recipients' => '08031234567',
                'message' => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_the_recent_endpoint_reports_a_recent_broadcast(): void
    {
        $state = State::factory()->create(['name' => 'Lagos']);
        Campaign::factory()->create([
            'channel' => CampaignChannel::Sms->value,
            'state_id' => $state->id,
            'lga_id' => null,
            'scope_label' => 'Lagos',
            'total_recipients' => 12,
        ]);

        $this->operator()
            ->getJson(route('admin.messaging.recent', ['channel' => 'sms', 'state_id' => $state->id]))
            ->assertOk()
            ->assertJsonPath('recent', true)
            ->assertJsonPath('recipients', 12);
    }

    public function test_the_campaign_detail_escapes_the_message(): void
    {
        $campaign = Campaign::factory()->create(['message' => '<script>alert(1)</script>']);

        $this->operator()
            ->get(route('admin.messaging.show', $campaign))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    // ── Email ────────────────────────────────────────────────────────────────

    public function test_an_email_broadcast_targets_records_with_an_email(): void
    {
        VoterRecord::factory()->create(['email' => 'one@example.com']);
        VoterRecord::factory()->create(['email' => 'two@example.com']);
        VoterRecord::factory()->create(['email' => null]);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'email',
                'mode' => 'records',
                'subject' => 'Hello everyone',
                'message' => 'Body text',
            ])
            ->assertOk()
            ->assertJsonPath('total', 2);

        $campaign = Campaign::query()->sole();

        $this->assertSame(CampaignChannel::Email, $campaign->channel);
        $this->assertSame('All records', $campaign->scope_label);
        $this->assertSame('Hello everyone', $campaign->subject);
        $this->assertDatabaseCount('campaign_messages', 2);
        $this->assertDatabaseHas('campaign_messages', ['recipient' => 'one@example.com', 'channel' => 'email']);
    }

    public function test_an_email_broadcast_requires_a_subject(): void
    {
        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'email',
                'mode' => 'list',
                'manual_recipients' => 'person@example.com',
                'message' => 'Body text',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function test_an_email_batch_sends_through_the_mailer_and_records_the_exchange(): void
    {
        Mail::fake();

        $campaign = Campaign::factory()->create([
            'channel' => CampaignChannel::Email->value,
            'provider' => 'array',
            'sender_id' => 'no-reply@example.com',
            'subject' => 'Hello',
            'message' => 'Body',
            'total_recipients' => 1,
        ]);
        CampaignMessage::factory()->create([
            'campaign_id' => $campaign->id,
            'channel' => CampaignChannel::Email->value,
            'recipient' => 'person@example.com',
        ]);

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('results.0.recipient', 'person@example.com')
            ->assertJsonPath('results.0.status', 'sent');

        Mail::assertSent(BroadcastEmail::class, fn (BroadcastEmail $mail): bool => $mail->hasTo('person@example.com') && $mail->subjectLine === 'Hello');

        $message = $campaign->messages()->sole();

        $this->assertSame('sent', $message->status);
        $this->assertSame(config('mail.default'), $message->request_headers['transport']);
        $this->assertSame('person@example.com', $message->request_body['to']);
    }

    public function test_an_email_broadcast_can_pause_after_test_recipients(): void
    {
        Mail::fake();

        VoterRecord::factory()->count(2)->create(['email' => 'one@example.com']);
        VoterRecord::factory()->create(['email' => 'two@example.com']);

        $this->operator()
            ->postJson(route('admin.messaging.store'), [
                'channel' => 'email',
                'mode' => 'records',
                'subject' => 'Hello',
                'message' => 'Body',
                'test_recipients' => 'tester@example.com',
                'pause_after_test' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('total', 4);

        $campaign = Campaign::query()->sole();

        $this->assertTrue($campaign->paused);
        $this->assertSame('tester@example.com', $campaign->messages()->orderBy('id')->value('recipient'));

        $this->operator()
            ->postJson(route('admin.messaging.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 1)
            ->assertJsonPath('paused', true);

        Mail::assertSent(BroadcastEmail::class, 1);
    }
}
