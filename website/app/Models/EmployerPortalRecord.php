<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployerPortalRecord extends Model
{
    protected $fillable = ['company_id', 'created_by', 'section', 'name', 'description', 'payload', 'is_active'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'is_active' => 'boolean'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
