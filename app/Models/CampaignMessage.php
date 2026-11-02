<?php

namespace App\Models;

use App\Enums\CampaignChannel;
use App\Services\Messaging\DispatchResult;
use Database\Factories\CampaignMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMessage extends Model
{
    /** @use HasFactory<CampaignMessageFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'channel',
        'voter_record_id',
        'recipient',
        'status',
        'provider_message_id',
        'provider_status',
        'error',
        'request_url',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_headers' => 'array',
            'response_status' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Campaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * @return BelongsTo<VoterRecord, $this>
     */
    public function voterRecord(): BelongsTo
    {
        return $this->belongsTo(VoterRecord::class);
    }

    /**
     * Store the full exchange for this recipient and mark the outcome.
     */
    public function recordResult(DispatchResult $result): void
    {
        $this->fill([
            'status' => $result->ok ? self::STATUS_SENT : self::STATUS_FAILED,
            'provider_message_id' => $result->providerMessageId,
            'provider_status' => $result->providerStatus,
            'error' => $result->error,
            'request_url' => $result->requestUrl,
            'request_headers' => $result->requestHeaders,
            'request_body' => $result->requestBody,
            'response_status' => $result->responseStatus,
            'response_headers' => $result->responseHeaders,
            'response_body' => $result->responseBody,
            'sent_at' => $result->ok ? now() : null,
        ])->save();
    }
}
