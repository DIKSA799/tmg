<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateCampaign;
use App\Enums\CampaignChannel;
use App\Enums\SmsProvider;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\Lga;
use App\Models\State;
use App\Models\VoterRecord;
use App\Services\Messaging\ChannelGatewayFactory;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MessagingController extends Controller
{
    private const MODE_RECORDS = 'records';

    private const MODE_LIST = 'list';

    public function index(): View
    {
        return view('admin.messaging', [
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'campaigns' => Campaign::query()
                ->with(['state:id,name', 'lga:id,name', 'admin:id,name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20),
            'channels' => CampaignChannel::cases(),
            'providers' => $this->smsProviders(),
            'mailer' => (string) config('mail.default'),
            'fromAddress' => (string) config('mail.from.address'),
            'emailConfigured' => $this->emailConfigured(),
            'defaultChannel' => CampaignChannel::Sms->value,
            'anyConfigured' => $this->emailConfigured()
                || collect(SmsProvider::cases())->contains(fn (SmsProvider $provider): bool => $provider->configured()),
        ]);
    }

    /**
     * Live recipient count for the selected channel/location, used by the form.
     */
    public function count(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', Rule::enum(CampaignChannel::class)],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', 'exists:lgas,id'],
        ]);

        $channel = $this->channel($validated['channel'] ?? null);

        $query = VoterRecord::query()
            ->inLocation($validated['state_id'] ?? null, $validated['lga_id'] ?? null)
            ->when($channel === CampaignChannel::Email, fn (Builder $inner): Builder => $inner->whereNotNull('email')->where('email', '!=', ''));

        return response()->json(['total' => $query->count()]);
    }

    /**
     * Create a campaign and queue one pending message per recipient. Sending is
     * driven afterwards, batch by batch, through the process endpoint.
     */
    public function store(Request $request, CreateCampaign $action): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['required', Rule::enum(CampaignChannel::class)],
            'provider' => [Rule::requiredIf($request->input('channel') === CampaignChannel::Sms->value), 'nullable', Rule::enum(SmsProvider::class)],
            'mode' => ['required', Rule::in([self::MODE_RECORDS, self::MODE_LIST])],
            'subject' => [Rule::requiredIf($request->input('channel') === CampaignChannel::Email->value), 'nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', Rule::exists('lgas', 'id')->where('state_id', $request->integer('state_id'))],
            'manual_recipients' => [Rule::requiredIf($request->input('mode') === self::MODE_LIST), 'nullable', 'string', 'max:20000'],
            'test_recipients' => ['nullable', 'string', 'max:20000'],
            'skip_recent' => ['boolean'],
            'skip_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_after_test' => ['boolean'],
        ]);

        $channel = $this->channel($validated['channel']);
        $this->ensureConfigured($channel, $validated['provider'] ?? null);

        $provider = $channel === CampaignChannel::Sms
            ? (string) ($validated['provider'] ?? SmsProvider::Termii->value)
            : (string) config('mail.default');

        $subject = $channel->needsSubject() ? ($validated['subject'] ?? null) : null;
        $testRecipients = $this->parseRecipients($channel, (string) ($validated['test_recipients'] ?? ''));
        $skipDays = $request->boolean('skip_recent') ? max(1, (int) ($validated['skip_days'] ?? 30)) : null;
        $admin = $request->user('admin');

        if ($validated['mode'] === self::MODE_RECORDS) {
            $campaign = $action->forRecords(
                $admin,
                $channel,
                $provider,
                isset($validated['state_id']) ? State::query()->find($validated['state_id']) : null,
                isset($validated['lga_id']) ? Lga::query()->find($validated['lga_id']) : null,
                $subject,
                $validated['message'],
                $testRecipients,
                $skipDays,
                $request->boolean('pause_after_test'),
            );
        } else {
            $recipients = $this->parseRecipients($channel, (string) ($validated['manual_recipients'] ?? ''));

            if ($recipients === []) {
                throw ValidationException::withMessages([
                    'manual_recipients' => $channel === CampaignChannel::Email
                        ? 'Enter at least one valid email address.'
                        : 'Enter at least one valid Nigerian phone number.',
                ]);
            }

            $campaign = $action->forList($admin, $channel, $provider, $recipients, $subject, $validated['message']);
        }

        return response()->json([
            'campaign_id' => $campaign->id,
            'total' => $campaign->total_recipients,
        ]);
    }

    /**
     * Send the next batch of pending messages and report progress.
     */
    public function process(Request $request, Campaign $campaign, ChannelGatewayFactory $gateways): JsonResponse
    {
        $this->ensureConfigured($campaign->channel, $campaign->provider);

        // "Continue" lifts a test-review pause so the full list sends.
        if ($request->boolean('resume')) {
            $campaign->update(['paused' => false]);
        }

        set_time_limit(120);

        $messages = $campaign->messages()
            ->where('status', CampaignMessage::STATUS_PENDING)
            // While paused after the test send, only the test recipients (queued
            // with no voter record) go out; the rest waits for the operator.
            ->when($campaign->paused, fn (Builder $query): Builder => $query->whereNull('voter_record_id'))
            ->orderBy('id')
            ->limit(max(1, (int) config("services.{$campaign->provider}.batch_size", 25)))
            ->get();

        $gateway = $gateways->forCampaign($campaign);
        $sender = (string) $campaign->sender_id;

        foreach ($messages as $message) {
            $message->recordResult($gateway->send($message->recipient, $campaign->subject, $campaign->message, $sender));
        }

        $campaign->refreshCounters();

        return response()->json([
            'processed' => $messages->count(),
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'remaining' => $campaign->messages()->where('status', CampaignMessage::STATUS_PENDING)->count(),
            'total' => $campaign->total_recipients,
            'status' => $campaign->status,
            'paused' => $campaign->paused,
            'skipped' => $campaign->skipped_count,
            'results' => $messages->map(fn (CampaignMessage $message): array => [
                'recipient' => $message->recipient,
                'status' => $message->status,
                'provider_message_id' => $message->provider_message_id,
                'provider_status' => $message->provider_status,
                'error' => $message->error,
                'request_url' => $message->request_url,
                'request_headers' => $message->request_headers,
                'request_body' => $message->request_body,
                'response_status' => $message->response_status,
                'response_headers' => $message->response_headers,
                'response_body' => $message->response_body,
                'sent_at' => $message->sent_at?->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Re-queue every failed message so it is sent again, and lift any pause.
     */
    public function retry(Campaign $campaign): JsonResponse
    {
        $this->ensureConfigured($campaign->channel, $campaign->provider);

        $campaign->messages()
            ->where('status', CampaignMessage::STATUS_FAILED)
            ->update([
                'status' => CampaignMessage::STATUS_PENDING,
                'provider_message_id' => null,
                'provider_status' => null,
                'error' => null,
                'request_url' => null,
                'request_headers' => null,
                'request_body' => null,
                'response_status' => null,
                'response_headers' => null,
                'response_body' => null,
                'sent_at' => null,
                'updated_at' => now(),
            ]);

        $campaign->update(['paused' => false]);
        $campaign->refreshCounters();

        return response()->json([
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'remaining' => $campaign->messages()->where('status', CampaignMessage::STATUS_PENDING)->count(),
            'total' => $campaign->total_recipients,
            'status' => $campaign->status,
            'paused' => $campaign->paused,
        ]);
    }

    /**
     * Whether the same channel/location was broadcast to in the last day.
     */
    public function recent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', Rule::enum(CampaignChannel::class)],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', 'exists:lgas,id'],
        ]);

        $channel = $this->channel($validated['channel'] ?? null);

        $campaign = Campaign::query()
            ->where('channel', $channel->value)
            ->when(
                $validated['state_id'] ?? null,
                fn (Builder $query, int|string $state): Builder => $query->where('state_id', $state),
                fn (Builder $query): Builder => $query->whereNull('state_id'),
            )
            ->when(
                $validated['lga_id'] ?? null,
                fn (Builder $query, int|string $lga): Builder => $query->where('lga_id', $lga),
                fn (Builder $query): Builder => $query->whereNull('lga_id'),
            )
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->first();

        return response()->json([
            'recent' => $campaign !== null,
            'sent_at' => $campaign?->created_at?->diffForHumans(),
            'recipients' => $campaign?->total_recipients,
        ]);
    }

    public function show(Campaign $campaign): View
    {
        $campaign->load(['admin:id,name', 'state:id,name', 'lga:id,name']);

        return view('admin.messaging-show', [
            'campaign' => $campaign,
            'messages' => $campaign->messages()
                ->with(['voterRecord:id,full_name,reference'])
                ->orderBy('id')
                ->paginate(50),
            'hasPending' => $campaign->messages()->where('status', CampaignMessage::STATUS_PENDING)->exists(),
        ]);
    }

    /**
     * SMS provider options with their sender id and whether they can send.
     *
     * @return list<array{value: string, label: string, senderId: ?string, configured: bool}>
     */
    private function smsProviders(): array
    {
        return collect(SmsProvider::cases())
            ->map(fn (SmsProvider $provider): array => [
                'value' => $provider->value,
                'label' => $provider->label(),
                'senderId' => $provider->senderId(),
                'configured' => $provider->configured(),
            ])
            ->all();
    }

    private function channel(?string $value): CampaignChannel
    {
        return CampaignChannel::tryFrom((string) $value) ?? CampaignChannel::Sms;
    }

    private function emailConfigured(): bool
    {
        return filled(config('mail.default')) && filled(config('mail.from.address'));
    }

    private function ensureConfigured(CampaignChannel $channel, ?string $provider): void
    {
        if ($channel === CampaignChannel::Email) {
            if (! $this->emailConfigured()) {
                throw ValidationException::withMessages([
                    'subject' => 'Email is not configured. Set MAIL_MAILER and MAIL_FROM_ADDRESS in the environment.',
                ]);
            }

            return;
        }

        $sms = SmsProvider::tryFrom((string) $provider) ?? SmsProvider::Termii;

        if (! $sms->configured()) {
            throw ValidationException::withMessages([
                'provider' => "{$sms->label()} is not configured. Set its API key and sender id in the environment, then clear the config cache.",
            ]);
        }
    }

    /**
     * Normalise a free-text list of recipients for the channel, dropping blanks,
     * duplicates and anything invalid.
     *
     * @return list<string>
     */
    private function parseRecipients(CampaignChannel $channel, string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];

        $values = array_map(
            fn (string $value): ?string => $channel === CampaignChannel::Email
                ? $this->normaliseEmail($value)
                : PhoneNumber::normalize($value),
            $parts,
        );

        return array_values(array_unique(array_filter($values)));
    }

    private function normaliseEmail(string $value): ?string
    {
        $email = mb_strtolower(trim($value));

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
    }
}
