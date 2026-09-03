<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model { protected $fillable=['payment_id','company_id','user_id','number','type','status','currency_code','amount','tax_amount']; protected function casts():array{return ['amount'=>'decimal:2','tax_amount'=>'decimal:2'];} }