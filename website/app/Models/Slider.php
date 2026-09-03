<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Slider extends Model { protected $fillable=['title','subtitle','image_path','cta_text','cta_url','sort_order','starts_at','ends_at','web_enabled','app_enabled','is_active']; protected function casts():array{return ['starts_at'=>'date','ends_at'=>'date','web_enabled'=>'boolean','app_enabled'=>'boolean','is_active'=>'boolean'];} }