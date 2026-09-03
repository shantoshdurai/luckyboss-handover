<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobBoost extends Model
{
    protected $fillable = [
        'job_id', 'company_id', 'type', 'starts_at', 'ends_at',
        'amount', 'currency', 'status',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    /**
     * Computed from the dates rather than trusted from `status`, so a boost
     * cannot keep charging or keep claiming to be live because a background job
     * failed to run.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active'
            && $this->starts_at <= now()
            && $this->ends_at >= now();
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
