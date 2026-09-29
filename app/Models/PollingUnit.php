<?php

namespace App\Models;

use Database\Factories\PollingUnitFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollingUnit extends Model
{
    /** @use HasFactory<PollingUnitFactory> */
    use HasFactory;

    protected $fillable = [
        'ward_id',
        'name',
        'code',
        'pu_code',
    ];

    /**
     * @return BelongsTo<Ward, $this>
     */
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    /**
     * @param  Builder<PollingUnit>  $query
     * @return Builder<PollingUnit>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', $term.'%')
                ->orWhere('name', 'like', '%'.$term.'%')
                ->orWhere('code', 'like', $term.'%')
                ->orWhere('pu_code', 'like', $term.'%');
        });
    }
}
