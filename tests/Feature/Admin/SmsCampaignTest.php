<?php

namespace Tests\Feature\Admin;

use App\Enums\SmsProvider;
use App\Models\Admin;
use App\Models\Lga;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\State;
use App\Models\VoterRecord;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsCampaignTest extends TestCase
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
        $this->get(route('admin.sms'))->assertRedirect(route('admin.login'));
    }

    public function test_the_sms_page_renders_for_an_admin(): void
    {
        $this->operator()
            ->get(route('admin.sms'))
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
            ->getJson(route('admin.sms.count', ['state_id' => $state->id, 'lga_id' => $lga->id]))
            ->assertOk()
            ->assertJsonPath('total', 3);
    }

    public function test_a_location_broadcast_queues_one_pending_message_per_record(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create(['name' => 'Ikeja']);
        VoterRecord::factory()->count(3)->create(['state_id' => $state->id, 'lga_id' => $lga->id]);

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'termii',
                'mode' => 'location',
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'message' => 'Hello Lagos',
            ])
            ->assertOk()
            ->assertJsonPath('total', 3);

        $campaign = SmsCampaign::query()->sole();

        $this->assertSame('Lagos — Ikeja', $campaign->scope_label);
        $this->assertSame('processing', $campaign->status);
        $this->assertSame(3, $campaign->total_recipients);
        $this->assertSame(3, $campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->count());
        $this->assertDatabaseCount('sms_messages', 3);
    }

    public function test_a_location_broadcast_includes_the_optional_test_numbers(): void
    {
        $this->configureTermii();

        $state = State::factory()->create(['name' => 'Lagos']);
        $lga = Lga::factory()->for($state)->create();
        VoterRecord::factory()->create(['state_id' => $state->id, 'lga_id' => $lga->id, 'phone' => '+2348031111111']);
        VoterRecord::factory()->create(['state_id' => $state->id, 'lga_id' => $lga->id, 'phone' => '+2348032222222']);

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'termii',
                'mode' => 'location',
                'state_id' => $state->id,
                'message' => 'Hello Lagos',
                'test_numbers' => "08127234628\n+2348031111111",
            ])
            ->assertOk()
            ->assertJsonPath('total', 3);

        $campaign = SmsCampaign::query()->sole();

        $this->assertSame(3, $campaign->total_recipients);
        $this->assertDatabaseCount('sms_messages', 3);
        $this->assertDatabaseHas('sms_messages', ['phone' => '+2348127234628', 'voter_record_id' => null]);
        $this->assertSame(1, $campaign->messages()->where('phone', '+2348031111111')->count());
    }

    public function test_a_manual_broadcast_normalises_and_deduplicates_numbers(): void
    {
        $this->configureTermii();

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'termii',
                'mode' => 'manual',
                'manual_numbers' => "08031234567\n08059876543\n+2348031234567\nnot-a-number",
                'message' => 'Test message',
            ])
            ->assertOk()
            ->assertJsonPath('total', 2);

        $campaign = SmsCampaign::query()->sole();

        $this->assertSame('Manual numbers', $campaign->scope_label);
        $this->assertNull($campaign->state_id);
        $this->assertSame(2, $campaign->total_recipients);
        $this->assertDatabaseHas('sms_messages', ['phone' => '+2348031234567', 'status' => 'pending']);
        $this->assertDatabaseHas('sms_messages', ['phone' => '+2348059876543', 'status' => 'pending']);
        $this->assertDatabaseCount('sms_messages', 2);
    }

    public function test_a_manual_broadcast_is_rejected_without_a_valid_number(): void
    {
        $this->configureTermii();

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'termii',
                'mode' => 'manual',
                'manual_numbers' => 'hello world',
                'message' => 'Test message',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('manual_numbers');

        $this->assertDatabaseCount('sms_campaigns', 0);
    }

    public function test_a_broadcast_is_rejected_when_the_selected_provider_is_not_configured(): void
    {
        config(['services.termii.key' => null, 'services.sendchamp.key' => null]);

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'termii',
                'mode' => 'manual',
                'manual_numbers' => '08031234567',
                'message' => 'Test message',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');

        $this->assertDatabaseCount('sms_campaigns', 0);
    }

    public function test_processing_a_batch_sends_via_termii_and_records_the_exchange(): void
    {
        $this->configureTermii();
        Http::preventStrayRequests();
        Http::fake([
            'api.ng.termii.com/*' => Http::response([
                'code' => 'ok',
                'message_id' => '3017544054459083819856413',
                'message' => 'Successfully Sent',
                'balance' => 1047.57,
            ], 200, ['Content-Type' => 'application/json', 'X-Termii-Test' => 'yes']),
        ]);

        $campaign = SmsCampaign::factory()->create(['message' => 'Hello', 'total_recipients' => 2]);
        SmsMessage::factory()->create(['sms_campaign_id' => $campaign->id, 'phone' => '+2348031234567']);
        SmsMessage::factory()->create(['sms_campaign_id' => $campaign->id, 'phone' => '+2348059876543']);

        $this->operator()
            ->postJson(route('admin.sms.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 2)
            ->assertJsonPath('failed', 0)
            ->assertJsonPath('remaining', 0)
            ->assertJsonPath('status', 'completed');

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.ng.termii.com/api/sms/send'
            && $request['to'] === '2348031234567'
            && $request['from'] === 'TMG'
            && $request['channel'] === 'generic');

        $message = $campaign->messages()->where('phone', '+2348031234567')->sole();

        $this->assertSame('sent', $message->status);
        $this->assertSame('3017544054459083819856413', $message->provider_message_id);
        $this->assertSame('Successfully Sent', $message->provider_status);
        $this->assertSame(200, $message->response_status);
        $this->assertSame('••••redacted', $message->request_body['api_key']);
        $this->assertSame('2348031234567', $message->request_body['to']);
        $this->assertNotNull($message->sent_at);

        $responseHeaderNames = array_map('strtolower', array_keys($message->response_headers));
        $this->assertContains('x-termii-test', $responseHeaderNames);
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
                'data' => [
                    'id' => 'MN-SMS-ad51fcd1-8257-4fc5-b6df-11cf2ddcdfac',
                    'reference' => 'MN-SMS-ad51fcd1-8257-4fc5-b6df-11cf2ddcdfac',
                    'status' => 'processing',
                ],
            ], 200, ['Content-Type' => 'application/json']),
        ]);

        $campaign = SmsCampaign::factory()->create([
            'provider' => SmsProvider::Sendchamp->value,
            'sender_id' => 'Sendchamp',
            'message' => 'Hello',
            'total_recipients' => 1,
        ]);
        SmsMessage::factory()->create(['sms_campaign_id' => $campaign->id, 'phone' => '+2348031234567']);

        $this->operator()
            ->postJson(route('admin.sms.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 1)
            ->assertJsonPath('remaining', 0);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.sendchamp.com/api/v1/sms/send'
            && $request->hasHeader('Authorization', 'Bearer test-sendchamp-key')
            && $request['to'] === ['2348031234567']
            && $request['sender_name'] === 'Sendchamp'
            && $request['route'] === 'non_dnd');

        $message = $campaign->messages()->sole();

        $this->assertSame('sent', $message->status);
        $this->assertSame('MN-SMS-ad51fcd1-8257-4fc5-b6df-11cf2ddcdfac', $message->provider_message_id);
        $this->assertSame(200, $message->response_status);
        $this->assertSame('Bearer ••••redacted', $message->request_headers['Authorization']);
    }

    public function test_a_rejected_sendchamp_response_is_recorded_as_failed(): void
    {
        $this->configureSendchamp();
        Http::preventStrayRequests();
        Http::fake([
            'api.sendchamp.com/*' => Http::response([
                'status' => 'failed',
                'code' => 400,
                'message' => 'Message required',
            ], 400),
        ]);

        $campaign = SmsCampaign::factory()->create([
            'provider' => SmsProvider::Sendchamp->value,
            'sender_id' => 'Sendchamp',
            'total_recipients' => 1,
        ]);
        SmsMessage::factory()->create(['sms_campaign_id' => $campaign->id, 'phone' => '+2348031234567']);

        $this->operator()->postJson(route('admin.sms.process', $campaign))->assertOk();

        $message = $campaign->messages()->sole();

        $this->assertSame('failed', $message->status);
        $this->assertSame(400, $message->response_status);
        $this->assertStringContainsString('Message required', $message->error);
    }

    public function test_a_broadcast_is_rejected_for_an_unknown_provider(): void
    {
        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'telegram',
                'mode' => 'manual',
                'manual_numbers' => '08031234567',
                'message' => 'Test message',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('provider');
    }

    public function test_the_campaign_records_the_selected_provider_and_sender_id(): void
    {
        $this->configureSendchamp();

        $this->operator()
            ->postJson(route('admin.sms.store'), [
                'provider' => 'sendchamp',
                'mode' => 'manual',
                'manual_numbers' => '08031234567',
                'message' => 'Test message',
            ])
            ->assertOk();

        $campaign = SmsCampaign::query()->sole();

        $this->assertSame(SmsProvider::Sendchamp, $campaign->provider);
        $this->assertSame('Sendchamp', $campaign->sender_id);
    }

    public function test_a_rejected_termii_response_is_recorded_as_failed(): void
    {
        $this->configureTermii();
        Http::preventStrayRequests();
        Http::fake([
            'api.ng.termii.com/*' => Http::response(['message' => 'Invalid API key'], 401),
        ]);

        $campaign = SmsCampaign::factory()->create(['total_recipients' => 1]);
        SmsMessage::factory()->create(['sms_campaign_id' => $campaign->id, 'phone' => '+2348031234567']);

        $this->operator()
            ->postJson(route('admin.sms.process', $campaign))
            ->assertOk()
            ->assertJsonPath('sent', 0)
            ->assertJsonPath('failed', 1)
            ->assertJsonPath('status', 'partial');

        $message = $campaign->messages()->sole();

        $this->assertSame('failed', $message->status);
        $this->assertSame(401, $message->response_status);
        $this->assertStringContainsString('Invalid API key', $message->error);
        $this->assertNull($message->sent_at);
    }

    public function test_the_campaign_detail_shows_the_recorded_request_and_response(): void
    {
        $campaign = SmsCampaign::factory()->create(['message' => 'Hello']);
        SmsMessage::factory()->create([
            'sms_campaign_id' => $campaign->id,
            'phone' => '+2348031234567',
            'status' => SmsMessage::STATUS_SENT,
            'provider_message_id' => 'abc123',
            'request_url' => 'https://api.ng.termii.com/api/sms/send',
            'request_headers' => ['Content-Type' => 'application/json'],
            'request_body' => ['to' => '2348031234567', 'api_key' => '••••redacted'],
            'response_status' => 200,
            'response_headers' => ['Content-Type' => ['application/json']],
            'response_body' => '{"code":"ok"}',
        ]);

        $this->operator()
            ->get(route('admin.sms.show', $campaign))
            ->assertOk()
            ->assertSee('Request headers', false)
            ->assertSee('Response body', false)
            ->assertSee('2348031234567', false)
            ->assertSee('abc123', false);
    }

    public function test_the_campaign_detail_escapes_the_message(): void
    {
        $campaign = SmsCampaign::factory()->create(['message' => '<script>alert(1)</script>']);

        $this->operator()
            ->get(route('admin.sms.show', $campaign))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
