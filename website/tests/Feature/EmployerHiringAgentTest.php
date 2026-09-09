<?php

namespace Tests\Feature;

use App\Models\AgentConversation;
use App\Models\CandidateContactReveal;
use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\EntitlementLedger;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\HiringScript;
use Tests\TestCase;

/**
 * The employer's hiring conversation, and the shortlist it produces.
 *
 * The candidate's agent risks writing rubbish onto a profile. This one risks
 * something worse: it hands out real people's phone numbers. What is held here:
 *
 *  1. **The chips are the only accepted answers.** Anything else and the spec
 *     the shortlist is built from can be written to at will.
 *  2. **A shortlist is scored, never assembled.** Somebody the matcher cannot
 *     assess must not appear on it at all, because a card with no score on a
 *     page headed "people who fit" is a claim we have not earned.
 *  3. **A contact is revealed by a deliberate POST, never by loading a page**
 *     (§72), and it is free for a candidate who applied to this employer first
 *     (§73) — getting that backwards bills an employer for their own inbox.
 *  4. **Nothing is published on the employer's behalf.** Finishing the
 *     conversation must leave the job board exactly as it was.
 */
class EmployerHiringAgentTest extends TestCase
{
    use RefreshDatabase;

    private function employer(): User
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Arun Kumar',
            'email' => 'arun'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $company = Company::create(['name' => 'Acme Build', 'country_code' => 'SG', 'status' => 'verified']);
        $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);

        return $user->fresh();
    }

    /** A candidate the matcher can actually score. */
    private function mason(array $overrides = []): User
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Rajan Murugan',
            'email' => 'rajan'.uniqid().'@example.com',
            'phone' => '+6581234456',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        CandidateProfile::create(array_merge([
            'user_id' => $user->id,
            'country_code' => 'SG',
            'current_title' => 'Mason',
            'current_location' => 'Jurong East',
            'years_experience' => 9,
            'skills' => ['Brickwork', 'Plastering', 'Tiling'],
        ], $overrides));

        return $user->fresh();
    }

    /** @param  list<string>  $answers */
    private function runThrough(User $employer, array $answers): AgentConversation
    {
        $this->actingAs($employer)->post(route('employer.chat.start'));
        $conversation = AgentConversation::where('user_id', $employer->id)->latest('id')->firstOrFail();

        foreach ($answers as $answer) {
            $this->actingAs($employer)->post(route('employer.chat.answer', $conversation), ['answer' => $answer]);
        }

        return $conversation->fresh();
    }

    /** The whole script, in the order the chips are offered. */
    private function fullRun(): array
    {
        return array_merge(self::OPENING, ['Brickwork, Plastering', '5', 'Jurong East', '5', 'Immediately', '3000']);
    }

    /**
     * Greeting, then how, then the trade and the role.
     *
     * `how` was added on 2026-09-10 when "Find me people" and "Post a vacancy"
     * stopped being two cards on the landing screen — the fork moved into the
     * conversation, so every run now passes through it.
     */
    private const OPENING = ['Yes', HiringScript::HOW_CHAT, 'Construction', 'Mason'];

    public function test_the_employer_lands_on_the_agent_not_a_dashboard(): void
    {
        $this->actingAs($this->employer())
            ->get(route('employer.home'))
            ->assertOk()
            ->assertSee('Who are you hiring?')
            // One door into the conversation, not two. "Find me people" and
            // "Post a vacancy" were the same errand described twice; the fork is
            // now the second question inside the conversation. Asserted on the
            // card's own copy rather than on the words "Post a vacancy", which
            // are still a legitimate shortcut in the account menu.
            ->assertSee('Hire for a role')
            ->assertDontSee('The full form, when you already know exactly what you are advertising.');
    }

    public function test_the_conversation_offers_the_apps_own_trade_words(): void
    {
        $employer = $this->employer();

        $this->actingAs($employer)->post(route('employer.chat.start'));
        $conversation = AgentConversation::where('user_id', $employer->id)->latest('id')->firstOrFail();

        // Past the greeting and the how-do-you-want-to-do-this fork, into the
        // category question.
        $this->actingAs($employer)->post(route('employer.chat.answer', $conversation), ['answer' => 'Yes']);
        $this->actingAs($employer)->post(route('employer.chat.answer', $conversation), ['answer' => HiringScript::HOW_CHAT]);

        $this->actingAs($employer)
            ->get(route('employer.chat.show', $conversation))
            ->assertOk()
            ->assertSee('What kind of work is it?')
            // WorkTaxonomy, not our job categories: the vocabulary both sides of
            // the market describe work in.
            ->assertSee('Construction')
            ->assertSee('Healthcare &amp; Nursing', false);

        $this->actingAs($employer)->post(route('employer.chat.answer', $conversation), ['answer' => 'Construction']);

        $this->actingAs($employer)
            ->get(route('employer.chat.show', $conversation))
            ->assertSee('What is the job?')
            ->assertSee('Mason')
            ->assertSee('Scaffolder');
    }

    public function test_an_answer_that_was_not_offered_is_refused(): void
    {
        $employer = $this->employer();
        $conversation = $this->runThrough($employer, ['Yes', HiringScript::HOW_CHAT]);

        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => 'Astrophysics'])
            ->assertSessionHasErrors('answer');

        $this->assertArrayNotHasKey('category', $conversation->fresh()->answers);
    }

    public function test_the_site_location_may_be_typed_because_the_chips_are_only_a_shortcut(): void
    {
        $employer = $this->employer();
        $conversation = $this->runThrough($employer, array_merge(self::OPENING, ['Brickwork', '5']));

        // Nowhere near any vacancy we have — and accepted, because an employer's
        // yard very often is not.
        $this->actingAs($employer)
            ->post(route('employer.chat.answer', $conversation), ['answer' => 'Sembawang'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Sembawang', $conversation->fresh()->answers['location']);
    }

    public function test_the_closing_count_is_the_number_of_people_actually_scored(): void
    {
        $employer = $this->employer();
        $this->mason();

        // A candidate with nothing on their profile. The matcher refuses to
        // score them, so they must not be counted or listed.
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);
        $blank = User::create(['name' => 'Nobody', 'email' => 'blank'.uniqid().'@example.com', 'password' => 'password']);
        $blank->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));
        CandidateProfile::create(['user_id' => $blank->id, 'country_code' => null]);

        $conversation = $this->runThrough($employer, $this->fullRun());

        $this->actingAs($employer)
            ->get(route('employer.chat.shortlist', $conversation))
            ->assertOk()
            ->assertSee('Rajan Murugan')
            ->assertDontSee('Nobody');
    }

    public function test_finishing_the_conversation_publishes_nothing(): void
    {
        $employer = $this->employer();
        $this->mason();

        $before = Job::count();
        $this->runThrough($employer, $this->fullRun());

        $this->assertSame($before, Job::count(), 'the agent must never post a vacancy on the employer\'s behalf');
    }

    public function test_a_contact_is_not_on_the_page_until_it_is_asked_for(): void
    {
        $employer = $this->employer();
        $candidate = $this->mason();

        $conversation = $this->runThrough($employer, $this->fullRun());

        $this->actingAs($employer)
            ->get(route('employer.chat.shortlist', $conversation))
            ->assertOk()
            ->assertSee('Rajan Murugan')
            ->assertDontSee($candidate->email)
            ->assertDontSee('+6581234456');
    }

    public function test_revealing_a_sourced_candidate_spends_a_credit_and_is_recorded(): void
    {
        $employer = $this->employer();
        $candidate = $this->mason();

        $this->actingAs($employer)
            ->post(route('employer.candidates.reveal', $candidate))
            ->assertSessionHasNoErrors();

        $reveal = CandidateContactReveal::where('candidate_id', $candidate->id)->first();
        $this->assertNotNull($reveal);
        $this->assertTrue($reveal->was_charged);
        $this->assertSame('sourced', $reveal->reason);

        $this->assertDatabaseHas('entitlement_ledger', [
            'key' => 'candidate_view',
            'delta' => -1,
            'reference_id' => $candidate->id,
        ]);
    }

    public function test_a_candidate_who_applied_to_this_employer_is_free(): void
    {
        $employer = $this->employer();
        $candidate = $this->mason();
        $company = $employer->companies()->first();

        $category = JobCategory::firstOrCreate(['name' => 'Construction'], ['slug' => 'construction']);
        $job = Job::create([
            'company_id' => $company->id,
            'job_category_id' => $category->id,
            'title' => 'Mason',
            'description' => 'Brickwork and plastering.',
            'country_code' => 'SG',
            'location' => 'Jurong East',
            'status' => 'published',
        ]);
        JobApplication::create(['job_id' => $job->id, 'candidate_id' => $candidate->id, 'status' => 'applied']);

        $this->actingAs($employer)->post(route('employer.candidates.reveal', $candidate));

        $reveal = CandidateContactReveal::where('candidate_id', $candidate->id)->firstOrFail();
        $this->assertFalse($reveal->was_charged, 'spec §73: they came to this employer, so their number is free');
        $this->assertSame('organic', $reveal->reason);

        // And nothing was taken for it.
        $this->assertSame(0, EntitlementLedger::where('key', 'candidate_view')->where('delta', '<', 0)->count());
    }

    public function test_revealing_the_same_person_twice_does_not_charge_twice(): void
    {
        $employer = $this->employer();
        $candidate = $this->mason();

        $this->actingAs($employer)->post(route('employer.candidates.reveal', $candidate));
        $this->actingAs($employer)->post(route('employer.candidates.reveal', $candidate));

        $this->assertSame(1, CandidateContactReveal::where('candidate_id', $candidate->id)->count());
        $this->assertSame(
            1,
            EntitlementLedger::where('key', 'candidate_view')->where('delta', '<', 0)->count(),
            'a second look at a number you already hold is not a second purchase'
        );
    }

    public function test_a_candidate_cannot_open_the_hiring_agent(): void
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);
        $seeker = User::create(['name' => 'Seeker', 'email' => 's'.uniqid().'@example.com', 'password' => 'password']);
        $seeker->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        $this->actingAs($seeker)->get(route('employer.home'))->assertForbidden();
        $this->actingAs($seeker)->post(route('employer.chat.start'))->assertForbidden();
    }

    public function test_one_employer_cannot_read_another_employers_conversation(): void
    {
        $mine = $this->employer();
        $theirs = $this->employer();

        $conversation = $this->runThrough($mine, ['Yes', HiringScript::HOW_CHAT, 'Construction']);

        $this->actingAs($theirs)->get(route('employer.chat.show', $conversation))->assertForbidden();
        $this->actingAs($theirs)->get(route('employer.chat.shortlist', $conversation))->assertForbidden();
    }

    public function test_posting_the_vacancy_is_filled_in_from_what_was_asked(): void
    {
        $employer = $this->employer();
        $conversation = $this->runThrough($employer, $this->fullRun());

        $this->actingAs($employer)
            ->get(route('employer.jobs.create', ['from' => $conversation->id]))
            ->assertOk()
            ->assertSee('value="Mason"', false)
            ->assertSee('value="Jurong East"', false)
            // Five people, from the "2 to 5" bucket.
            ->assertSee('value="5"', false)
            ->assertSee('Filled in from what you told the hiring agent');
    }

    public function test_an_unfinished_conversation_does_not_prefill_a_job_form(): void
    {
        $employer = $this->employer();
        $conversation = $this->runThrough($employer, ['Yes', HiringScript::HOW_CHAT, 'Construction']);

        $this->actingAs($employer)
            ->get(route('employer.jobs.create', ['from' => $conversation->id]))
            ->assertOk()
            ->assertDontSee('Filled in from what you told the hiring agent');
    }
}
