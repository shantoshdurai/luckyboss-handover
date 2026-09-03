<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CommunicationLog extends Model { protected $fillable=['company_id','sender_id','recipient_id','channel','status','subject','body','sent_at','error']; protected function casts():array{return ['sent_at'=>'datetime'];} public function company(){return $this->belongsTo(Company::class);} public function sender(){return $this->belongsTo(User::class,'sender_id');} public function recipient(){return $this->belongsTo(User::class,'recipient_id');} }