<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SavedJob extends Model { protected $fillable=['candidate_id','job_id']; public function job(){return $this->belongsTo(Job::class);} }