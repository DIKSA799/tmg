<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateSmsCampaign;
use App\Enums\SmsProvider;
use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\State;
use App\Models\VoterRecord;
use App\Services\Sms\SmsGatewayFactory;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SmsController extends Controller
{
    private const MODE_LOCATION = 'location';

    private const MODE_MANUAL = 'manual';

    public function index(): View
    {
        return view('admin.sms', [
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'campaigns' => SmsCampaign::query()
                ->with(['state:id,name', 'lga:id,name', 'admin:id,name'])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(20),
            'providers' => $this->providers(),
            'defaultProvider' => collect(SmsProvider::cases())->first(fn (SmsProvider $provider): bool => $provider->configured())?->value ?? SmsProvider::Termii->value,
            'anyConfigured' => collect(SmsProvider::cases())->contains(fn (SmsProvider $provider): bool => $provider->configured()),
        ]);
    }

    /**
     * Live recipient count for the selected state/LGA, used by the compose form.
     */
    public function count(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', 'exists:lgas,id'],
        ]);

        return response()->json([
            'total' => VoterRecord::query()
                ->inLocation($validated['state_id'] ?? null, $validated['lga_id'] ?? null)
                ->count(),
        ]);
    }

    /**
     * Create a campaign and queue one pending message per recipient. Sending is
     * driven afterwards, batch by batch, through the process endpoint.
     */
    public function store(Request $request, CreateSmsCampaign $action): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', Rule::enum(SmsProvider::class)],
            'mode' => ['required', Rule::in([self::MODE_LOCATION, self::MODE_MANUAL])],
            'message' => ['required', 'string', 'max:1000'],
            'state_id' => [Rule::requiredIf($request->input('mode') === self::MODE_LOCATION), 'nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', Rule::exists('lgas', 'id')->where('state_id', $request->integer('state_id'))],
            'manual_numbers' => [Rule::requiredIf($request->input('mode') === self::MODE_MANUAL), 'nullable', 'string', 'max:20000'],
            'test_numbers' => ['nullable', 'string', 'max:20000'],
            'skip_recent' => ['boolean'],
            'skip_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'pause_after_test' => ['boolean'],
        ]);

        $provider = SmsProvider::from($validated['provider']);
        $this->ensureConfigured($provider);

        $admin = $request->user('admin');

        if ($validated['mode'] === self::MODE_LOCATION) {
            $campaign = $action->forLocation(
                $admin,
                $provider,
                State::query()->findOrFail($validated['state_id']),
                isset($validated['lga_id']) ? Lga::query()->find($validated['lga_id']) : null,
                $validated['message'],
                $this->parseManualNumbers((string) ($validated['test_numbers'] ?? '')),
                $request->boolean('skip_recent') ? max(1, (int) ($validated['skip_days'] ?? 30)) : null,
                $request->boolean('pause_after_test'),
            );
        } else {
            $numbers = $this->parseManualNumbers((string) ($validated['manual_numbers'] ?? ''));

            if ($numbers === []) {
                throw ValidationException::withMessages([
                    'manual_numbers' => 'Enter at least one valid Nigerian phone number.',
                ]);
            }

            $campaign = $action->forNumbers($admin, $provider, $numbers, $validated['message']);
        }

        return response()->json([
            'campaign_id' => $campaign->id,
            'total' => $campaign->total_recipients,
        ]);
    }

    /**
     * Send the next batch of pending messages and report progress.
     */
    public function process(Request $request, SmsCampaign $campaign, SmsGatewayFactory $gateways): JsonResponse
    {
        $this->ensureConfigured($campaign->provider);

        // "Continue" lifts a test-review pause so the full list sends.
        if ($request->boolean('resume')) {
            $campaign->update(['paused' => false]);
        }

        set_time_limit(120);

        $messages = $campaign->messages()
            ->where('status', SmsMessage::STATUS_PENDING)
            // While paused after the test send, only the test numbers (queued with
            // no voter record) go out; the rest waits for the operator.
            ->when($campaign->paused, fn (Builder $query): Builder => $query->whereNull('voter_record_id'))
            ->orderBy('id')
            ->limit(max(1, (int) config("services.{$campaign->provider->value}.batch_size", 25)))
            ->get();

        $gateway = $gateways->make($campaign->provider);
        $senderId = (string) $campaign->sender_id;

        foreach ($messages as $message) {
            $message->recordResult($gateway->send($message->phone, $campaign->message, $senderId));
        }

        $campaign->refreshCounters();

        return response()->json([
            'processed' => $messages->count(),
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'remaining' => $campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->count(),
            'total' => $campaign->total_recipients,
            'status' => $campaign->status,
            'paused' => $campaign->paused,
            'skipped' => $campaign->skipped_count,
            'results' => $messages->map(fn (SmsMessage $message): array => [
                'phone' => $message->phone,
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
    public function retry(SmsCampaign $campaign): JsonResponse
    {
        $this->ensureConfigured($campaign->provider);

        $campaign->messages()
            ->where('status', SmsMessage::STATUS_FAILED)
            ->update([
                'status' => SmsMessage::STATUS_PENDING,
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
            'remaining' => $campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->count(),
            'total' => $campaign->total_recipients,
            'status' => $campaign->status,
            'paused' => $campaign->paused,
        ]);
    }

    /**
     * Whether the same location was broadcast to in the last day, so the compose
     * form can warn before sending a likely duplicate.
     */
    public function recent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state_id' => ['required', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', 'exists:lgas,id'],
        ]);

        $campaign = SmsCampaign::query()
            ->where('state_id', $validated['state_id'])
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

    public function show(SmsCampaign $campaign): View
    {
        $campaign->load(['admin:id,name', 'state:id,name', 'lga:id,name']);

        return view('admin.sms-show', [
            'campaign' => $campaign,
            'messages' => $campaign->messages()
                ->with(['voterRecord:id,full_name,reference'])
                ->orderBy('id')
                ->paginate(50),
            'hasPending' => $campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->exists(),
        ]);
    }

    /**
     * Provider options with their sender id and whether they can currently send.
     *
     * @return list<array{value: string, label: string, senderId: ?string, configured: bool}>
     */
    private function providers(): array
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

    private function ensureConfigured(SmsProvider $provider): void
    {
        if (! $provider->configured()) {
            throw ValidationException::withMessages([
                'provider' => "{$provider->label()} is not configured. Set its API key and sender id in the environment, then clear the config cache.",
            ]);
        }
    }

    /**
     * Normalise a free-text list of numbers, dropping blanks, duplicates and
     * anything that is not a valid Nigerian mobile number.
     *
     * @return list<string>
     */
    private function parseManualNumbers(string $raw): array
    {
        return collect(preg_split('/[\s,;]+/', $raw) ?: [])
            ->map(fn (string $value): ?string => PhoneNumber::normalize($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
