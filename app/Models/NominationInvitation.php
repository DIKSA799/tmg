<?php

namespace App\Models;

use Database\Factories\NominationInvitationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NominationInvitation extends Model
{
    /** @use HasFactory<NominationInvitationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'public_id',
        'nominee_id',
        'inviter_id',
        'senatorial_district_id',
        'message',
        'status',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation): void {
            $invitation->public_id ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Ambassador, $this>
     */
    public function nominee(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'nominee_id');
    }

    /**
     * @return BelongsTo<Ambassador, $this>
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'inviter_id');
    }

    /**
     * @return BelongsTo<SenatorialDistrict, $this>
     */
    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    /**
     * @param  Builder<NominationInvitation>  $query
     * @return Builder<NominationInvitation>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
