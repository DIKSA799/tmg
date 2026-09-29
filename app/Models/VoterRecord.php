<?php

namespace App\Models;

use Database\Factories\VoterRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VoterRecord extends Model
{
    /** @use HasFactory<VoterRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'idempotency_key',
        'full_name',
        'gender',
        'age_band',
        'phone',
        'state_id',
        'lga_id',
        'ward_id',
        'polling_unit_id',
        'registered_voter_status',
        'pvc_status',
        'preferred_language',
        'preferred_language_other',
        'preferred_channel',
        'preferred_channel_other',
        'consent_to_contact',
        'consent_to_data',
        'agent_id',
        'ip_address',
        'latitude',
        'longitude',
        'user_agent',
        'device',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_to_contact' => 'boolean',
            'consent_to_data' => 'boolean',
            'device' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'captured_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->public_id ??= (string) Str::uuid();
            $record->captured_at ??= now();
        });
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
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * @return BelongsTo<PollingUnit, $this>
     */
    public function pollingUnit(): BelongsTo
    {
        return $this->belongsTo(PollingUnit::class);
    }
}
