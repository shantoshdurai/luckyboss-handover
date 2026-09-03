<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Offer letters, interview invitations and status emails.
 *
 * Named in spec §3 as three separate switchable features, and they are the
 * clearest hour-for-hour saving in the product: an agency writes these dozens
 * of times a week and they all say nearly the same thing.
 *
 * WHY THIS IS ASSEMBLY, NOT GENERATION
 *
 * The candidate's name, the job title, the company, the salary and the start
 * date are already in the database. A model that re-invents them can get them
 * wrong, and a wrong salary in an offer letter is a legal problem, not a typo.
 *
 * So the facts are always injected from our own records, and the model is asked
 * only for the wording around them. Every letter is returned as a draft for the
 * employer to read before sending — nothing here sends anything.
 *
 * When AI is unavailable, or the employer's plan does not include it, the same
 * facts fill a plain template. The letter is less polished and completely
 * correct, which is the right way round.
 */
class RecruitmentLetterService
{
    /** @var array<string,string> spec §3 switch per letter type */
    private const FLAGS = [
        'offer' => 'ai_offer_letter_enabled',
        'interview' => 'ai_interview_letter_enabled',
        'status' => 'ai_email_generator_enabled',
    ];

    public function __construct(private readonly FeatureFlagService $flags)
    {
    }

    /**
     * @param  string  $type  offer | interview | status
     * @param  array<string,mixed>  $terms  salary, start_date, interview_at, mode, decision
     * @return array{subject:string, body:string, provider:string, facts:array}
     */
    public function draft(
        string $type,
        JobApplication $application,
        Job $job,
        array $terms = [],
        bool $allowAi = true,
    ): array {
        $facts = $this->facts($type, $application, $job, $terms);

        $useAi = $allowAi
            && $this->flags->enabled('platform_ai_enabled')
            && $this->flags->enabled(self::FLAGS[$type] ?? 'platform_ai_enabled');

        if ($useAi) {
            $written = $this->write($type, $facts);
            if ($written !== null) {
                return $written + ['facts' => $facts];
            }
        }

        return $this->template($type, $facts) + ['facts' => $facts];
    }

    /**
     * The facts, taken from our records rather than from the request body.
     *
     * A client that could name the candidate could also name somebody else's,
     * and a letter addressed to the wrong person carries a real salary figure
     * out of the building.
     */
    private function facts(string $type, JobApplication $application, Job $job, array $terms): array
    {
        $candidate = $application->candidate;

        return array_filter([
            'candidate_name' => $candidate?->name ?: 'Candidate',
            'job_title' => $job->title,
            'company_name' => $job->company?->name ?? 'Luckyboss',
            'location' => $job->location ?: $job->country_code,
            'currency' => $terms['currency'] ?? $job->currency_code ?? 'SGD',
            'salary' => $terms['salary'] ?? null,
            'start_date' => $terms['start_date'] ?? null,
            'interview_at' => $terms['interview_at'] ?? null,
            'interview_mode' => $terms['interview_mode'] ?? null,
            'decision' => $terms['decision'] ?? null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Asks the model for wording only. Returns null on any failure so the
     * caller falls through to the template — a letter that cannot be written is
     * still a letter that must exist.
     */
    private function write(string $type, array $facts): ?array
    {
        $key = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        if (! $key) {
            return null;
        }

        $model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));

        $brief = match ($type) {
            'offer' => 'a job offer letter',
            'interview' => 'an interview invitation',
            default => 'a short application status email',
        };

        $prompt = "Write {$brief} for a recruitment agency in Southeast Asia.\n"
            ."Use ONLY these facts and do not invent any others:\n"
            .json_encode($facts, JSON_PRETTY_PRINT)."\n\n"
            ."Rules:\n"
            ."- Plain, warm, professional English. Short sentences.\n"
            ."- Many readers are blue-collar workers reading English as a second language. No jargon.\n"
            ."- Do not state a salary, date or company name that is not in the facts above.\n"
            ."- Do not add legal clauses, terms and conditions, or signatures.\n"
            ."- End without a sign-off name; the employer adds their own.\n\n"
            .'Respond ONLY with valid JSON: {"subject": "...", "body": "..."}';

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".urlencode($key);

            $response = Http::withoutVerifying()->timeout(20)->post($endpoint, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    // Letters must not drift. Low temperature keeps the model
                    // close to the facts it was given.
                    'temperature' => 0.3,
                    // Generous: gemini-2.5-flash spends part of its allowance on
                    // internal reasoning before emitting anything, and a tight
                    // cap truncates the JSON mid-string so it parses as nothing.
                    'maxOutputTokens' => 2048,
                ],
            ]);

            if (! $response->successful()) {
                return null;
            }

            $raw = (string) $response->json('candidates.0.content.parts.0.text');
            if (preg_match('/\{[\s\S]*\}/', $raw, $m) !== 1) {
                return null;
            }

            $json = json_decode($m[0], true);
            if (! is_array($json) || blank($json['body'] ?? null)) {
                return null;
            }

            return [
                'subject' => (string) ($json['subject'] ?? $this->subject($type, $facts)),
                'body' => (string) $json['body'],
                'provider' => 'gemini_cloud_api',
            ];
        } catch (\Throwable $e) {
            Log::warning('[RecruitmentLetter] '.$e->getMessage());

            return null;
        }
    }

    /**
     * The same facts in a plain letter. Less polished, entirely correct, and
     * always available — including on a plan with no AI at all.
     */
    private function template(string $type, array $facts): array
    {
        $name = $facts['candidate_name'];
        $title = $facts['job_title'];
        $company = $facts['company_name'];

        $body = match ($type) {
            'offer' => $this->paragraphs([
                "Dear {$name},",
                "We are pleased to offer you the position of {$title} at {$company}"
                    .(isset($facts['location']) ? " in {$facts['location']}" : '').'.',
                isset($facts['salary'])
                    ? "Your salary will be {$facts['currency']} {$facts['salary']} per month."
                    : null,
                isset($facts['start_date'])
                    ? "We would like you to start on {$facts['start_date']}."
                    : null,
                'Please reply to confirm that you accept this offer. If you have any questions, contact us and we will help.',
                'We look forward to working with you.',
            ]),
            'interview' => $this->paragraphs([
                "Dear {$name},",
                "Thank you for applying for the {$title} role at {$company}. We would like to meet you.",
                isset($facts['interview_at'])
                    ? "Your interview is on {$facts['interview_at']}."
                    : 'We will contact you shortly with a date and time.',
                isset($facts['interview_mode'])
                    ? "The interview will be held: {$facts['interview_mode']}."
                    : null,
                'Please reply to confirm that you can attend. If the time does not suit you, tell us and we will arrange another.',
            ]),
            default => $this->paragraphs([
                "Dear {$name},",
                "Thank you for your interest in the {$title} role at {$company}.",
                ($facts['decision'] ?? null) === 'rejected'
                    ? 'On this occasion we have decided to move forward with other candidates. We will keep your profile on file and contact you when a suitable role opens.'
                    : 'Your application is progressing and we will be in touch with the next step shortly.',
                'Thank you for your time.',
            ]),
        };

        return [
            'subject' => $this->subject($type, $facts),
            'body' => $body,
            'provider' => 'local_template',
        ];
    }

    private function subject(string $type, array $facts): string
    {
        return match ($type) {
            'offer' => "Job offer: {$facts['job_title']} at {$facts['company_name']}",
            'interview' => "Interview invitation: {$facts['job_title']} at {$facts['company_name']}",
            default => "Your application for {$facts['job_title']}",
        };
    }

    /** @param array<int,?string> $lines */
    private function paragraphs(array $lines): string
    {
        return implode("\n\n", array_filter($lines, static fn ($l) => $l !== null && $l !== ''));
    }
}
