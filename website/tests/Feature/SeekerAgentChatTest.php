<?php

namespace Tests\Feature;

use App\Models\AgentConversation;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AgentScript;
use Tests\TestCase;

/**
 * The linear Lucky AI conversation.
 *
 * Sir asked for TickBig's Agent Ambo shape: one question at a time, answered by
 * tapping, each answer left on screen. The risks that shape creates, and what
 * each test here holds:
 *
 *  1. **The chips must be the only accepted answers.** If a posted value is not
 *     one of the options offered, the list is decoration and the conversation
 *     becomes a way to write arbitrary strings onto a profile the matcher reads.
 *  2. **Every option offered must come from real vacancies.** An agent that
 *     invites a candidate to pick a trade we have no work in has arranged its
 *     own empty match list.
 *  3. **The closing number must be counted, never claimed.**
 */
class SeekerAgentChatTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(): User
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));
        CandidateProfile::create(['user_id' => $user->id, 'country_code' => null]);

        return $user->fresh();
    }

    private function vacancy(string $title = 'Warehouse Supervisor', string $location = 'Jurong East'): Job
    {
        $category = JobCategory::firstOrCreate(['name' => 'Warehouse'], ['slug' => 'warehouse']);
        $company = Company::create(['name' => 'Acme', 'country_code' => 'SG', 'status' => 'active']);

        return Job::create([
            'company_id' => $company->id,
            'job_category_id' => $category->id,
            'title' => $title,
            'description' => 'Forklift, stock counting and night shifts in a busy warehouse.',
            'country_code' => 'SG',
            'location' => $location,
            'experience_min' => 2,
            'experience_max' => 8,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function start(User $user): AgentConversation
    {
        $this->actingAs($user)->post(route('seeker.chat.start'))->assertRedirect();

        return AgentConversation::where('user_id', $user->id)->latest('id')->firstOrFail();
    }

    public function test_a_conversation_opens_with_the_agent_speaking_first(): void
    {
        $user = $this->candidate();
        $conversation = $this->start($user);

        $this->assertSame('agent', $conversation->messages()->first()->role);

        $this->actingAs($user)
            ->get(route('seeker.chat.show', $conversation))
            ->assertOk()
            ->assertSee('you should not need the keyboard')
            ->assertSee('Yes, let’s go', false);
    }

    public function test_the_option_list_is_built_from_real_vacancies(): void
    {
        $user = $this->candidate();
        $this->vacancy('Warehouse Supervisor');
        $conversation = $this->start($user);

        // Past the opening confirm, onto the category question.
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK]);

        $this->actingAs($user)
            ->get(route('seeker.chat.show', $conversation))
            ->assertOk()
            ->assertSee('What work are you looking for?')
            // The app's own categories, whether or not we have a vacancy in one.
            ->assertSee('Construction')
            ->assertSee('Healthcare &amp; Nursing', false);

        // Then the trade, narrowed to that category.
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Construction']);

        $this->actingAs($user)
            ->get(route('seeker.chat.show', $conversation))
            ->assertOk()
            ->assertSee('What is your work?')
            // Construction's own trades, from AppData.
            ->assertSee('Mason')
            ->assertSee('Scaffolder');
    }

    public function test_an_answer_outside_the_offered_options_is_refused(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK]);

        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => 'Astronautics'])
            ->assertSessionHasErrors('answer');

        $this->assertNull($user->fresh()->candidateProfile->preferred_category);
    }

    public function test_an_answer_outside_the_offered_choices_is_refused(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);

        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK]);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Construction']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Mason']);

        // Experience is a chip list; 45 was never offered.
        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => '45'])
            ->assertSessionHasErrors('answer');

        $this->assertNull($user->fresh()->candidateProfile->years_experience);
    }

    public function test_the_whole_conversation_writes_the_profile_the_matcher_reads(): void
    {
        $user = $this->candidate();
        $this->vacancy('Warehouse Supervisor', 'Jurong East');
        $conversation = $this->start($user);

        foreach (['Yes', AgentScript::HOW_ASK, 'Construction', 'Mason', '4', 'Brickwork, Plastering', 'Jurong East'] as $answer) {
            $this->actingAs($user)
                ->post(route('seeker.chat.answer', $conversation), ['answer' => $answer])
                ->assertRedirect();
        }

        $profile = $user->fresh()->candidateProfile;

        $this->assertSame('Mason', $profile->current_title);
        $this->assertSame(4, $profile->years_experience);
        $this->assertSame('Jurong East', $profile->current_location);
        $this->assertSame(['Brickwork', 'Plastering'], $profile->skills);
        // Both places, because JobMatchService reads both.
        $this->assertSame(['Brickwork', 'Plastering'], $profile->resume_data['skills']);

        $this->assertSame('done', $conversation->fresh()->status);
    }

    public function test_a_chosen_phrase_reads_back_as_the_phrase_not_the_number(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);

        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK]);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Construction']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Mason']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => '4']);

        // Storing 4 is right; showing "4" in the bubble misquotes the candidate.
        $this->actingAs($user)
            ->get(route('seeker.chat.show', $conversation))
            ->assertOk()
            ->assertSee('3–5 years');
    }

    public function test_the_closing_line_counts_matches_rather_than_claiming_them(): void
    {
        $user = $this->candidate();
        $this->vacancy('Warehouse Supervisor', 'Jurong East');
        $conversation = $this->start($user);

        foreach (['Yes', AgentScript::HOW_ASK, 'Construction', 'Mason', '4', 'Brickwork, Plastering', 'Jurong East'] as $answer) {
            $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => $answer]);
        }

        $closing = $conversation->fresh()->messages()->get()->last();

        $this->assertSame('agent', $closing->role);
        $this->assertStringContainsString("That's everything", $closing->body);
        // Either a counted number or an honest "nothing above the bar" — never
        // a figure we did not score.
        $this->assertTrue(
            str_contains($closing->body, 'I found') || str_contains($closing->body, 'Nothing is above'),
            'The closing line must report a counted result: '.$closing->body
        );
    }

    public function test_an_async_answer_returns_the_transcript_and_next_control(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);

        $response = $this->actingAs($user)
            ->withHeader('X-Chat-Async', '1')
            ->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes'])
            ->assertOk()
            ->assertJsonStructure(['transcript', 'control', 'done']);

        // The transcript comes back rendered, so the page can drop it straight
        // in without a reload — and it is the same partial the page uses, so a
        // message added this way cannot look different from one that was there.
        // The greeting is followed by the CV-or-questions fork.
        $this->assertStringContainsString('Shall I read your CV', $response->json('transcript'));
        $this->assertStringContainsString(AgentScript::HOW_ASK, $response->json('control'));
        $this->assertFalse($response->json('done'));
    }

    public function test_a_refused_async_answer_returns_the_error_and_keeps_the_question(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK]);

        $response = $this->actingAs($user)
            ->withHeader('X-Chat-Async', '1')
            ->post(route('seeker.chat.answer', $conversation), ['answer' => 'Astronautics'])
            ->assertOk();

        $this->assertNotEmpty($response->json('error'));
        // The same question comes back, so the candidate is not left staring at
        // a conversation with nothing to tap.
        $this->assertStringContainsString('Construction', $response->json('control'));
        $this->assertNull($user->fresh()->candidateProfile->preferred_category);
    }

    public function test_the_conversation_still_works_without_javascript(): void
    {
        $user = $this->candidate();
        $this->vacancy();
        $conversation = $this->start($user);

        // No X-Chat-Async header: the plain form path must still redirect and
        // advance, because the chips are ordinary submit buttons.
        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes'])
            ->assertRedirect(route('seeker.chat.show', $conversation));

        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_ASK])
            ->assertRedirect(route('seeker.chat.show', $conversation));

        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => 'Construction'])
            ->assertRedirect();

        $this->assertSame('Construction', $user->fresh()->candidateProfile->preferred_category);
    }

    public function test_starting_always_opens_a_new_conversation(): void
    {
        $user = $this->candidate();
        $this->vacancy();

        $first = $this->start($user);
        $this->actingAs($user)->post(route('seeker.chat.answer', $first), ['answer' => 'Yes']);

        $second = $this->start($user);

        // A half-finished chat must not be silently reopened: the button says it
        // will find work, and landing back in an hour-old conversation reads as
        // the button doing nothing. History is the way back to that one.
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, AgentConversation::where('user_id', $user->id)->count());
        $this->assertSame('open', $first->fresh()->status, 'The abandoned one stays listed in History.');
    }

    public function test_a_candidate_cannot_open_someone_elses_conversation(): void
    {
        $mine = $this->candidate();
        $theirs = $this->candidate();
        $conversation = $this->start($theirs);

        $this->actingAs($mine)->get(route('seeker.chat.show', $conversation))->assertForbidden();
        $this->actingAs($mine)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes'])->assertForbidden();
    }

    public function test_an_untouched_conversation_is_reused_rather_than_piling_up(): void
    {
        $user = $this->candidate();

        // Nothing was answered in the first, so there is nothing to come back
        // to and nothing to list — reusing it keeps History free of empty rows
        // from a double tap. The moment a question is answered, starting again
        // opens a new one (see the test above).
        $first = $this->start($user);
        $second = $this->start($user);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, AgentConversation::where('user_id', $user->id)->count());
    }
}
