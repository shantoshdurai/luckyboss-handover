<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'country_code',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_users')
            ->withPivot('company_role', 'is_active')
            ->withTimestamps();
    }

    public function candidateProfile()
    {
        return $this->hasOne(CandidateProfile::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'candidate_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function savedJobs()
    {
        return $this->hasMany(SavedJob::class, 'candidate_id');
    }

    public function jobAlerts()
    {
        return $this->hasMany(JobAlert::class, 'candidate_id');
    }

    public function resumes()
    {
        return $this->hasMany(CandidateResume::class, 'candidate_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasRole('super-admin') || $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }
}
