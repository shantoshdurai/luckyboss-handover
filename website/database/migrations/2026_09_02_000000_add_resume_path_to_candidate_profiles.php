<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Somewhere to keep the resume file itself.
 *
 * `resume_file_name` held only the display name, so the uploaded document was
 * thrown away after parsing — and when parsing was switched off the file was
 * discarded outright. A candidate who uploads a CV and is then told to type
 * everything in by hand has every reason to think the app lost their document,
 * and the employer never sees the CV that was actually sent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->string('resume_path')->nullable()->after('resume_file_name');
        });
    }

    public function down(): void
    {
        Schema::table('candidate_profiles', function (Blueprint $table): void {
            $table->dropColumn('resume_path');
        });
    }
};
