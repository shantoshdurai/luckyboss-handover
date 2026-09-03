<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The measurements behind employer insights.
 *
 * The employer app sells a boost (spec §61) and then shows the employer nothing
 * about what it did. There was no honest way to fix that: nothing recorded how
 * often a vacancy was seen, and boosts existed only in the phone's local
 * storage, so an employer who reinstalled the app lost every record of what
 * they had paid for.
 *
 * Inventing view counts to fill the screen was the one option not on the table.
 * These two tables are what make a real answer possible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();

            // Null for a signed-out browser on the public site. Kept nullable
            // rather than dropping those rows: an employer paying for a boost
            // cares how many people saw the vacancy, not how many were logged in.
            $table->foreignId('viewer_id')->nullable()->constrained('users')->nullOnDelete();

            // 'app' | 'web' — a boost that works on one and not the other is
            // worth knowing about before an employer complains.
            $table->string('source', 16)->default('web');

            // Coarse de-duplication key: one row per viewer per job per day, so
            // a candidate refreshing a page cannot inflate what an employer is
            // shown. Hashed because it holds an IP for signed-out visitors.
            $table->string('dedupe_hash', 64)->nullable();

            $table->timestamp('viewed_at')->index();
            $table->timestamps();

            $table->index(['job_id', 'viewed_at']);
            $table->unique(['job_id', 'dedupe_hash']);
        });

        Schema::create('job_boosts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // 'featured' | 'urgent' | 'top' — matches BoostType in the app.
            $table->string('type', 24);

            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->index();

            // Stored in minor units, as the rest of the billing does, so a
            // rounding error cannot appear between the charge and the receipt.
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->default('SGD');

            $table->string('status', 16)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_boosts');
        Schema::dropIfExists('job_views');
    }
};
