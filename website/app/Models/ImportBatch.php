<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ImportBatch extends Model { protected $fillable=['external_source_id','user_id','data_type','status','records_received','records_imported','records_failed','error_log']; public function externalSource(){return $this->belongsTo(ExternalSource::class);} }