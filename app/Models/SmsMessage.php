<?php

namespace App\Models;

use App\Services\Sms\SmsResult;
use Database\Factories\SmsMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    /** @use HasFactory<SmsMessageFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'sms_campaign_id',
        'voter_record_id',
        'phone',
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
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_headers' => 'array',
            'response_status' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SmsCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    /**
     * @return BelongsTo<VoterRecord, $this>
     */
    public function voterRecord(): BelongsTo
    {
        return $this->belongsTo(VoterRecord::class);
    }

    /**
     * Store the full Termii exchange for this recipient and mark the outcome.
     */
    public function recordResult(SmsResult $result): void
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
