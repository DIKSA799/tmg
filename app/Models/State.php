<?php

namespace App\Models;

use Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
     * @return HasManyThrough<Ward, Lga, $this>
     */
    public function wards(): HasManyThrough
    {
        return $this->hasManyThrough(Ward::class, Lga::class);
    }
}
