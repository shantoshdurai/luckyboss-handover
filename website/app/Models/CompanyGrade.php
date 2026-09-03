<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyGrade extends Model
{
    protected $fillable = ['name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}