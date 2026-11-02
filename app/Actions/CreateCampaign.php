<?php

namespace App\Actions;

use App\Enums\CampaignChannel;
use App\Enums\SmsProvider;
use App\Models\Admin;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use App\Models\Lga;
use App\Models\State;
use App\Models\VoterRecord;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;

class CreateCampaign
{
    private const INSERT_CHUNK = 500;

    /**
     * Broadcast to voter records — a state with an optional LGA, or every record
     * when no state is given. Test recipients are added on top and queued first.
     *
     * @param  list<string>  $testRecipients  phones (SMS) or emails (Email)
     */
    public function forRecords(
        ?Admin $admin,
        CampaignChannel $channel,
        string $provider,
        ?State $state,
        ?Lga $lga,
        ?string $subject,
        string $body,
        array $testRecipients = [],
        ?int $skipRecentDays = null,
        bool $pauseAfterTest = false,
    ): Campaign {
        $testRecipients = array_values(array_unique($testRecipients));
        $column = $this->recipientColumn($channel);

        // Recipients already messaged on this channel recently, when asked to skip.
        $messaged = fn (): Builder => CampaignMessage::query()
            ->where('channel', $channel->value)
            ->where('status', CampaignMessage::STATUS_SENT)
            ->when($skipRecentDays !== null, fn (Builder $inner): Builder => $inner->where('created_at', '>=', now()->subDays($skipRecentDays)))
            ->select('recipient');

        $base = VoterRecord::query()
            ->inLocation($state?->id, $lga?->id)
            // Email campaigns only reach records that actually have an address.
            ->when($channel === CampaignChannel::Email, fn (Builder $inner): Builder => $inner->whereNotNull('email')->where('email', '!=', ''))
            // A test recipient that is also a record here must not receive twice.
            ->when($testRecipients !== [], fn (Builder $inner): Builder => $inner->whereNotIn($column, $testRecipients));

        $skipped = $skipRecentDays === null
            ? 0
            : max(0, (clone $base)->count() - (clone $base)->whereNotIn($column, $messaged())->count());

        $query = (clone $base)
            ->when($skipRecentDays !== null, fn (Builder $inner): Builder => $inner->whereNotIn($column, $messaged()));

        $campaign = $this->createCampaign(
            $admin,
            $channel,
            $provider,
            $this->scopeLabel($state, $lga),
            $subject,
            $body,
            (clone $query)->count() + count($testRecipients),
            $state?->id,
            $lga?->id,
            $skipped,
            $pauseAfterTest && $testRecipients !== [],
        );

        $now = now();

        // Queue the test recipients first so their responses are the first thing
        // the operator sees — they are the "must deliver" checks.
        $this->attachRecipients($campaign, $testRecipients, $now);

        $query->select(['id', $column])->chunkById(self::INSERT_CHUNK, function ($records) use ($campaign, $channel, $column, $now): void {
            $rows = $records->map(fn (VoterRecord $record): array => $this->row(
                $campaign,
                $channel,
                (string) $record->{$column},
                $now,
                $record->id,
            ))->all();

            CampaignMessage::query()->insert($rows);
        });

        return $campaign;
    }

    /**
     * Broadcast to a hand-typed list of recipients.
     *
     * @param  list<string>  $recipients
     */
    public function forList(
        ?Admin $admin,
        CampaignChannel $channel,
        string $provider,
        array $recipients,
        ?string $subject,
        string $body,
    ): Campaign {
        $campaign = $this->createCampaign(
            $admin,
            $channel,
            $provider,
            $channel === CampaignChannel::Email ? 'Manual emails' : 'Manual numbers',
            $subject,
            $body,
            count($recipients),
        );

        $this->attachRecipients($campaign, $recipients);

        return $campaign;
    }

    private function recipientColumn(CampaignChannel $channel): string
    {
        return $channel === CampaignChannel::Email ? 'email' : 'phone';
    }

    private function scopeLabel(?State $state, ?Lga $lga): string
    {
        return match (true) {
            $state === null => 'All records',
            $lga === null => $state->name,
            default => $state->name.' — '.$lga->name,
        };
    }

    private function createCampaign(
        ?Admin $admin,
        CampaignChannel $channel,
        string $provider,
        string $scopeLabel,
        ?string $subject,
        string $body,
        int $total,
        ?int $stateId = null,
        ?int $lgaId = null,
        int $skipped = 0,
        bool $paused = false,
    ): Campaign {
        return Campaign::query()->create([
            'provider' => $provider,
            'channel' => $channel,
            'admin_id' => $admin?->id,
            'state_id' => $stateId,
            'lga_id' => $lgaId,
            'scope_label' => $scopeLabel,
            'sender_id' => $this->senderFor($channel, $provider),
            'subject' => $channel->needsSubject() ? $subject : null,
            'message' => $body,
            'status' => $paused ? Campaign::STATUS_PAUSED : Campaign::STATUS_PROCESSING,
            'paused' => $paused,
            'total_recipients' => $total,
            'skipped_count' => $skipped,
        ]);
    }

    private function senderFor(CampaignChannel $channel, string $provider): string
    {
        if ($channel === CampaignChannel::Email) {
            return (string) config('mail.from.address');
        }

        return (string) SmsProvider::tryFrom($provider)?->senderId();
    }

    /**
     * @param  list<string>  $recipients
     */
    private function attachRecipients(Campaign $campaign, array $recipients, ?DateTimeInterface $now = null): void
    {
        if ($recipients === []) {
            return;
        }

        $now ??= now();

        $rows = array_map(
            fn (string $recipient): array => $this->row($campaign, $campaign->channel, $recipient, $now),
            $recipients,
        );

        CampaignMessage::query()->insert($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Campaign $campaign, CampaignChannel $channel, string $recipient, DateTimeInterface $now, ?int $voterRecordId = null): array
    {
        return [
            'campaign_id' => $campaign->id,
            'channel' => $channel->value,
            'voter_record_id' => $voterRecordId,
            'recipient' => $recipient,
            'status' => CampaignMessage::STATUS_PENDING,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
