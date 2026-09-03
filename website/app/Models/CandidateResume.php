<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CandidateResume extends Model { protected $fillable=['candidate_id','file_path','file_name','summary','parsed_data','parse_status']; protected function casts():array{return ['parsed_data'=>'array'];} public function candidate(){return $this->belongsTo(User::class,'candidate_id');} }