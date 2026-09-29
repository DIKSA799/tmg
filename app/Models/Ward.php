<?php

namespace App\Models;

use Database\Factories\WardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    /** @use HasFactory<WardFactory> */
    use HasFactory;

    protected $fillable = [
        'lga_id',
        'name',
        'slug',
        'code',
    ];

    /**
     * @return BelongsTo<Lga, $this>
     */
    public function lga(): BelongsTo
    {
        return $this->belongsTo(Lga::class);
    }

    /**
     * @return HasMany<PollingUnit, $this>
     */
    public function pollingUnits(): HasMany
    {
        return $this->hasMany(PollingUnit::class);
    }
}
