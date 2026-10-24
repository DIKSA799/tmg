<?php

namespace App\Models;

use Database\Factories\NominationVoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominationVote extends Model
{
    /** @use HasFactory<NominationVoteFactory> */
    use HasFactory;

    protected $fillable = [
        'nomination_id',
        'voter_id',
        'senatorial_district_id',
    ];

    /**
     * @return BelongsTo<Nomination, $this>
     */
    public function nomination(): BelongsTo
    {
        return $this->belongsTo(Nomination::class);
    }

    /**
     * @return BelongsTo<Ambassador, $this>
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(Ambassador::class, 'voter_id');
    }

    /**
     * @return BelongsTo<SenatorialDistrict, $this>
     */
    public function senatorialDistrict(): BelongsTo
    {
        return $this->belongsTo(SenatorialDistrict::class);
    }
}
