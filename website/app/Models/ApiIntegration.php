<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApiIntegration extends Model { protected $fillable=['key','name','provider','encrypted_secret','encrypted_webhook_secret','webhook_secret_hint','environment','is_enabled','monthly_limit','usage_count','last_requested_at','last_error']; protected function casts():array{return ['is_enabled'=>'boolean','last_requested_at'=>'datetime'];} }