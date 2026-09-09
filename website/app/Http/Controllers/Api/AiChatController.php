<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiIntegration;
use App\Models\FeatureFlag;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $message = trim($request->input('message', ''));
        if (empty($message)) {
            return response()->json(['reply' => 'How can I assist your career or hiring journey today?']);
        }

        // Check if Platform AI is toggled ON in Admin Panel
        $isAiEnabled = FeatureFlag::where('key', 'platform_ai_enabled')->value('is_enabled') ?? true;
        $geminiKey = config('services.gemini.api_key', env('GEMINI_API_KEY'));
        $geminiModel = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
        $openaiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        $groqKey = config('services.groq.api_key', env('GROQ_API_KEY'));

        // ─── 1. If AI Toggle is ON, try Cloud LLM API (Gemini) ───
        if ($isAiEnabled && $geminiKey) {
            try {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key=" . urlencode($geminiKey);
                
                $systemInstruction = "You are Lucky AI, the smart recruitment copilot for the Luckyboss Marketplace (Singapore, Malaysia, India).

STRICT RESPONSE GUIDELINES (Goldilocks Rule - Not too short, not too long):
1. NO FILLER FLUFF: Skip generic pleasantries (e.g. \"That is an excellent question...\"). Jump straight into the answer.
2. CRISP & BALANCED: Structure replies as:
   - 1 direct opening sentence.
   - 3 to 5 concise, actionable bullet points (1-2 lines each) using icons (e.g. • 1️⃣ **Step Name:** ...).
   - 1 short closing next-step sentence.
3. EFFICIENT & COMPLETE: Do not write huge essays, but ALWAYS fully complete every sentence and thought without cutting off.
4. HIGHLIGHT KEYWORDS: Use **bold** for key features, buttons, or locations.";
                
                $response = Http::withoutVerifying()
                    ->timeout(10)
                    ->post($endpoint, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemInstruction]]
                        ],
                        'contents' => [
                            ['parts' => [['text' => $message]]]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.6,
                            'maxOutputTokens' => 600,
                        ]
                    ]);

                // Spec §67: every AI call records its tokens and estimated cost,
                // success or not. The tokens were spent either way, and a log
                // that counts only successes understates what AI costs us —
                // which is the number the pricing decision depends on.
                app(\App\Services\AiUsageRecorder::class)->record(
                    feature: 'ai_chat',
                    user: $request->user(),
                    model: $geminiModel,
                    promptTokens: (int) $response->json('usageMetadata.promptTokenCount', 0),
                    completionTokens: (int) $response->json('usageMetadata.candidatesTokenCount', 0),
                    status: $response->successful() ? 'success' : 'failed',
                );

                if ($response->successful()) {
                    $replyText = $response->json('candidates.0.content.parts.0.text');
                    if (!empty(trim($replyText))) {
                        ApiIntegration::where('key', 'platform_gemini')
                            ->orWhere('key', 'platform_openai')
                            ->increment('usage_count');

                        return response()->json([
                            'reply' => trim($replyText),
                            'actions' => $this->determineContextualActions($message),
                            'engine' => 'gemini_cloud_api_live',
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini Chat API exception: ' . $e->getMessage());
            }
        }

        // ─── 2. Local Intelligent Deterministic Heuristic Engine (Code Script Fallback) ───
        return response()->json($this->runLocalChatScript($message));
    }

    /**
     * Deterministic Local Heuristic NLP Chat Engine
     */
    private function runLocalChatScript(string $message): array
    {
        $q = strtolower($message);
        $reply = "Hello! I am Lucky AI, your recruitment copilot on Luckyboss.\n\nHere is how I can assist you today:\n• 🔍 **Find Verified Jobs:** Browse active openings across Singapore, Malaysia, and India.\n• 📊 **Salary Insights:** See the published pay ranges on our current openings.\n• 📄 **Resume Match Scoring:** Get instant feedback on your profile fit.\n• 🏢 **Employer Hiring:** Post vacancies and reach our verified candidates.";
        $actions = [
            ['label' => 'Explore Jobs', 'url' => route('jobs.index')],
            ['label' => 'Post Vacancy', 'url' => route('register.employer')]
        ];

        if (str_contains($q, 'post') || str_contains($q, 'how do employers post') || str_contains($q, 'post new jobs') || str_contains($q, 'create job')) {
            $reply = "Employers can post new job openings in 3 easy steps:\n\n• 1️⃣ **Sign In:** Log in to your Employer Portal (or create a free company account).\n• 2️⃣ **Create Vacancy:** Click **Post a Job** and enter role details, required skills, salary range, and location.\n• 3️⃣ **Publish & Match:** Review and click **Publish** to immediately reach verified candidates with automated AI fit scoring.\n\nReady to get started?";
            $actions = [
                ['label' => 'Post Vacancy Now', 'url' => route('register.employer')],
                ['label' => 'Employer Pricing', 'url' => route('login')]
            ];
        } elseif (str_contains($q, 'help') || str_contains($q, 'what can you') || str_contains($q, 'features') || str_contains($q, 'things you can')) {
            $reply = "I'm here to streamline your career growth and hiring workflows:\n\n• 🔍 **Job Discovery:** Search verified roles in Logistics, IT, Construction, Healthcare, and Finance.\n• 📄 **AI Resume Scoring:** Analyze and boost your profile match rate for top recruiters.\n• 💼 **Interview Prep:** Access role-specific interview checklists and common questions.\n• 🏢 **Recruiter Tools:** Post vacancies, review applications, and filter talent across Singapore, Malaysia, and India.";
            $actions = [
                ['label' => 'Search All Jobs', 'url' => route('jobs.index')],
                ['label' => 'Update Seeker Profile', 'url' => route('seeker.profile.edit')],
                ['label' => 'Employer Portal', 'url' => route('register.employer')]
            ];
        } elseif (str_contains($q, 'warehouse') || str_contains($q, 'logistics') || str_contains($q, 'supervisor')) {
            $reply = $this->describeOpenings(
                ['warehouse', 'logistics'],
                'Logistics & Warehouse Operations'
            );
            $actions = [
                ['label' => 'View Warehouse Jobs', 'url' => route('jobs.index', ['keyword' => 'Warehouse'])],
                ['label' => 'Browse All Roles', 'url' => route('jobs.index')]
            ];
        } elseif (str_contains($q, 'construction') || str_contains($q, 'engineer') || str_contains($q, 'site')) {
            $reply = $this->describeOpenings(
                ['construction', 'engineer', 'site'],
                'Construction & Engineering'
            );
            $actions = [['label' => 'Explore Construction Roles', 'url' => route('jobs.index', ['keyword' => 'Construction'])]];
        } elseif (str_contains($q, 'resume') || str_contains($q, 'score') || str_contains($q, 'match')) {
            $reply = "To achieve a **90%+ match score** on Luckyboss:\n\n• List 5+ specific technical and operational skills.\n• Detail measurable outcomes from previous employment.\n• Keep your location and salary expectations accurate.\n• Upload a clean PDF version of your resume.";
            $actions = [
                ['label' => 'Update Profile & Resume', 'url' => route('seeker.profile.edit')]
            ];
        } elseif (str_contains($q, 'salary') || str_contains($q, 'paying') || str_contains($q, 'pay')) {
            $reply = $this->describePay();
            $actions = [['label' => 'Browse All Jobs', 'url' => route('jobs.index')]];
        }

        return [
            'reply' => $reply,
            'actions' => $actions,
            'engine' => 'local_heuristic_nlp_script',
        ];
    }

    /**
     * Describes what is actually open in a sector, from the jobs table.
     *
     * This replaces three hardcoded vacancy lists. The worst of them queried
     * the real jobs into `$matchingJobs` and then ignored the result entirely,
     * printing invented roles and locations under the words "We have active
     * openings" — a candidate asking about warehouse work was told about
     * vacancies that did not exist, with an invented pay band attached.
     *
     * When nothing matches, this says so. An empty answer is a true one, and
     * the alternative is what was here before.
     */
    private function describeOpenings(array $keywords, string $sectorLabel): string
    {
        $jobs = Job::where('status', 'published')
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $word) {
                    $query->orWhere('title', 'like', '%' . $word . '%');
                }
            })
            ->take(4)
            ->get();

        if ($jobs->isEmpty()) {
            return "I do not have any **{$sectorLabel}** vacancies open right now.\n\nNew roles are published regularly — browse everything currently live, or tell me another kind of work and I will check.";
        }

        $lines = $jobs->map(function (Job $job) {
            $line = '• **' . $job->title . '**';

            if (! empty($job->location)) {
                $line .= ' — ' . $job->location;
            }

            if ($job->salary_min && $job->salary_max) {
                $line .= ' (' . $job->currency_code . ' '
                    . number_format((float) $job->salary_min) . ' - '
                    . number_format((float) $job->salary_max) . ')';
            }

            return $line;
        })->implode("\n");

        $count = $jobs->count();
        $heading = $count === 1
            ? "There is 1 **{$sectorLabel}** role open right now:"
            : "Here are {$count} **{$sectorLabel}** roles open right now:";

        return $heading . "\n\n" . $lines;
    }

    /**
     * Reports the real published pay range, per currency.
     *
     * Previously asserted a flat "SGD 3,500 to SGD 6,500/month" that was not
     * derived from anything.
     */
    private function describePay(): string
    {
        $bands = Job::where('status', 'published')
            ->whereNotNull('salary_min')
            ->whereNotNull('salary_max')
            ->selectRaw('currency_code, MIN(salary_min) AS low, MAX(salary_max) AS high, COUNT(*) AS total')
            ->groupBy('currency_code')
            ->orderByDesc('total')
            ->take(3)
            ->get();

        if ($bands->isEmpty()) {
            return "None of the currently published roles list a salary range, so I cannot quote one honestly.\n\nOpen a vacancy and the employer's stated package is shown where they have given it.";
        }

        $lines = $bands->map(fn ($band) => '• **' . $band->currency_code . ' '
            . number_format((float) $band->low) . ' - '
            . number_format((float) $band->high) . '**'
        )->implode("\n");

        return "Across the roles currently published with a stated salary:\n\n" . $lines
            . "\n\nRanges are what the employers themselves published, not an estimate.";
    }

    /**
     * Contextual action buttons generator
     */
    private function determineContextualActions(string $message): array
    {
        $q = strtolower($message);
        if (str_contains($q, 'warehouse') || str_contains($q, 'logistics')) {
            return [['label' => 'View Logistics Jobs', 'url' => route('jobs.index', ['keyword' => 'Warehouse'])]];
        }
        if (str_contains($q, 'resume') || str_contains($q, 'profile')) {
            return [['label' => 'Go to Profile', 'url' => route('seeker.profile.edit')]];
        }
        if (str_contains($q, 'employer') || str_contains($q, 'post') || str_contains($q, 'hire')) {
            return [['label' => 'Employer Portal', 'url' => route('register.employer')]];
        }
        return [['label' => 'Explore Jobs', 'url' => route('jobs.index')]];
    }
}