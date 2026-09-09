<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Spec §67 — AI usage accounting.
 *
 * The spec asks every AI call to record employer, user, job, candidate, feature,
 * provider, model, tokens, estimated cost, date and status, and says plainly:
 *
 *   > This is critical for billing and auditing.
 *
 * `ai_usage_log` had seven columns — id, company_id, user_id, feature, source
 * and timestamps. No model, no tokens, no cost. The consequence was concrete:
 * §12 lets admin sell "Professional: 100 AI candidate searches" and §79 puts an
 * **AI Cost** card on the admin dashboard beside Employer Revenue, and we could
 * populate neither. We could not tell whether a package priced at SGD 299 made
 * money or lost it.
 *
 * `estimated_cost_usd` is decimal(12,6), not a float: fractions of a cent per
 * call add up across a month, and float drift in money is not worth the argument
 * it eventually causes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_usage_log', function (Blueprint $table) {
            // `company_id` was NOT NULL, which meant every seeker-side AI call —
            // resume parsing and the copilot, the two that cost us money on the
            // free side — threw on insert and recorded nothing. The write is
            // wrapped in a try/catch so accounting can never break the feature it
            // measures, and that is exactly why nobody noticed. A candidate has
            // no company; the column has to allow it.
            $table->foreignId('company_id')->nullable()->change();

            // Which piece of work the spend was for. Nullable because seeker-side
            // calls — resume parsing, the copilot — have neither.
            $table->foreignId('job_id')->nullable()->after('user_id');
            $table->foreignId('candidate_id')->nullable()->after('job_id');

            // Whose account paid: 'lucky_boss' or 'employer_byoai' (§5). Cost is
            // only ours when it is the former, and the AI Cost card must not
            // count an employer's own API spend as ours.
            $table->string('provider', 40)->nullable()->after('feature');
            $table->string('model', 80)->nullable()->after('provider');

            $table->unsignedInteger('prompt_tokens')->nullable()->after('model');
            $table->unsignedInteger('completion_tokens')->nullable()->after('prompt_tokens');
            $table->decimal('estimated_cost_usd', 12, 6)->nullable()->after('completion_tokens');

            // success | failed | disabled | unreadable — a failed call still cost
            // us the tokens it burned before failing, so it is recorded too.
            $table->string('status', 20)->nullable()->after('estimated_cost_usd');

            $table->index(['company_id', 'created_at'], 'ai_usage_company_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ai_usage_log', function (Blueprint $table) {
            $table->dropIndex('ai_usage_company_date_idx');
            $table->dropColumn([
                'job_id', 'candidate_id', 'provider', 'model',
                'prompt_tokens', 'completion_tokens', 'estimated_cost_usd', 'status',
            ]);
        });
    }
};
