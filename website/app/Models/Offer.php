<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Offer extends Model { protected $fillable = ['job_application_id','company_id','position','salary','currency_code','joining_date','work_location','terms','status','sent_at','responded_at']; protected function casts(): array { return ['joining_date'=>'date','sent_at'=>'datetime','responded_at'=>'datetime','salary'=>'decimal:2']; } public function application() { return $this->belongsTo(JobApplication::class, 'job_application_id'); } }