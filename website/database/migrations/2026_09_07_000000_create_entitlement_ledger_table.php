<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every grant and every consumption of a billable action, as a ledger.
 *
 * A ledger rather than a `credits_remaining` counter, for three reasons:
 *
 *  - **It is explainable.** The first time an employer says "I bought 15 and
 *    only got 12", a counter can only restate its current value. This can show
 *    where each one went.
 *  - **It meters what is currently free.** Sir's decision on 2026-09-07 was that
 *    seekers are free "in a way like zero rupees", so the backend can flip them
 *    to paid at any time. That flip needs usage data that starts accruing now,
 *    not on the day someone decides to charge.
 *  - **It carries both billing models.** `expires_at` on a grant row is what
 *    separates a recurring plan's monthly allowance (expires) from a purchased
 *    pack (does not). Which of those we sell is still open — the ledger does not
 *    need to know.
 *
 * Owner is polymorphic because seeker actions are metered on the same path as
 * employer actions. A `company_id` column here would have forced the
 * `if (seeker) skip billing` branch that the decision above exists to prevent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entitlement_ledger', function (Blueprint $table) {
            $table->id();

            // App\Models\Company for employers, App\Models\User for candidates.
            $table->morphs('owner');

            // The SKU. See App\Services\EntitlementCatalogue.
            $table->string('key', 60);

            // Positive grants, negative consumption. Balance is the sum.
            $table->integer('delta');

            // purchase | plan_grant | free_tier | admin_grant | consumption
            $table->string('source', 30);

            // What the row was for: a Job, a JobApplication, a Payment.
            $table->nullableMorphs('reference');

            // Null means it never expires. A monthly plan grant sets this; a
            // purchased pack does not.
            $table->timestamp('expires_at')->nullable();

            // 'YYYY-MM' on free-tier grants, so one month's allowance is issued
            // exactly once per owner per SKU.
            $table->string('period', 7)->nullable();

            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'key'], 'entitlement_owner_key_idx');
            $table->unique(['owner_type', 'owner_id', 'key', 'period', 'source'], 'entitlement_free_tier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entitlement_ledger');
    }
};
