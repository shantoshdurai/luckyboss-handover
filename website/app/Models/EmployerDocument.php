<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerDocument extends Model
{
    protected $fillable = ['company_id', 'name', 'file_path', 'status', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
