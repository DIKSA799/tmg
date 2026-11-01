<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateSmsCampaign;
use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\State;
use App\Models\VoterRecord;
use App\Services\Sms\TermiiClient;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
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
            'senderId' => (string) config('services.termii.sender_id'),
            'configured' => $this->configured(),
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
        $this->ensureConfigured();

        $validated = $request->validate([
            'mode' => ['required', Rule::in([self::MODE_LOCATION, self::MODE_MANUAL])],
            'message' => ['required', 'string', 'max:1000'],
            'state_id' => [Rule::requiredIf($request->input('mode') === self::MODE_LOCATION), 'nullable', 'integer', 'exists:states,id'],
            'lga_id' => ['nullable', 'integer', Rule::exists('lgas', 'id')->where('state_id', $request->integer('state_id'))],
            'manual_numbers' => [Rule::requiredIf($request->input('mode') === self::MODE_MANUAL), 'nullable', 'string', 'max:20000'],
        ]);

        $admin = $request->user('admin');

        if ($validated['mode'] === self::MODE_LOCATION) {
            $campaign = $action->forLocation(
                $admin,
                State::query()->findOrFail($validated['state_id']),
                isset($validated['lga_id']) ? Lga::query()->find($validated['lga_id']) : null,
                $validated['message'],
            );
        } else {
            $numbers = $this->parseManualNumbers((string) ($validated['manual_numbers'] ?? ''));

            if ($numbers === []) {
                throw ValidationException::withMessages([
                    'manual_numbers' => 'Enter at least one valid Nigerian phone number.',
                ]);
            }

            $campaign = $action->forNumbers($admin, $numbers, $validated['message']);
        }

        return response()->json([
            'campaign_id' => $campaign->id,
            'total' => $campaign->total_recipients,
        ]);
    }

    /**
     * Send the next batch of pending messages and report progress.
     */
    public function process(SmsCampaign $campaign, TermiiClient $client): JsonResponse
    {
        $this->ensureConfigured();
        set_time_limit(120);

        $messages = $campaign->messages()
            ->where('status', SmsMessage::STATUS_PENDING)
            ->orderBy('id')
            ->limit(max(1, (int) config('services.termii.batch_size', 25)))
            ->get();

        foreach ($messages as $message) {
            $message->recordResult($client->send($message->phone, $campaign->message));
        }

        $campaign->refreshCounters();

        return response()->json([
            'processed' => $messages->count(),
            'sent' => $campaign->sent_count,
            'failed' => $campaign->failed_count,
            'remaining' => $campaign->messages()->where('status', SmsMessage::STATUS_PENDING)->count(),
            'total' => $campaign->total_recipients,
            'status' => $campaign->status,
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

    private function configured(): bool
    {
        return (bool) config('services.termii.enabled')
            && filled(config('services.termii.key'))
            && filled(config('services.termii.sender_id'));
    }

    private function ensureConfigured(): void
    {
        if (! $this->configured()) {
            throw ValidationException::withMessages([
                'termii' => 'Termii is not configured. Set TERMII_API_KEY and TERMII_SENDER_ID in the environment.',
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
