<?php

namespace App\Actions;

use App\Enums\SmsProvider;
use App\Models\Admin;
use App\Models\Lga;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\State;
use App\Models\VoterRecord;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

class CreateSmsCampaign
{
    private const INSERT_CHUNK = 500;

    /**
     * Create a campaign targeting every voter record in a state (and optionally
     * a single LGA), with any extra test numbers added on top so the sender can
     * confirm delivery. Recipients are queued as pending messages immediately so
     * progress survives a closed tab.
     *
     * @param  list<string>  $extraNumbers
     */
    public function forLocation(
        ?Admin $admin,
        SmsProvider $provider,
        State $state,
        ?Lga $lga,
        string $message,
        array $extraNumbers = [],
    ): SmsCampaign {
        $extraNumbers = array_values(array_unique($extraNumbers));

        $query = VoterRecord::query()
            ->inLocation($state->id, $lga?->id)
            // A test number that is also a record here must not receive two messages.
            ->when($extraNumbers !== [], fn (Builder $inner): Builder => $inner->whereNotIn('phone', $extraNumbers));

        $campaign = $this->createCampaign(
            $admin,
            $provider,
            $lga === null ? $state->name : $state->name.' — '.$lga->name,
            $message,
            (clone $query)->count() + count($extraNumbers),
            $state->id,
            $lga?->id,
        );

        $now = now();

        $query->select(['id', 'phone'])->chunkById(self::INSERT_CHUNK, function ($records) use ($campaign, $now): void {
            $rows = $records->map(fn (VoterRecord $record): array => $this->row($campaign, $record->phone, $now, $record->id))->all();

            SmsMessage::query()->insert($rows);
        });

        $this->attachNumbers($campaign, $extraNumbers, $now);

        return $campaign;
    }

    /**
     * Create a campaign that sends only to the given phone numbers.
     *
     * @param  list<string>  $numbers
     */
    public function forNumbers(?Admin $admin, SmsProvider $provider, array $numbers, string $message): SmsCampaign
    {
        $campaign = $this->createCampaign($admin, $provider, 'Manual numbers', $message, count($numbers));

        $this->attachNumbers($campaign, $numbers);

        return $campaign;
    }

    private function createCampaign(
        ?Admin $admin,
        SmsProvider $provider,
        string $scopeLabel,
        string $message,
        int $total,
        ?int $stateId = null,
        ?int $lgaId = null,
    ): SmsCampaign {
        return SmsCampaign::query()->create([
            'provider' => $provider,
            'admin_id' => $admin?->id,
            'state_id' => $stateId,
            'lga_id' => $lgaId,
            'scope_label' => $scopeLabel,
            'channel' => 'sms',
            'sender_id' => $provider->senderId(),
            'message' => $message,
            'status' => SmsCampaign::STATUS_PROCESSING,
            'total_recipients' => $total,
        ]);
    }

    /**
     * @param  list<string>  $numbers
     */
    private function attachNumbers(SmsCampaign $campaign, array $numbers, ?DateTimeInterface $now = null): void
    {
        if ($numbers === []) {
            return;
        }

        $now ??= now();

        $rows = array_map(fn (string $number): array => $this->row($campaign, $number, $now), $numbers);

        SmsMessage::query()->insert($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(SmsCampaign $campaign, string $phone, DateTimeInterface $now, ?int $voterRecordId = null): array
    {
        return [
            'sms_campaign_id' => $campaign->id,
            'voter_record_id' => $voterRecordId,
            'phone' => $phone,
            'status' => SmsMessage::STATUS_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
