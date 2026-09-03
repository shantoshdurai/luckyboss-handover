<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateNote extends Model
{
    protected $fillable = ['company_id', 'job_application_id', 'user_id', 'note'];

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
