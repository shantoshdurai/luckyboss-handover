<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model { protected $fillable=['user_id','company_id','subscription_id','job_id','reference','purpose','gateway','status','currency_code','amount','gateway_payload','paid_at']; protected function casts():array{return ['amount'=>'decimal:2','gateway_payload'=>'array','paid_at'=>'datetime'];} public function company(){return $this->belongsTo(Company::class);} public function user(){return $this->belongsTo(User::class);} }