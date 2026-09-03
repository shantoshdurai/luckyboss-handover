<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The remaining profile fields the mobile app collects.
 *
 * Until now the app gathered headline, department, projects, languages and the
 * job preferences, then kept them in memory only — so a candidate who filled in
 * their profile, closed the app and came back was asked everything again. These
 * columns are what make the answers survive.
 *
 * The list-valued fields are JSON rather than pivot tables: they are read and
 * written whole, never queried by element, and Laravel maps `json()` to native
 * JSON on MySQL and TEXT on SQLite with no code change either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->string('headline', 180)->nullable()->after('current_title');
            $table->string('department', 120)->nullable()->after('headline');
            $table->string('preferred_category', 120)->nullable()->after('department');

            $table->json('skills')->nullable();
            $table->json('projects')->nullable();
            $table->json('languages')->nullable();
            $table->json('work_modes')->nullable();
            $table->json('job_types')->nullable();

            $table->string('resume_file_name', 255)->nullable();
            $table->string('qualification', 80)->nullable();
            $table->string('course', 120)->nullable();
            $table->string('passing_year', 8)->nullable();
            $table->boolean('is_student')->default(false);

            // Nullable on purpose: "not answered" is distinct from "no", and
            // storing an unanswered question as false would quietly exclude
            // candidates from jobs they qualify for.
            $table->boolean('open_to_relocate')->nullable();
            $table->boolean('has_work_permit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'headline', 'department', 'preferred_category',
                'skills', 'projects', 'languages', 'work_modes', 'job_types',
                'resume_file_name', 'qualification', 'course', 'passing_year',
                'is_student', 'open_to_relocate', 'has_work_permit',
            ]);
        });
    }
};
