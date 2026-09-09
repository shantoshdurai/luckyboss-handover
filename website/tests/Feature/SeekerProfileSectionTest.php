<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Per-section editing on the view-first profile.
 *
 * The view-first profile shipped with every pencil handing off to the 396-line
 * form, so fixing one line of a summary meant a round trip through every field
 * the candidate had already filled in. These save one section at a time.
 *
 * The risk this creates, and the reason for most of these tests: a section save
 * that posts a partial payload could blank the fields it did not render. A
 * candidate editing their skills must not lose their job title.
 */
class SeekerProfileSectionTest extends TestCase
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

        CandidateProfile::create(array_merge([
            'user_id' => $user->id,
            'country_code' => 'SG',
            'current_title' => 'Electrician',
            'current_location' => 'Singapore',
            'years_experience' => 5,
            'professional_summary' => 'Five years on commercial fit-outs.',
            'skills' => ['Electrical Wiring'],
        ], $profile));

        return $user->fresh();
    }

    public function test_a_section_save_leaves_every_other_section_alone(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'about'), [
                'professional_summary' => 'Rewritten summary.',
            ])
            ->assertRedirect();

        $profile = $user->fresh()->candidateProfile;

        $this->assertSame('Rewritten summary.', $profile->professional_summary);
        // The fields the About form never rendered must survive untouched.
        $this->assertSame('Electrician', $profile->current_title);
        $this->assertSame(5, $profile->years_experience);
        $this->assertSame(['Electrical Wiring'], $profile->skills);
    }

    public function test_experience_saves_its_own_fields(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'experience'), [
                'current_title' => 'Senior Electrician',
                'years_experience' => 9,
                'current_location' => 'Jurong',
                'notice_period' => 'Immediate',
                'qualification' => 'ITI Electrician',
            ])
            ->assertRedirect();

        $profile = $user->fresh()->candidateProfile;

        $this->assertSame('Senior Electrician', $profile->current_title);
        $this->assertSame(9, $profile->years_experience);
        $this->assertSame('Jurong', $profile->current_location);
        $this->assertSame('Five years on commercial fit-outs.', $profile->professional_summary);
    }

    public function test_skills_are_written_where_the_matcher_reads_them(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'skills'), [
                'skills' => 'Conduit installation, Fault finding,  Cable pulling ',
            ])
            ->assertRedirect();

        $profile = $user->fresh()->candidateProfile;

        $expected = ['Conduit installation', 'Fault finding', 'Cable pulling'];

        // Both places, because JobMatchService reads both. Writing one and not
        // the other is how a candidate ends up with skills on screen and an
        // empty match list.
        $this->assertSame($expected, $profile->skills);
        $this->assertSame($expected, $profile->resume_data['skills']);
    }

    public function test_preferences_save(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'preferences'), [
                'preferred_location' => 'Tuas',
                'expected_salary' => 3200,
                'preferred_currency' => 'SGD',
                'availability' => 'Immediately',
            ])
            ->assertRedirect();

        $profile = $user->fresh()->candidateProfile;

        $this->assertSame('Tuas', $profile->preferred_location);
        $this->assertEquals(3200, $profile->expected_salary);
        $this->assertSame('SGD', $profile->preferred_currency);
    }

    public function test_an_unknown_section_is_not_a_way_to_write_arbitrary_columns(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'documents'), ['resume_file_name' => 'forged.pdf'])
            ->assertNotFound();

        $this->assertNull($user->fresh()->candidateProfile->resume_file_name);
    }

    public function test_a_bad_value_is_refused_and_reopens_its_own_section(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->put(route('seeker.profile.section', 'experience'), ['years_experience' => 900])
            ->assertSessionHasErrors('years_experience');

        $this->assertSame(5, $user->fresh()->candidateProfile->years_experience);

        // The page must reopen on Experience rather than on the first tab, or
        // the error renders on a panel the candidate cannot see. Same failure
        // the registration wizard had before $stepFields.
        $this->actingAs($user)
            ->from(route('seeker.profile.show'))
            ->followingRedirects()
            ->put(route('seeker.profile.section', 'experience'), ['years_experience' => 900])
            ->assertOk()
            ->assertSee("editing: 'experience'", false)
            ->assertSee("tab: 'experience'", false);
    }

    public function test_an_employer_cannot_write_a_candidate_section(): void
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);

        $employer = User::create([
            'name' => 'Hiring Manager',
            'email' => 'hm'.uniqid().'@example.com',
            'password' => 'password',
        ]);
        $employer->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->actingAs($employer)
            ->put(route('seeker.profile.section', 'about'), ['professional_summary' => 'x'])
            ->assertForbidden();
    }
}
