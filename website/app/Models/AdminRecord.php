<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AdminRecord extends Model { protected $fillable=['module','name','slug','description','payload','is_active']; protected function casts():array{return ['payload'=>'array','is_active'=>'boolean'];} }