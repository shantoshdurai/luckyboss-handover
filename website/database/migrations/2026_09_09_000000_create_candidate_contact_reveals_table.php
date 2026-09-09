<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who has seen whose phone number, and whether it cost anything.
 *
 * Spec §72 asks for a confirm before a contact is revealed, and §73 says a
 * candidate who applied to this employer directly is free — we charge for
 * people Lucky Boss found, never for people the employer earned themselves.
 * Both of those need a record, for three separate reasons:
 *
 *  - **So a second look is free.** Without a row, an employer who reopens a
 *    shortlist pays again for a number they already have, which is indefensible.
 *  - **So the candidate can be told.** A worker is entitled to know which
 *    companies hold their phone number and when they got it.
 *  - **So the ledger can be explained.** `candidate_view` consumption rows say
 *    a credit was spent; this says who it was spent on.
 *
 * `was_charged` is stored rather than derived. Whether a reveal was free
 * depended on the state of things at the moment it happened — the free tier, the
 * enforcement switch, whether that candidate had applied yet — and none of that
 * can be reconstructed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_contact_reveals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            // The person who tapped it, not just the company — an employer team
            // can have several members and "who looked" is a real question.
            $table->foreignId('revealed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('was_charged')->default(false);
            // 'organic' when they applied to us, 'sourced' when we found them.
            $table->string('reason', 20)->default('sourced');
            $table->timestamps();

            $table->unique(['company_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_contact_reveals');
    }
};
