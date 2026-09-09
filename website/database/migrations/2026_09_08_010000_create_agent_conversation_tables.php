<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lucky AI conversations.
 *
 * The agent stops being four links to four pages and becomes what sir asked
 * for after watching TickBig's Agent Ambo: one question at a time, answered by
 * tapping, with each answer staying on screen as part of a transcript.
 *
 * Stored rather than kept in the session for two reasons. It makes the History
 * rail real — it has been showing an honest "No saved conversations yet" since
 * the rail was built — and a candidate who closes the tab halfway through
 * answering four questions should not have to start again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Which script is running: find_job today, room for more.
            $table->string('intent', 40);
            // The answers collected so far, keyed by question. The profile is
            // still the system of record — this is the transcript's own copy,
            // so re-opening a finished conversation reads back what was said
            // rather than what the profile has since been edited to.
            $table->json('answers')->nullable();
            $table->string('status', 20)->default('open'); // open | done
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('agent_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // agent | user
            $table->text('body');
            // The question key this message belongs to, so a transcript can be
            // replayed without re-deriving it from the text.
            $table->string('question_key', 40)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_messages');
        Schema::dropIfExists('agent_conversations');
    }
};
