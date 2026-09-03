<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $fillable = ['company_id', 'job_category_id', 'title', 'image_path', 'description', 'country_code', 'location', 'work_mode', 'job_type', 'experience_min', 'experience_max', 'salary_min', 'salary_max', 'currency_code', 'salary_visible', 'vacancies', 'closing_date', 'status', 'is_featured', 'is_urgent', 'is_sponsored', 'is_external', 'is_paid_apply', 'application_fee', 'published_at', 'archived_at'];

    protected function casts(): array
    {
        return ['closing_date' => 'date', 'published_at' => 'datetime', 'archived_at' => 'datetime', 'salary_visible' => 'boolean', 'is_featured' => 'boolean', 'is_urgent' => 'boolean', 'is_sponsored' => 'boolean', 'is_external' => 'boolean', 'is_paid_apply' => 'boolean', 'salary_min' => 'decimal:2', 'salary_max' => 'decimal:2', 'application_fee' => 'decimal:2'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }
}