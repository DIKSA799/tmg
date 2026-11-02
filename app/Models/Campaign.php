<?php

namespace App\Models;

use App\Enums\CampaignChannel;
use App\Enums\SmsProvider;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'provider',
        'admin_id',
        'state_id',
        'lga_id',
        'scope_label',
        'channel',
        'sender_id',
        'subject',
        'message',
        'status',
        'paused',
        'total_recipients',
        'sent_count',
        'failed_count',
        'skipped_count',
    ];

    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'paused' => 'boolean',
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'skipped_count' => 'integer',
        ];
    }

    /**
     * The SMS provider enum when this campaign went out over SMS, otherwise null.
     */
    public function smsProvider(): ?SmsProvider
    {
        return $this->channel === CampaignChannel::Sms
            ? SmsProvider::tryFrom((string) $this->provider)
            : null;
    }

    /**
     * @return HasMany<CampaignMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(CampaignMessage::class);
    }

    /**
     * @return BelongsTo<Admin, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsTo<Lga, $this>
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    /**
     * Recompute the delivery counts and the overall status from the messages.
     */
    public function refreshCounters(): void
    {
        $counts = $this->messages()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $sent = (int) $counts->get(CampaignMessage::STATUS_SENT, 0);
        $failed = (int) $counts->get(CampaignMessage::STATUS_FAILED, 0);
        $pending = (int) $counts->get(CampaignMessage::STATUS_PENDING, 0);

        $status = match (true) {
            $pending > 0 => $this->paused ? self::STATUS_PAUSED : self::STATUS_PROCESSING,
            $failed > 0 => self::STATUS_PARTIAL,
            default => self::STATUS_COMPLETED,
        };

        $this->forceFill([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $status,
        ])->save();
    }
}
