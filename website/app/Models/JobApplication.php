<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = ['job_id', 'candidate_id', 'assigned_to', 'source', 'status', 'match_score', 'applied_at', 'last_activity_at'];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime', 'last_activity_at' => 'datetime', 'match_score' => 'decimal:2'];
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }
}