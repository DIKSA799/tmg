<?php

namespace App\Models;

use Database\Factories\AmbassadorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Ambassador extends Authenticatable
{
    /** @use HasFactory<AmbassadorFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'voter_record_id',
        'name',
        'phone',
        'email',
        'password',
        'photo_path',
        'state_id',
        'lga_id',
        'ward_id',
        'polling_unit_id',
        'senatorial_district_id',
        'terms_accepted_at',
        'notice_seen_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'terms_accepted_at' => 'datetime',
            'notice_seen_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Email is a login identity, so it is stored and matched in lowercase.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value === null || trim($value) === ''
                ? null
                : Str::lower(trim($value)),
        );
    }

    /**
     * @return BelongsTo<VoterRecord, $this>
     */
    public function voterRecord(): BelongsTo
    {
        return $this->belongsTo(VoterRecord::class);
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

    /**
     * @return BelongsTo<SenatorialDistrict, $this>
     */
    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }

    /**
     * @return HasOne<Nomination, $this>
     */
    public function nomination(): HasOne
    {
        return $this->hasOne(Nomination::class);
    }

    /**
     * @return HasMany<NominationVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(NominationVote::class, 'voter_id');
    }

    /**
     * @return HasMany<NominationInvitation, $this>
     */
    public function invitationsReceived(): HasMany
    {
        return $this->hasMany(NominationInvitation::class, 'nominee_id');
    }

    /**
     * @return HasMany<NominationInvitation, $this>
     */
    public function invitationsSent(): HasMany
    {
        return $this->hasMany(NominationInvitation::class, 'inviter_id');
    }

    /**
     * @param  Builder<Ambassador>  $query
     * @return Builder<Ambassador>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhere('phone', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%');
        });
    }

    /**
     * @param  Builder<Ambassador>  $query
     * @return Builder<Ambassador>
     */
    public function scopeInDistrict(Builder $query, ?int $districtId): Builder
    {
        return $query->where('senatorial_district_id', $districtId);
    }

    public function isNominated(): bool
    {
        return $this->nomination !== null;
    }

    public function atDistrict(?int $districtId): bool
    {
        return $districtId !== null && $this->senatorial_district_id === $districtId;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'TMG';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path !== null ? asset('storage/'.$this->photo_path) : null;
    }
}
