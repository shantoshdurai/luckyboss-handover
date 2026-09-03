<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Package extends Model { protected $fillable = ['company_type_id','company_grade_id','name','slug','description','validity_days','entitlements','is_active']; protected function casts(): array { return ['entitlements'=>'array','is_active'=>'boolean']; } public function prices() { return $this->hasMany(PackagePrice::class); } }