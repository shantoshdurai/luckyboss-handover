<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PackagePrice extends Model { protected $fillable=['package_id','currency_code','amount','tax_rate']; protected function casts():array{return ['amount'=>'decimal:2','tax_rate'=>'decimal:2'];} }