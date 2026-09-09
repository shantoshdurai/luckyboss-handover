<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lucky AI — the screen a candidate lands on after signing in.
 *
 * The agent's one job is to collect what `JobMatchService::readiness()` says is
 * missing, and then get out of the way. Two things must hold:
 *
 *  1. **It asks only for what is missing**, and stops as soon as it can score.
 *     A fixed five-step wizard would ask a candidate whose resume already
 *     answered everything to answer it all again.
 *  2. **Skipping writes nothing.** Every field here feeds the matcher, and a
 *     helpful default is precisely how every candidate once scored 45% against
 *     every vacancy.
 */
class SeekerAgentTest extends TestCase
{
    use RefreshDatabase;

    private function candidate(array $profile = []): User
    {
        Role::firstOrCreate(['slug' => 'job-seeker'], ['name' => 'Job Seeker', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $user->roles()->attach(Role::where('slug', 'job-seeker')->value('id'));

        CandidateProfile::create(array_merge(['user_id' => $user->id, 'country_code' => 'SG'], $profile));

        return $user->fresh();
    }

    public function test_signing_in_lands_a_candidate_on_lucky_ai(): void
    {
        $this->seed();

        $this->post(route('login'), [
            'email' => 'candidate@luckyboss.test',
            'password' => 'password',
        ])->assertRedirect(route('seeker.home'));
    }

    public function test_the_home_greets_and_offers_four_ways_in(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->get(route('seeker.home'))
            ->assertOk()
            ->assertSee('Hello, Ravi')
            ->assertSee('What are we doing today?')
            // Nothing known about them yet, so the first card starts the agent.
            ->assertSee('Find me a job');
    }

    public function test_the_cards_change_once_we_can_score_them(): void
    {
        $user = $this->candidate([
            'current_title' => 'Electrician',
            'current_location' => 'Singapore',
            'years_experience' => 5,
            'skills' => ['Electrical Wiring'],
        ]);

        $this->actingAs($user)
            ->get(route('seeker.home'))
            ->assertOk()
            // The first card always opens the conversation now; only its
            // wording changes once we can score them.
            ->assertSee('Find me better work')
            ->assertDontSee('Find me a job');
    }

    public function test_the_agent_asks_the_next_missing_thing_and_only_that(): void
    {
        // A title alone is not enough to score, so the agent carries on — but
        // it must not ask again for what it already has.
        $user = $this->candidate(['country_code' => null, 'current_title' => 'Electrician']);

        $this->actingAs($user)
            ->get(route('seeker.agent.find'))
            ->assertOk()
            ->assertSee('What are you good at?')
            ->assertDontSee('What work do you do?');
    }

    public function test_an_answer_is_saved_and_the_agent_moves_on(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->post(route('seeker.agent.answer'), ['answer' => 'Site Electrician'])
            ->assertRedirect(route('seeker.agent.find'));

        $this->assertSame('Site Electrician', $user->fresh()->candidateProfile->current_title);
    }

    public function test_skills_are_written_where_the_matcher_reads_them(): void
    {
        // No country_code either: a title plus any second signal already makes
        // a candidate scoreable, and the agent stops asking the moment it can
        // score. So the title has to be answered here, not seeded.
        $user = $this->candidate(['country_code' => null]);

        $this->actingAs($user)
            ->post(route('seeker.agent.answer'), ['answer' => 'Electrician'])
            ->assertRedirect();

        $this->assertSame('Electrician', $user->fresh()->candidateProfile->current_title);

        $this->actingAs($user)
            ->post(route('seeker.agent.answer'), ['answer' => 'Wiring, Fault finding'])
            ->assertRedirect();

        $profile = $user->fresh()->candidateProfile;

        $this->assertSame(['Wiring', 'Fault finding'], $profile->skills);
        $this->assertSame(['Wiring', 'Fault finding'], $profile->resume_data['skills']);
    }

    public function test_skipping_writes_nothing_and_says_what_it_costs(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->post(route('seeker.agent.answer'), ['skip' => '1'])
            ->assertRedirect(route('seeker.home'))
            ->assertSessionHas('info');

        // No placeholder, no default, no empty string standing in for an answer.
        $this->assertNull($user->fresh()->candidateProfile->current_title);
    }

    public function test_a_candidate_we_can_already_score_is_sent_straight_to_their_matches(): void
    {
        $user = $this->candidate([
            'current_title' => 'Electrician',
            'current_location' => 'Singapore',
            'years_experience' => 5,
            'skills' => ['Electrical Wiring'],
        ]);

        $this->actingAs($user)
            ->get(route('seeker.agent.find'))
            ->assertRedirect(route('seeker.resume.matches'));
    }

    public function test_an_empty_answer_is_refused_rather_than_stored(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->post(route('seeker.agent.answer'), ['answer' => ''])
            ->assertSessionHasErrors('answer');

        $this->assertNull($user->fresh()->candidateProfile->current_title);
    }

    public function test_an_employer_cannot_open_the_candidate_agent(): void
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        $employer = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $employer->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->actingAs($employer)->get(route('seeker.home'))->assertForbidden();
    }
}
