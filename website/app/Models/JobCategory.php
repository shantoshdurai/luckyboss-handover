<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'icon', 'icon_image_path', 'sort_order', 'show_on_home', 'is_active'];

    protected function casts(): array
    {
        return ['show_on_home' => 'boolean', 'is_active' => 'boolean'];
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}