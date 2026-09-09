<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplyRun extends Model
{
    protected $fillable = ['user_id', 'ran_at', 'considered', 'applied', 'outcome', 'note'];

    protected $casts = [
        'ran_at' => 'datetime',
        'considered' => 'integer',
        'applied' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What the candidate is told about this run.
     *
     * Every branch says something true about why nothing happened. "We are
     * still looking" with no reason is how a broken feature hides for a month.
     */
    public function summary(): string
    {
        return match ($this->outcome) {
            'applied' => $this->applied === 1
                ? 'Applied to 1 job for you.'
                : "Applied to {$this->applied} jobs for you.",
            'no_matches' => 'No new jobs cleared your match score. Nothing was sent.',
            'not_ready' => 'We need more of your profile before we can match you. Nothing was sent.',
            'limit_reached' => 'You had already reached your daily limit. Nothing was sent.',
            'no_credits' => 'Your auto-apply allowance is used up. Nothing was sent.',
            'disabled' => 'Auto-apply was switched off. Nothing was sent.',
            default => 'Nothing was sent.',
        };
    }
}
