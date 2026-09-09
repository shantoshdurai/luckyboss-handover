<?php

namespace App\Services;

use App\Models\CandidateResume;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Storing a candidate's CV, and reading it with Gemini when that is switched on.
 *
 * Lifted out of Api\V1\ResumeParseController so the web portal's resume-first
 * onboarding runs the identical pipeline. Two copies of this would drift, and
 * the half that drifted would be the one that starts inventing employment
 * history.
 *
 * The order of operations is the important part and is not an accident:
 * **the document is stored before anything else is attempted**, and storing it
 * never depends on an AI switch, an API key, or a successful parse. Autofill
 * once returned 403 and threw the upload away, so a candidate who attached
 * their CV and was then asked to type everything by hand had every reason to
 * think we had lost it — and the employer never received the CV that was
 * actually sent.
 */
class ResumeIntakeService
{
    /** Gemini's inline-data limit for a single request, with headroom. */
    public const MAX_KB = 4096;

    /**
     * Store the CV, then try to read it.
     *
     * @return array{status:string, message:string, requires_review:bool, data:?array, resume:array{file_name:string, path:?string, url:?string, stored:bool}}
     */
    public function intake(UploadedFile $file, User $user): array
    {
        // Read the bytes up front. store() *moves* the temp file, so anything
        // reading getRealPath() afterwards gets nothing — which would silently
        // break parsing for every upload.
        $contents = @file_get_contents($file->getRealPath()) ?: '';
        $mimeType = $file->getMimeType();

        $stored = $this->store($file, $user);

        if (! $this->autofillAvailable()) {
            return $this->outcome('disabled', 'Your resume has been saved. Autofill is switched off, so please enter your details below.', null, $stored);
        }

        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        if (! $key) {
            Log::warning('[ResumeIntake] parser flag on but no Gemini key configured.');

            return $this->outcome('unavailable', 'Your resume has been saved. Autofill is temporarily unavailable, so please enter your details below.', null, $stored);
        }

        // Metered before the call, not after: the Gemini spend happens whether
        // or not the reply is usable, so recording only successes would
        // understate what this actually costs us. Free and unlimited for the
        // candidate — this never refuses.
        app(SubscriptionEntitlementService::class)->consume($user, 'resume_parse', 1);

        try {
            $extracted = $this->extract($contents, $mimeType, $key, $user);
        } catch (\Throwable $e) {
            Log::warning('[ResumeIntake] '.$e->getMessage());
            $extracted = null;
        }

        if ($extracted === null) {
            return $this->outcome('unreadable', 'Your resume has been saved, but we could not read it automatically. Please enter your details below.', null, $stored);
        }

        return $this->outcome('success', 'We read your resume. Check every field below before saving.', $extracted, $stored);
    }

    /** Both switches are server-side: hiding a button in Flutter is not a control (spec §93). */
    public function autofillAvailable(): bool
    {
        $aiEnabled = FeatureFlag::where('key', 'platform_ai_enabled')->value('is_enabled') ?? true;
        $parserEnabled = FeatureFlag::where('key', 'ai_resume_parser_enabled')->value('is_enabled') ?? false;

        return (bool) $aiEnabled && (bool) $parserEnabled;
    }

    /**
     * Saves the document against the candidate's profile.
     *
     * Failure is logged and swallowed: a storage problem must not turn a working
     * upload into an error the candidate cannot act on, and parsing may still
     * succeed and fill their profile.
     *
     * @return array{file_name:string, path:?string, url:?string, stored:bool}
     */
    public function store(UploadedFile $file, User $user): array
    {
        $originalName = $file->getClientOriginalName();

        try {
            $directory = public_path('uploads/resumes');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $name = 'resume-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension();
            $file->move($directory, $name);
            $path = 'uploads/resumes/'.$name;

            $profile = $user->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'profile_completion' => 0]);
            $profile->forceFill([
                'resume_file_name' => $originalName,
                'resume_path' => $path,
            ])->save();

            // The employer-facing record. Kept alongside the profile columns
            // because the ATS lists documents from here.
            CandidateResume::create([
                'candidate_id' => $user->id,
                'file_path' => $path,
                'file_name' => $originalName,
                'parse_status' => 'uploaded',
            ]);

            return ['file_name' => $originalName, 'path' => $path, 'url' => asset($path), 'stored' => true];
        } catch (\Throwable $e) {
            Log::warning('[ResumeIntake] could not store the resume: '.$e->getMessage());

            return ['file_name' => $originalName, 'path' => null, 'url' => null, 'stored' => false];
        }
    }

    /**
     * @param  array{file_name:string, path:?string, url:?string, stored:bool}  $stored
     */
    private function outcome(string $status, string $message, ?array $data, array $stored): array
    {
        return [
            'status' => $status,
            'message' => $message,
            // Never true without data. The caller must present extracted values
            // as things to check, not as though the candidate typed them.
            'requires_review' => $data !== null,
            'data' => $data,
            'resume' => $stored,
        ];
    }

    /**
     * Sends the document to Gemini's multimodal endpoint and returns structured
     * fields, or null when nothing usable came back.
     */
    public function extract(string $contents, ?string $mimeType, string $key, ?User $user = null): ?array
    {
        $model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));

        $prompt = <<<'PROMPT'
Extract the candidate's details from this resume.

Return ONLY a JSON object, no prose and no markdown fence, with these keys:
  "name": string
  "email": string
  "phone": string
  "current_title": string
  "current_company": string
  "years_experience": integer
  "qualification": one of "Doctorate","Post Graduate","Graduate","Class XII","Class X","Below Class X"
  "course": string
  "passing_year": string
  "current_city": string
  "skills": array of strings, concrete and searchable, no soft-skill filler
  "summary": string, at most 3 sentences

Use an empty string for anything the resume does not state. Never invent a
value: a guessed employer or date is worse than a blank field the candidate
fills in themselves.
PROMPT;

        $response = Http::withoutVerifying()
            ->timeout(45)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".urlencode($key),
                [
                    'contents' => [[
                        'parts' => [
                            ['text' => $prompt],
                            ['inline_data' => ['mime_type' => $mimeType ?: 'application/pdf', 'data' => base64_encode($contents)]],
                        ],
                    ]],
                    // Generous, and deliberately so: gemini-2.5-flash spends part
                    // of its budget on reasoning before emitting output, and a
                    // tight cap truncates the JSON mid-object so the whole reply
                    // parses as nothing. SkillController hit exactly this at 300.
                    'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 2048],
                ]
            );

        // Spec §67: recorded whether or not the reply was usable. The tokens
        // were spent before we found out.
        app(AiUsageRecorder::class)->record(
            feature: 'resume_parse',
            user: $user,
            model: $model,
            promptTokens: (int) $response->json('usageMetadata.promptTokenCount', 0),
            completionTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0),
            status: $response->successful() ? 'success' : 'failed',
        );

        if (! $response->successful()) {
            Log::warning('[ResumeIntake] HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300));

            return null;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $clean = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? '');

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            Log::warning('[ResumeIntake] unparseable reply: '.mb_substr($text, 0, 300));

            return null;
        }

        return $this->normalise($decoded);
    }

    /**
     * Coerces the model's output into the shape we expect.
     *
     * Whitelisting keys matters: without it a hallucinated extra field flows
     * through and, eventually, onto a profile.
     */
    public function normalise(array $raw): array
    {
        $string = fn (string $k) => is_string($raw[$k] ?? null) ? trim($raw[$k]) : '';

        $skills = [];
        if (is_array($raw['skills'] ?? null)) {
            $skills = collect($raw['skills'])
                ->filter(fn ($s) => is_string($s) && trim($s) !== '' && mb_strlen($s) <= 60)
                ->map(fn (string $s) => trim($s))
                ->unique()
                ->take(30)
                ->values()
                ->all();
        }

        return [
            'name' => $string('name'),
            'email' => $string('email'),
            'phone' => $string('phone'),
            'current_title' => $string('current_title'),
            'current_company' => $string('current_company'),
            'years_experience' => (int) ($raw['years_experience'] ?? 0),
            'qualification' => $string('qualification'),
            'course' => $string('course'),
            'passing_year' => $string('passing_year'),
            'current_city' => $string('current_city'),
            'skills' => $skills,
            'summary' => $string('summary'),
        ];
    }
}
