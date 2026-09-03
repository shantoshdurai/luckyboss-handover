<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Subscription extends Model { protected $fillable = ['company_id','package_id','status','starts_at','expires_at','entitlements','currency_code','amount']; protected function casts(): array { return ['starts_at'=>'date','expires_at'=>'date','entitlements'=>'array']; } public function package() { return $this->belongsTo(Package::class); } public function company() { return $this->belongsTo(Company::class); } }