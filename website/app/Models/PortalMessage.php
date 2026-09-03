<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PortalMessage extends Model { protected $table='messages'; protected $fillable=['sender_id','recipient_id','company_id','job_application_id','type','subject','body','read_at']; protected function casts():array{return ['read_at'=>'datetime'];} }