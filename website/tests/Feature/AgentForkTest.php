<?php

namespace Tests\Feature;

use App\Models\AgentConversation;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\AgentScript;
use App\Services\HiringScript;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The fork that moved off the landing screen and into the conversation.
 *
 * The employer home offered "Find me people" and "Post a vacancy" as two cards.
 * They are one errand — both end in a vacancy with candidates against it — and
 * the difference is only *how* you describe it, which is not a question a
 * landing screen can help anyone answer. It is asked second in the conversation
 * now, and the candidate's agent asks its own version of it: read the CV, or ask
 * the questions.
 *
 * The important part in both is the handover. Answering one way ends the
 * conversation and moves to another screen, and it has to work on the async
 * path — where a 302 would be followed by fetch itself and the transcript would
 * try to render somebody else's HTML — as well as with scripting off.
 */
class AgentForkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles, the trade taxonomy and the packages. Every test here needs all
        // three, and the fork question's chips come from the script rather than
        // the database, so seeding once here keeps each test to its own point.
        $this->seed();
    }

    private function employer(): User
    {
        $user = User::factory()->create(['email' => 'fork@acme.test', 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $company = Company::create(['name' => 'Fork Co', 'country_code' => 'SG', 'status' => 'verified']);
        $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        return $user;
    }

    private function candidate(): User
    {
        $user = User::factory()->create(['email' => 'forkseeker@acme.test', 'password' => 'password123']);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));
        $user->candidateProfile()->create(['profile_completion' => 10]);

        return $user;
    }

    private function openEmployerChat(User $employer): AgentConversation
    {
        $this->actingAs($employer)->post(route('employer.chat.start'));
        $conversation = AgentConversation::where('user_id', $employer->id)->latest('id')->firstOrFail();

        $this->actingAs($employer)->post(route('employer.chat.answer', $conversation), ['answer' => 'Yes']);

        return $conversation->fresh();
    }

    public function test_the_employer_is_asked_how_before_anything_about_the_role(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->actingAs($employer)
            ->get(route('employer.chat.show', $conversation))
            ->assertOk()
            ->assertSee('Do you want to talk it through, or fill in the form yourself?')
            ->assertSee(HiringScript::HOW_CHAT)
            ->assertSee(HiringScript::HOW_FORM);
    }

    public function test_choosing_the_form_hands_over_to_the_form(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => HiringScript::HOW_FORM])
            ->assertRedirect(route('employer.jobs.create'));
    }

    public function test_the_async_path_is_told_where_to_go_rather_than_redirected(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        // A 302 here would be followed by fetch, and the JSON body would be the
        // job form's HTML.
        $this->actingAs($employer)
            ->withHeader('X-Chat-Async', '1')
            ->post(route('employer.chat.answer', $conversation), ['answer' => HiringScript::HOW_FORM])
            ->assertOk()
            ->assertExactJson(['redirect' => route('employer.jobs.create')]);
    }

    public function test_the_handed_over_conversation_is_still_saved(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => HiringScript::HOW_FORM]);

        // Someone who changes their mind has to be able to come back to it and
        // tap the other answer.
        $this->assertSame(HiringScript::HOW_FORM, $conversation->fresh()->answers['how']);
    }

    public function test_talking_it_through_carries_on_to_the_trade(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => HiringScript::HOW_CHAT])
            ->assertRedirect(route('employer.chat.show', $conversation));

        $this->actingAs($employer)
            ->get(route('employer.chat.show', $conversation))
            ->assertSee('What kind of work is it?');
    }

    public function test_an_invented_answer_to_the_fork_is_refused(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => 'Do it for me'])
            ->assertSessionHasErrors('answer');

        $this->assertArrayNotHasKey('how', $conversation->fresh()->answers);
    }

    public function test_the_candidate_is_offered_the_resume_reader_inside_the_conversation(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->post(route('seeker.chat.start'));
        $conversation = AgentConversation::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);

        // The resume reader has existed since ResumeParseController shipped, and
        // lived behind a card on the home screen — never offered to the person
        // who had just started answering questions it could have answered.
        $this->actingAs($user)
            ->get(route('seeker.chat.show', $conversation))
            ->assertOk()
            ->assertSee('Shall I read your CV')
            ->assertSee(AgentScript::HOW_RESUME)
            ->assertSee(AgentScript::HOW_ASK);

        $this->actingAs($user)
            ->post(route('seeker.chat.answer', $conversation), ['answer' => AgentScript::HOW_RESUME])
            ->assertRedirect(route('seeker.resume.choose'));

        $this->assertSame(AgentScript::HOW_RESUME, $conversation->fresh()->answers['how']);
    }

    public function test_a_saved_conversation_can_be_cleared_and_takes_its_transcript_with_it(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $this->assertTrue($conversation->messages()->exists());

        $this->actingAs($employer)
            ->delete(route('employer.chat.destroy', $conversation))
            ->assertRedirect();

        $this->assertDatabaseMissing('agent_conversations', ['id' => $conversation->id]);
        // Cascaded by the foreign key. A transcript with no conversation is a row
        // nothing can ever read and nothing will ever delete.
        $this->assertDatabaseMissing('agent_messages', ['agent_conversation_id' => $conversation->id]);
    }

    public function test_one_person_cannot_clear_another_persons_conversation(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $intruder = User::factory()->create(['email' => 'intruder@acme.test', 'password' => 'password123']);
        $intruder->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->actingAs($intruder)
            ->delete(route('employer.chat.destroy', $conversation))
            ->assertForbidden();

        $this->assertDatabaseHas('agent_conversations', ['id' => $conversation->id]);
    }

    public function test_a_candidate_can_clear_their_own_conversation(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)->post(route('seeker.chat.start'));
        $conversation = AgentConversation::where('user_id', $user->id)->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->delete(route('seeker.chat.destroy', $conversation))
            ->assertRedirect();

        $this->assertDatabaseMissing('agent_conversations', ['id' => $conversation->id]);
    }

    public function test_the_home_screen_offers_the_clear_control_for_each_saved_row(): void
    {
        $user = $this->candidate();
        $this->actingAs($user)->post(route('seeker.chat.start'));
        $conversation = AgentConversation::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->actingAs($user)->post(route('seeker.chat.answer', $conversation), ['answer' => 'Yes']);

        $this->actingAs($user)
            ->get(route('seeker.home'))
            ->assertOk()
            ->assertSee('Clear this conversation')
            ->assertSee(route('seeker.chat.destroy', $conversation), false);
    }

    public function test_the_employer_chat_shows_what_is_left_of_the_real_balances(): void
    {
        $employer = $this->employer();
        $conversation = $this->openEmployerChat($employer);

        $page = $this->actingAs($employer)->get(route('employer.chat.show', $conversation))->assertOk();

        // The monthly free tier with no plan: one vacancy, five candidates, five
        // reports. Real ledger balances, not a constant.
        $page->assertSee('1</span>', false);
        $page->assertSee('vacancy left');
        $page->assertSee('candidates left');
        $page->assertSee(route('employer.subscription'), false);
    }
}
