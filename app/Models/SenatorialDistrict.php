<?php

namespace App\Models;

use Database\Factories\SenatorialDistrictFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SenatorialDistrict extends Model
{
    /** @use HasFactory<SenatorialDistrictFactory> */
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'slug',
    ];

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return HasMany<Lga, $this>
     */
    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    /**
     * @return HasMany<Ambassador, $this>
     */
    public function ambassadors(): HasMany
    {
        return $this->hasMany(Ambassador::class);
    }

    /**
     * @return HasMany<Nomination, $this>
     */
    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    public function label(): string
    {
        return $this->name;
    }
}
