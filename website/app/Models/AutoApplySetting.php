<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoApplySetting extends Model
{
    protected $table = 'candidate_auto_apply_settings';

    protected $fillable = ['user_id', 'enabled', 'minimum_score', 'daily_limit', 'last_run_at'];

    protected $casts = [
        'enabled' => 'boolean',
        'minimum_score' => 'integer',
        'daily_limit' => 'integer',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The score this candidate's auto-apply must clear.
     *
     * Their own floor if they set one, otherwise the platform's. Never the
     * lower of the two: a candidate who asked for 85% does not get 60% jobs
     * because an administrator lowered the site default.
     */
    public function effectiveMinimumScore(int $platformMinimum): int
    {
        return max($platformMinimum, $this->minimum_score ?? 0);
    }
}
