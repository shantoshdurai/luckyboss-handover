<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-apply: applying on a candidate's behalf while they are not watching.
 *
 * Two tables, and the second one is not optional.
 *
 * `candidate_auto_apply_settings` is the candidate's own opt-in. The admin
 * switch in Site Settings is a kill-switch over the whole feature, not consent
 * for any individual — nobody is auto-applied because an administrator turned
 * something on.
 *
 * `auto_apply_runs` is the log. Without it, a candidate who switched this on
 * and sees no new applications cannot tell the difference between "we looked
 * and nothing cleared your threshold" and "it is broken", and neither can we.
 * Every run writes a row even when it applies to nothing, with the reason.
 * This project's recurring defect is surfaces that imply activity that never
 * happened; a run log is the cheapest defence against adding another one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_auto_apply_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            // Null means "use the platform threshold". Stored as null rather
            // than copied, so raising the admin minimum raises it for everyone
            // who never expressed a preference of their own.
            $table->unsignedTinyInteger('minimum_score')->nullable();
            $table->unsignedSmallInteger('daily_limit')->default(5);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('auto_apply_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('ran_at');
            // How many published vacancies were scored this run, and how many
            // cleared the bar and were applied to.
            $table->unsignedInteger('considered')->default(0);
            $table->unsignedInteger('applied')->default(0);
            // applied | no_matches | not_ready | limit_reached | no_credits | disabled
            $table->string('outcome', 32);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ran_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_apply_runs');
        Schema::dropIfExists('candidate_auto_apply_settings');
    }
};
