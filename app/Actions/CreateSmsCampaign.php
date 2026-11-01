<?php

namespace App\Actions;

use App\Models\Admin;
use App\Models\Lga;
use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use App\Models\State;
use App\Models\VoterRecord;

class CreateSmsCampaign
{
    private const INSERT_CHUNK = 500;

    /**
     * Create a campaign targeting every voter record in a state (and optionally
     * a single LGA). Recipients are queued as pending messages immediately so
     * progress survives a closed tab.
     */
    public function forLocation(?Admin $admin, State $state, ?Lga $lga, string $message): SmsCampaign
    {
        $query = VoterRecord::query()->inLocation($state->id, $lga?->id);

        $campaign = $this->createCampaign(
            $admin,
            $lga === null ? $state->name : $state->name.' — '.$lga->name,
            $message,
            (clone $query)->count(),
            $state->id,
            $lga?->id,
        );

        $now = now();

        $query->select(['id', 'phone'])->chunkById(self::INSERT_CHUNK, function ($records) use ($campaign, $now): void {
            $rows = $records->map(fn (VoterRecord $record): array => $this->row($campaign, $record->phone, $now, $record->id))->all();

            SmsMessage::query()->insert($rows);
        });

        return $campaign;
    }

    /**
     * Create a campaign that sends only to the given phone numbers.
     *
     * @param  list<string>  $numbers
     */
    public function forNumbers(?Admin $admin, array $numbers, string $message): SmsCampaign
    {
        $campaign = $this->createCampaign($admin, 'Manual numbers', $message, count($numbers));

        $now = now();

        $rows = array_map(fn (string $number): array => $this->row($campaign, $number, $now), $numbers);

        SmsMessage::query()->insert($rows);

        return $campaign;
    }

    private function createCampaign(
        ?Admin $admin,
        string $scopeLabel,
        string $message,
        int $total,
        ?int $stateId = null,
        ?int $lgaId = null,
    ): SmsCampaign {
        return SmsCampaign::query()->create([
            'admin_id' => $admin?->id,
            'state_id' => $stateId,
            'lga_id' => $lgaId,
            'scope_label' => $scopeLabel,
            'channel' => 'sms',
            'sender_id' => (string) config('services.termii.sender_id'),
            'message' => $message,
            'status' => SmsCampaign::STATUS_PROCESSING,
            'total_recipients' => $total,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(SmsCampaign $campaign, string $phone, \DateTimeInterface $now, ?int $voterRecordId = null): array
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
