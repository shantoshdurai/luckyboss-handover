<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class JobAlert extends Model { protected $fillable=['candidate_id','name','keyword','job_category_id','country_code','location','salary_min','experience_min','frequency','channels','is_active']; protected function casts():array{return ['channels'=>'array','is_active'=>'boolean','salary_min'=>'decimal:2'];} }