<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->string('profile_photo_path')->nullable()->after('user_id');
            $table->date('date_of_birth')->nullable()->after('country_code');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->json('resume_data')->nullable()->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->dropColumn(['profile_photo_path', 'date_of_birth', 'gender', 'resume_data']);
        });
    }
};
