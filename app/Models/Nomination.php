<?php

namespace App\Models;

use Database\Factories\NominationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Nomination extends Model
{
    /** @use HasFactory<NominationFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_WITHDRAWN = 'withdrawn';

    protected $fillable = [
        'public_id',
        'ambassador_id',
        'state_id',
        'senatorial_district_id',
        'nominated_by',
        'statement',
        'status',
        'votes_count',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'votes_count' => 'integer',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $nomination): void {
            $nomination->public_id ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<Ambassador, $this>
     */
    public function ambassador(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class);
    }

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsTo<SenatorialDistrict, $this>
     */
    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    /**
     * @return BelongsTo<Ambassador, $this>
     */
    public function nominator(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'nominated_by');
    }

    /**
     * @return HasMany<NominationVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(NominationVote::class);
    }

    /**
     * @param  Builder<Nomination>  $query
     * @return Builder<Nomination>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<Nomination>  $query
     * @return Builder<Nomination>
     */
    public function scopeForDistrict(Builder $query, ?int $districtId): Builder
    {
        // An ambassador without an assigned district has no zone to show.
        // Returning no rows keeps the page alive while the zone is repaired.
        if ($districtId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('senatorial_district_id', $districtId);
    }

    /**
     * @param  Builder<Nomination>  $query
     * @return Builder<Nomination>
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->orderByDesc('votes_count')->orderBy('created_at')->orderBy('id');
    }
}
