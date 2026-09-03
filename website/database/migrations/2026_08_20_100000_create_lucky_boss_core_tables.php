<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->unique()->after('email');
            $table->string('country_code', 3)->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('password');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('guard_name')->default('web'); $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->primary(['role_id', 'user_id']);
        });
        Schema::create('company_types', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('company_grades', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_type_id')->nullable()->constrained(); $table->foreignId('company_grade_id')->nullable()->constrained(); $table->string('name'); $table->string('registration_number')->nullable()->index(); $table->string('industry')->nullable(); $table->string('email')->nullable(); $table->string('phone', 32)->nullable(); $table->string('website')->nullable(); $table->string('country_code', 3)->nullable()->index(); $table->string('state')->nullable(); $table->string('city')->nullable(); $table->text('address')->nullable(); $table->string('status')->default('pending')->index(); $table->timestamp('verified_at')->nullable(); $table->timestamps();
        });
        Schema::create('company_users', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('company_role')->default('recruiter'); $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['company_id', 'user_id']);
        });
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); $table->string('country_code', 3)->nullable(); $table->string('current_title')->nullable(); $table->text('professional_summary')->nullable(); $table->string('current_location')->nullable(); $table->string('preferred_location')->nullable(); $table->unsignedSmallInteger('years_experience')->nullable(); $table->decimal('current_salary', 12, 2)->nullable(); $table->decimal('expected_salary', 12, 2)->nullable(); $table->string('preferred_currency', 3)->nullable(); $table->string('notice_period')->nullable(); $table->string('availability')->nullable(); $table->unsignedTinyInteger('profile_completion')->default(0); $table->boolean('is_visible')->default(true); $table->timestamps();
        });
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable(); $table->string('icon')->nullable(); $table->unsignedSmallInteger('sort_order')->default(0); $table->boolean('show_on_home')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('job_category_id')->nullable()->constrained(); $table->string('title')->index(); $table->longText('description'); $table->string('country_code', 3)->index(); $table->string('location')->nullable(); $table->string('work_mode')->default('on-site'); $table->string('job_type')->default('full-time'); $table->unsignedSmallInteger('experience_min')->nullable(); $table->unsignedSmallInteger('experience_max')->nullable(); $table->decimal('salary_min', 12, 2)->nullable(); $table->decimal('salary_max', 12, 2)->nullable(); $table->string('currency_code', 3)->default('SGD'); $table->boolean('salary_visible')->default(true); $table->unsignedInteger('vacancies')->default(1); $table->date('closing_date')->nullable()->index(); $table->string('status')->default('draft')->index(); $table->boolean('is_featured')->default(false); $table->boolean('is_urgent')->default(false); $table->boolean('is_paid_apply')->default(false); $table->decimal('application_fee', 12, 2)->nullable(); $table->timestamp('published_at')->nullable(); $table->timestamps();
        });
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id(); $table->string('key')->unique(); $table->string('name'); $table->text('description')->nullable(); $table->boolean('is_enabled')->default(false); $table->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete(); $table->string('action'); $table->string('entity_type')->nullable(); $table->unsignedBigInteger('entity_id')->nullable(); $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->string('ip_address', 45)->nullable(); $table->text('user_agent')->nullable(); $table->timestamps(); $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs'); Schema::dropIfExists('feature_flags'); Schema::dropIfExists('jobs'); Schema::dropIfExists('job_categories'); Schema::dropIfExists('candidate_profiles'); Schema::dropIfExists('company_users'); Schema::dropIfExists('companies'); Schema::dropIfExists('company_grades'); Schema::dropIfExists('company_types'); Schema::dropIfExists('role_user'); Schema::dropIfExists('roles');
        Schema::table('users', function (Blueprint $table) { $table->dropUnique(['phone']); $table->dropColumn(['phone', 'country_code', 'is_active']); });
    }
};