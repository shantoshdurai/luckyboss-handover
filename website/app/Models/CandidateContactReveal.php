<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One employer, one candidate, one moment their contact details were shown.
 *
 * Unique per company and candidate, so revealing the same person twice is a
 * lookup rather than a second charge.
 */
class CandidateContactReveal extends Model
{
    protected $fillable = ['company_id', 'candidate_id', 'revealed_by', 'was_charged', 'reason'];

    protected function casts(): array
    {
        return ['was_charged' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
