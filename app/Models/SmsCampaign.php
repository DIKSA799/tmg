<?php

namespace App\Models;

use App\Enums\SmsProvider;
use Database\Factories\SmsCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    /** @use HasFactory<SmsCampaignFactory> */
    use HasFactory;

    public const STATUS_PROCESSING = 'processing';

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
        'message',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'provider' => SmsProvider::class,
            'total_recipients' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<SmsMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
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

        $sent = (int) $counts->get(SmsMessage::STATUS_SENT, 0);
        $failed = (int) $counts->get(SmsMessage::STATUS_FAILED, 0);
        $pending = (int) $counts->get(SmsMessage::STATUS_PENDING, 0);

        $status = match (true) {
            $pending > 0 => self::STATUS_PROCESSING,
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
