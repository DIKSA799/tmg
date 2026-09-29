<?php

namespace App\Models;

use Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    /** @use HasFactory<StateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'code',
    ];

    /**
     * @return HasMany<Lga, $this>
     */
    public function lgas(): HasMany
    {
        return $this->hasMany(Lga::class);
    }

    /**
     * @return HasMany<Ward, $this>
     */
    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }

    /**
     * @return HasMany<PollingUnit, $this>
     */
    public function pollingUnits(): HasMany
    {
        return $this->hasMany(PollingUnit::class);
    }
}
