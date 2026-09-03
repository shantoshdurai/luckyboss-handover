<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The skill taxonomy behind key-skill entry and related-skill suggestions.
 *
 * Two tables rather than a JSON column, because the interesting query runs in
 * the other direction: "given Flutter, what else do candidates who list Flutter
 * also list?" That is a join, and it has to stay fast while a candidate taps
 * chips one after another.
 *
 * Relations are stored as directed rows and written in both directions by the
 * seeder. Storing one row and querying with an OR across two columns looks
 * tidier but cannot use an index on either side.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);

            // Lowercased, punctuation-stripped form. 'Node.js', 'node js' and
            // 'NodeJS' must not become three separate skills on three different
            // candidate profiles, or matching silently degrades.
            $table->string('slug', 140)->unique();

            // Which of the app's job categories this belongs to. Nullable
            // because plenty of skills are cross-cutting (Excel, English).
            $table->string('category', 80)->nullable()->index();

            // Ranks type-ahead results and seeds the "suggested" lists, so the
            // common skill appears above the obscure one.
            $table->unsignedInteger('popularity')->default(0);

            // Skills discovered from AI expansion rather than the curated seed.
            // Kept separate so an admin can review what the model invented
            // before it is offered as though it were vetted.
            $table->boolean('is_curated')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'popularity']);
        });

        Schema::create('skill_relations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->foreignId('related_skill_id')->constrained('skills')->cascadeOnDelete();

            // How strongly the two co-occur. Ordering by this is what makes
            // Flutter suggest Dart before it suggests Git.
            $table->unsignedSmallInteger('weight')->default(50);

            $table->unique(['skill_id', 'related_skill_id']);
            $table->index(['skill_id', 'weight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_relations');
        Schema::dropIfExists('skills');
    }
};
