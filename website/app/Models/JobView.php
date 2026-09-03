<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobView extends Model
{
    protected $fillable = ['job_id', 'viewer_id', 'source', 'dedupe_hash', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'datetime'];
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
