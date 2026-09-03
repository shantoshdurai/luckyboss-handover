<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\HandlesApiUploads;
use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resume parsing — POST /api/v1/resume/parse
 *
 * CLAUDE.md has documented this route as shipped since the project began. It
 * never existed. This is it.
 *
 * Two gates, both server-side, because spec §93 is explicit that hiding a button
 * in Flutter is not a control:
 *   1. `platform_ai_enabled` — the master AI switch (spec §3).
 *   2. `ai_resume_parser_enabled` — vision parsing specifically, so an admin can
 *      run the chatbot while leaving document upload off.
 *
 * With either off the endpoint returns 403 and the app falls back to manual
 * entry. It never returns invented data: a resume parser that guesses puts a
 * fabricated work history on a real person's profile and sends it to employers.
 */
class ResumeParseController extends Controller
{
    use HandlesApiUploads;

    /** Gemini's inline-data limit for a single request, with headroom. */
    private const MAX_KB = 4096;

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'resume' => [
                'required', 'file',
                'mimes:pdf,doc,docx',
                'max:'.self::MAX_KB,
            ],
        ], [
            'resume.mimes' => 'Upload a PDF or Word document.',
            'resume.max' => 'That file is too large. Keep it under 4 MB.',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('resume');

        // Read the bytes up front. storePublicUpload() *moves* the temp file,
        // so anything reading $file->getRealPath() afterwards gets nothing —
        // which would have silently broken parsing for every upload.
        $contents = file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType();

        // The document is kept whatever happens next.
        //
        // Autofill used to return 403 and throw the upload away, so a candidate
        // who attached their CV and was then told to type everything by hand
        // had every reason to believe the app had lost it — and the employer
        // never received the CV that was actually sent. Parsing is a
        // convenience on top of storing the file; storing it is the part that
        // must not depend on an AI switch.
        $stored = $this->keepResume($request, $file);

        $aiEnabled = FeatureFlag::where('key', 'platform_ai_enabled')->value('is_enabled') ?? true;
        $parserEnabled = FeatureFlag::where('key', 'ai_resume_parser_enabled')->value('is_enabled') ?? false;

        if (! $aiEnabled || ! $parserEnabled) {
            return response()->json([
                'status' => 'disabled',
                'message' => 'Your resume has been saved. Autofill is switched off, so please enter your details below.',
                'resume' => $stored,
            ]);
        }

        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        if (! $key) {
            Log::warning('[ResumeParse] parser flag on but no Gemini key configured.');

            return response()->json([
                'status' => 'unavailable',
                'message' => 'Your resume has been saved. Autofill is temporarily unavailable, so please enter your details below.',
                'resume' => $stored,
            ]);
        }

        try {
            $extracted = $this->extract($contents, $mimeType, $key);
        } catch (\Throwable $e) {
            Log::warning('[ResumeParse] '.$e->getMessage());
            $extracted = null;
        }

        if ($extracted === null) {
            return response()->json([
                'status' => 'unreadable',
                'message' => 'Your resume has been saved, but we could not read it automatically. Please enter your details below.',
                'resume' => $stored,
            ]);
        }

        return response()->json([
            'status' => 'success',
            // Explicitly flagged as unverified. The client must present these as
            // values to check, not as though the candidate typed them.
            'requires_review' => true,
            'data' => $extracted,
            'resume' => $stored,
        ]);
    }

    /**
     * Saves the document against the candidate's profile and returns what the
     * app needs to show it.
     *
     * Failure here is logged and swallowed: a storage problem must not turn a
     * working upload into an error the candidate cannot act on, and parsing may
     * still succeed and fill their profile.
     *
     * @return array{file_name:string, url:?string, stored:bool}
     */
    private function keepResume(Request $request, UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();

        try {
            $path = $this->storePublicUpload($file, 'resumes', 'resume');

            $profile = $request->user()?->candidateProfile;
            if ($profile !== null) {
                $profile->forceFill([
                    'resume_file_name' => $originalName,
                    'resume_path' => $path,
                ])->save();
            }

            return ['file_name' => $originalName, 'url' => asset($path), 'stored' => true];
        } catch (\Throwable $e) {
            Log::warning('[ResumeParse] could not store the resume: '.$e->getMessage());

            return ['file_name' => $originalName, 'url' => null, 'stored' => false];
        }
    }

    /**
     * Sends the document to Gemini's multimodal endpoint and returns structured
     * fields, or null when nothing usable came back.
     */
    private function extract(string $contents, string $mimeType, string $key): ?array
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
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => base64_encode($contents),
                                ],
                            ],
                        ],
                    ]],
                    // Generous, and deliberately so: gemini-2.5-flash spends part
                    // of its budget on reasoning before emitting output, and a
                    // tight cap truncates the JSON mid-object so the whole reply
                    // parses as nothing. SkillController hit exactly this.
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 2048,
                    ],
                ]
            );

        if (! $response->successful()) {
            Log::warning('[ResumeParse] HTTP '.$response->status().': '.mb_substr($response->body(), 0, 300));

            return null;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $clean = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($text)) ?? '');

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            Log::warning('[ResumeParse] unparseable reply: '.mb_substr($text, 0, 300));

            return null;
        }

        return $this->normalise($decoded);
    }

    /**
     * Coerces the model's output into the shape the app expects.
     *
     * Whitelisting keys matters: without it a hallucinated extra field flows
     * into the client and, eventually, onto a profile.
     */
    private function normalise(array $raw): array
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
