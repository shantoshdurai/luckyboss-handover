<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateProfile extends Model
{
    protected $fillable = ['user_id', 'profile_photo_path', 'country_code', 'date_of_birth', 'gender', 'current_title', 'professional_summary', 'current_location', 'preferred_location', 'years_experience', 'current_salary', 'expected_salary', 'preferred_currency', 'notice_period', 'availability', 'profile_completion', 'is_visible', 'resume_data', 'headline', 'department', 'preferred_category', 'skills', 'projects', 'languages', 'work_modes', 'job_types', 'resume_file_name', 'qualification', 'course', 'passing_year', 'is_student', 'open_to_relocate', 'has_work_permit'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_visible' => 'boolean',
            'resume_data' => 'array',
            'skills' => 'array',
            'projects' => 'array',
            'languages' => 'array',
            'work_modes' => 'array',
            'job_types' => 'array',
            'is_student' => 'boolean',
            // Nullable booleans: null means unanswered, which is not false.
            'open_to_relocate' => 'boolean',
            'has_work_permit' => 'boolean',
        ];
    }
}