<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApplicationStatusHistory extends Model { protected $fillable=['job_application_id','user_id','from_status','to_status','remark']; }