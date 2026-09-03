<?php

namespace Tests\Feature;

use App\Models\CandidateProfile;
use App\Models\FeatureFlag;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The resume must survive whether or not the AI parses it.
 *
 * Autofill used to return 403 and throw the upload away when the parser was
 * switched off, so a candidate who attached their CV and was then asked to type
 * everything by hand had every reason to think the app had lost it — and the
 * employer never received the document that was actually sent.
 *
 * Parsing is a convenience layered on top of storing the file. These tests hold
 * that line: in every outcome the file is saved and the response says what the
 * candidate should do next.
 */
class ResumeUploadTest extends TestCase
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
        CandidateProfile::create(['user_id' => $user->id, 'country_code' => 'IN']);

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

    public function test_the_resume_is_saved_even_when_autofill_is_switched_off(): void
    {
        $user = $this->candidate();
        Sanctum::actingAs($user, ['job-seeker']);
        $this->flags(ai: true, parser: false);

        $response = $this->postJson('/api/v1/resume/parse', ['resume' => $this->cv()]);

        // No longer a 403. The upload succeeded; only the autofill is off.
        $response->assertOk()
            ->assertJsonPath('status', 'disabled')
            ->assertJsonPath('resume.stored', true)
            ->assertJsonPath('resume.file_name', 'ravi_cv.pdf');

        $this->assertStringContainsString(
            'enter your details',
            $response->json('message'),
            'the candidate must be told what to do next, not just refused'
        );

        $profile = $user->candidateProfile->fresh();
        $this->assertSame('ravi_cv.pdf', $profile->resume_file_name);
        $this->assertNotNull($profile->resume_path, 'the file must be attached to the profile');
    }

    public function test_the_master_ai_switch_also_leaves_the_resume_saved(): void
    {
        $user = $this->candidate();
        Sanctum::actingAs($user, ['job-seeker']);
        $this->flags(ai: false, parser: true);

        $this->postJson('/api/v1/resume/parse', ['resume' => $this->cv()])
            ->assertOk()
            ->assertJsonPath('status', 'disabled')
            ->assertJsonPath('resume.stored', true);

        $this->assertNotNull($user->candidateProfile->fresh()->resume_path);
    }

    public function test_an_unreadable_resume_still_keeps_the_file(): void
    {
        $user = $this->candidate();
        Sanctum::actingAs($user, ['job-seeker']);
        $this->flags(ai: true, parser: true);
        config(['services.gemini.api_key' => 'test-key']);

        // Gemini answers, but with nothing usable in it.
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'sorry, no']]]]],
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/resume/parse', ['resume' => $this->cv()]);

        $response->assertOk()
            ->assertJsonPath('status', 'unreadable')
            ->assertJsonPath('resume.stored', true);

        // The important half: a parser that cannot read the document must not
        // invent a work history for a real person.
        $this->assertNull($response->json('data'));
        $this->assertNotNull($user->candidateProfile->fresh()->resume_path);
    }

    public function test_a_successful_parse_returns_fields_marked_for_review(): void
    {
        $user = $this->candidate();
        Sanctum::actingAs($user, ['job-seeker']);
        $this->flags(ai: true, parser: true);
        config(['services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode([
                    'name' => 'Ravi Kumar',
                    'email' => 'ravi.kumar@example.com',
                    'phone' => '+919876512345',
                    'current_title' => 'Forklift Operator',
                    'years_experience' => 6,
                    'current_city' => 'Chennai',
                    'skills' => ['Forklift Operating', 'Pallet Jack'],
                ])]]]]],
            ], 200),
        ]);

        $this->postJson('/api/v1/resume/parse', ['resume' => $this->cv()])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            // Never presented as though the candidate typed it.
            ->assertJsonPath('requires_review', true)
            ->assertJsonPath('data.name', 'Ravi Kumar')
            ->assertJsonPath('data.skills.0', 'Forklift Operating')
            ->assertJsonPath('resume.stored', true);
    }

    public function test_a_wrong_file_type_is_refused_before_anything_is_stored(): void
    {
        Sanctum::actingAs($this->candidate(), ['job-seeker']);
        $this->flags(ai: true, parser: true);

        $this->postJson('/api/v1/resume/parse', [
            'resume' => UploadedFile::fake()->create('holiday.png', 20, 'image/png'),
        ])->assertStatus(422);
    }

    public function test_an_unauthenticated_upload_is_rejected(): void
    {
        $this->postJson('/api/v1/resume/parse', ['resume' => $this->cv()])
            ->assertUnauthorized();
    }
}
