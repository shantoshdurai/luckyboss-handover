<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateSkill extends Model
{
    protected $fillable = ['candidate_id', 'name', 'level'];

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
