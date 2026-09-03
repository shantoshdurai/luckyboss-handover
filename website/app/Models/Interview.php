<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Interview extends Model { protected $fillable = ['job_application_id','company_id','interviewer_id','mode','scheduled_at','duration_minutes','time_zone','venue','meeting_link','notes','status']; protected function casts(): array { return ['scheduled_at'=>'datetime']; } public function application(){return $this->belongsTo(JobApplication::class,'job_application_id');} public function company(){return $this->belongsTo(Company::class);} public function interviewer(){return $this->belongsTo(User::class,'interviewer_id');} }