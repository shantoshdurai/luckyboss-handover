<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\FeatureFlag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Resume-first onboarding on the web portal.
 *
 * The flow sir described is one continuous motion: upload, read, check, matched
 * jobs, Apply All. These tests hold the two things that make it trustworthy:
 *
 *  1. **Nothing extracted reaches the profile until the candidate confirms it.**
 *     A parser that writes straight through puts a fabricated employer in front
 *     of a real hiring manager.
 *  2. **The file is kept in every outcome**, including when autofill is off or
 *     the document could not be read.
 */
class SeekerResumeIntakeTest extends TestCase
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
        CandidateProfile::create(['user_id' => $user->id, 'country_code' => 'SG']);

        return $user;
    }

    private function flags(bool $ai, bool $parser): void
    {
        FeatureFlag::updateOrCreate(['key' => 'platform_ai_enabled'], ['name' => 'Platform AI', 'is_enabled' => $ai]);
        FeatureFlag::updateOrCreate(['key' => 'ai_resume_parser_enabled'], ['name' => 'Resume Parser', 'is_enabled' => $parser]);
    }

    private function cv(): UploadedFile
    {
        return UploadedFile::fake()->create('ravi_cv.pdf', 40, 'application/pdf');
    }

    private function fakeGemini(array $fields): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($fields)]]]]],
            ], 200),
        ]);
    }

    public function test_the_two_doors_are_offered_with_upload_first(): void
    {
        $this->actingAs($this->candidate())
            ->get(route('seeker.resume.choose'))
            ->assertOk()
            ->assertSee('Upload your resume')
            ->assertSee('Fill it in myself');
    }

    public function test_an_upload_lands_on_review_without_touching_the_profile(): void
    {
        $user = $this->candidate();
        $this->flags(ai: true, parser: true);
        $this->fakeGemini([
            'name' => 'Ravi Kumar',
            'current_title' => 'Electrician',
            'years_experience' => 7,
            'current_city' => 'Chennai',
            'skills' => ['Wiring', 'Conduit Bending'],
        ]);

        $this->actingAs($user)
            ->post(route('seeker.resume.upload'), ['resume' => $this->cv()])
            ->assertRedirect(route('seeker.resume.review'));

        // The document is kept immediately...
        $profile = $user->candidateProfile->fresh();
        $this->assertSame('ravi_cv.pdf', $profile->resume_file_name);
        $this->assertNotNull($profile->resume_path);

        // ...but not one extracted field has been written.
        $this->assertNull($profile->current_title, 'extraction must not reach the profile before the candidate confirms it');
        $this->assertNull($profile->years_experience);
        $this->assertEmpty($profile->skills ?? []);
    }

    public function test_the_review_screen_shows_the_extracted_values_for_checking(): void
    {
        $user = $this->candidate();
        $this->flags(ai: true, parser: true);
        $this->fakeGemini([
            'name' => 'Ravi Kumar',
            'current_title' => 'Electrician',
            'years_experience' => 7,
            'skills' => ['Wiring'],
        ]);

        $this->actingAs($user)->post(route('seeker.resume.upload'), ['resume' => $this->cv()]);

        $this->actingAs($user)
            ->get(route('seeker.resume.review'))
            ->assertOk()
            ->assertSee('Check what we read')
            ->assertSee('Electrician', false)
            ->assertSee('Nothing has been saved yet', false);
    }

    public function test_confirming_writes_the_profile_and_goes_to_matches(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->post(route('seeker.resume.confirm'), [
                'name' => 'Ravi Kumar',
                'email' => $user->email,
                'current_title' => 'Electrician',
                'years_experience' => 7,
                'current_location' => 'Singapore',
                'skills' => json_encode(['Wiring', 'Conduit Bending']),
            ])
            ->assertRedirect(route('seeker.resume.matches'));

        $profile = $user->candidateProfile->fresh();
        $this->assertSame('Electrician', $profile->current_title);
        $this->assertSame(7, $profile->years_experience);

        // Written to both places JobMatchService reads. Writing one and not the
        // other leaves a candidate unmatched right after telling us their trade.
        $this->assertContains('Wiring', $profile->skills);
        $this->assertContains('Wiring', $profile->resume_data['skills']);
    }

    public function test_skills_typed_as_plain_text_are_accepted(): void
    {
        // The no-JavaScript path posts a comma-separated string, not JSON.
        $user = $this->candidate();

        $this->actingAs($user)->post(route('seeker.resume.confirm'), [
            'name' => 'Ravi Kumar',
            'email' => $user->email,
            'skills' => 'Wiring, Forklift ,  Welding',
        ])->assertRedirect();

        $this->assertSame(['Wiring', 'Forklift', 'Welding'], $user->candidateProfile->fresh()->skills);
    }

    public function test_a_thin_profile_is_asked_for_more_rather_than_shown_unmatched_jobs(): void
    {
        $user = $this->candidate();

        $this->actingAs($user)
            ->get(route('seeker.resume.matches'))
            ->assertOk()
            ->assertSee('Upload your resume to see your matches');
    }

    public function test_the_file_survives_when_autofill_is_switched_off(): void
    {
        $user = $this->candidate();
        $this->flags(ai: true, parser: false);

        $this->actingAs($user)
            ->post(route('seeker.resume.upload'), ['resume' => $this->cv()])
            ->assertRedirect(route('seeker.resume.review'));

        $this->assertNotNull($user->candidateProfile->fresh()->resume_path);
    }

    public function test_an_unreadable_document_still_reaches_the_review_screen(): void
    {
        $user = $this->candidate();
        $this->flags(ai: true, parser: true);
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'sorry, no']]]]],
            ], 200),
        ]);

        $this->actingAs($user)
            ->post(route('seeker.resume.upload'), ['resume' => $this->cv()])
            ->assertRedirect(route('seeker.resume.review'));

        $this->assertNotNull($user->candidateProfile->fresh()->resume_path);

        // No invented values on the form.
        $this->actingAs($user)
            ->get(route('seeker.resume.review'))
            ->assertOk()
            ->assertSee('Tell us about your work');
    }

    public function test_a_wrong_file_type_is_refused(): void
    {
        $this->actingAs($this->candidate())
            ->post(route('seeker.resume.upload'), [
                'resume' => UploadedFile::fake()->create('holiday.png', 20, 'image/png'),
            ])
            ->assertSessionHasErrors('resume');
    }

    public function test_an_employer_cannot_reach_the_candidate_flow(): void
    {
        Role::firstOrCreate(['slug' => 'employer'], ['name' => 'Employer', 'guard_name' => 'web']);
        $employer = User::create(['name' => 'Boss', 'email' => 'boss'.uniqid().'@example.com', 'password' => 'password']);
        $employer->roles()->attach(Role::where('slug', 'employer')->value('id'));

        $this->actingAs($employer)->get(route('seeker.resume.choose'))->assertForbidden();
        $this->actingAs($employer)->get(route('seeker.resume.matches'))->assertForbidden();
    }
}
