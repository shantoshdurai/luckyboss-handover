<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What each company has actually spent our AI budget on.
 *
 * Spec §33 lists "AI Usage" as a package setting alongside the job and
 * candidate limits, and it was the only one with nothing behind it. The
 * packages said yes or no to AI and nothing more, so one employer on an
 * unlimited-feeling plan could cost more in Gemini calls than their whole
 * subscription brings in — and nobody would find out until the bill arrived.
 *
 * Logged per call rather than as a running counter, because a counter cannot
 * answer "what did they spend it on" when an employer disputes their limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // job_description | interview_questions | letter | shortlist
            $table->string('feature', 40);

            // 'platform' when it was our key and our money, 'byoai' when the
            // employer paid their own provider. Only platform calls count
            // against a plan limit — charging somebody for spending their own
            // budget would be indefensible.
            $table->string('source', 16)->default('platform');

            $table->timestamps();

            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_log');
    }
};
