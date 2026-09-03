<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['company_type_id', 'company_grade_id', 'name', 'logo_path', 'registration_number', 'industry', 'email', 'phone', 'website', 'country_code', 'state', 'city', 'address', 'status', 'verified_at'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'company_users')
            ->withPivot('company_role', 'is_active')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function employerNotes()
    {
        return $this->hasMany(EmployerNote::class);
    }

    public function employerDocuments()
    {
        return $this->hasMany(EmployerDocument::class);
    }
}