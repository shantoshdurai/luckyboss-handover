<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ExternalSource extends Model { protected $fillable=['name','source_type','feed_type','status','contacts_visible','import_limit','description']; protected function casts():array{return ['contacts_visible'=>'boolean'];} }