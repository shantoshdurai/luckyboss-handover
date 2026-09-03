<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\AIRecruitmentEngineService;
use App\Services\EmployerAiGate;
use App\Services\RecruitmentLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The AI tools an employer is paying a subscription for.
 *
 * The engine methods behind these have existed for a while but were reachable
 * only from the web portal, so the mobile employer app — the thing the
 * subscription is sold through — had no AI in it at all.
 *
 * Every action here runs through [EmployerAiGate] first. When the gate denies,
 * the request still returns a usable answer built by the rule-based engine,
 * with `ai: false` and `upgrade_required: true` so the app can show what an
 * upgrade would buy. What it does not do is call Gemini: an un-entitled request
 * must not spend the AI budget, and returning a paid result while pretending it
 * was free is worse than refusing.
 */
class EmployerAiController extends Controller
{
    public function __construct(
        private readonly AIRecruitmentEngineService $engine,
        private readonly EmployerAiGate $gate,
        private readonly RecruitmentLetterService $letters,
    ) {
    }

    /**
     * Drafts an offer letter, an interview invitation or a status email.
     *
     * The single biggest time saving in the product: an agency writes these
     * dozens of times a week and they all say nearly the same thing.
     *
     * Nothing is sent. Every letter comes back as a draft for the employer to
     * read, because a wrong salary in an offer letter is a legal problem rather
     * than a typo — and the facts are read from our own records, never from the
     * request body, so a client cannot address somebody else's offer to a
     * candidate of its choosing.
     */
    public function letter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:offer,interview,status'],
            'application_id' => ['required', 'integer'],
            'salary' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'start_date' => ['nullable', 'string', 'max:40'],
            'interview_at' => ['nullable', 'string', 'max:60'],
            'interview_mode' => ['nullable', 'string', 'max:60'],
            'decision' => ['nullable', 'in:progressing,rejected'],
        ]);

        $company = $this->company($request);

        // Scoped to this company's own vacancies. Without the join an employer
        // could draft a letter against a competitor's applicant by guessing an
        // id, and read that candidate's name back in the response.
        $application = JobApplication::where('id', $data['application_id'])
            ->whereHas('job', fn ($q) => $q->where('company_id', $company?->id))
            ->with(['candidate', 'job.company'])
            ->firstOrFail();

        $decision = $this->gate->decide($company);

        $letter = $this->letters->draft(
            $data['type'],
            $application,
            $application->job,
            collect($data)->only(['salary', 'currency', 'start_date', 'interview_at', 'interview_mode', 'decision'])->all(),
            allowAi: $decision['allowed'],
        );

        // Recorded only when the model actually did the work. A template
        // fallback costs us nothing, so charging it against the employer's
        // monthly allowance would be taking something for nothing.
        if ($decision['allowed'] && $letter['provider'] !== 'local_template') {
            $this->gate->record($company, 'letter', $decision['source'], $request->user()?->id);
        }

        return response()->json([
            'status' => 'success',
            'ai' => $decision['allowed'] && $letter['provider'] !== 'local_template',
            'source' => $decision['source'] ?? 'rule-based',
            'provider' => $letter['provider'],
            'upgrade_required' => $decision['upgrade_required'],
            'message' => $decision['reason'],
            'data' => [
                'subject' => $letter['subject'],
                'body' => $letter['body'],
                // Returned so the app can show the employer exactly which facts
                // the draft was built from before they send it.
                'facts' => $letter['facts'],
            ],
        ]);
    }

    /**
     * What this employer's plan unlocks. Drives the locks and upgrade prompts
     * in the portal app; never the authority on access, which is re-checked on
     * every call below.
     */
    public function status(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->gate->summary($this->company($request)),
        ]);
    }

    /**
     * Drafts a vacancy from a title. This is the one that actually saves an
     * employer time — the reason "post a job" stops being a blank page.
     */
    public function jobDescription(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:200'],
        ]);

        $decision = $this->gate->decide($this->company($request));

        $result = $this->engine->generateJobDescription(
            $data['title'],
            $data['category'] ?? '',
            $data['location'] ?? 'Singapore',
            allowAi: $decision['allowed'],
        );

        $this->meter($request, $decision, $result, 'job_description');

        return $this->respond($result, $decision);
    }

    /**
     * Interview questions for one applicant on one vacancy.
     */
    public function interviewQuestions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_id' => ['required', 'integer'],
            'application_id' => ['required', 'integer'],
        ]);

        $company = $this->company($request);
        $job = Job::where('company_id', $company?->id)->findOrFail($data['job_id']);

        // Scoped to this company's own applications: an employer must not be
        // able to read a competitor's candidate by guessing an id.
        $application = JobApplication::where('job_id', $job->id)
            ->with('candidate')
            ->findOrFail($data['application_id']);

        abort_unless($application->candidate !== null, 404);

        $decision = $this->gate->decide($company);

        $result = $this->engine->generateInterviewQuestions(
            $job,
            $application->candidate,
            allowAi: $decision['allowed'],
        );

        $this->meter($request, $decision, $result, 'interview_questions');

        return $this->respond($result, $decision);
    }

    /**
     * Ranks the applicants on one vacancy, best first.
     *
     * Deliberately capped. Scoring every applicant on a popular vacancy through
     * the cloud model would be slow and expensive, and nobody reads past the
     * first screen of a shortlist anyway.
     */
    public function shortlist(Request $request, Job $job): JsonResponse
    {
        $company = $this->company($request);
        abort_unless($company !== null && $job->company_id === $company->id, 404);

        $decision = $this->gate->decide($company);

        $applications = JobApplication::where('job_id', $job->id)
            ->with('candidate.candidateProfile')
            ->latest()
            ->limit(25)
            ->get();

        $ranked = $applications
            ->filter(fn (JobApplication $a) => $a->candidate !== null)
            ->map(function (JobApplication $a) use ($job, $decision) {
                $match = $decision['allowed']
                    ? $this->engine->calculateMatch($job, $a->candidate)
                    : $this->engine->runLocalHeuristicModel($job, $a->candidate, $a->candidate->candidateProfile);

                return [
                    'application_id' => $a->id,
                    'candidate_id' => $a->candidate->id,
                    'name' => $a->candidate->name,
                    'current_title' => $a->candidate->candidateProfile?->current_title,
                    'status' => $a->status,
                    'match' => $match,
                    'score' => (float) ($match['score'] ?? $match['match_percentage'] ?? 0),
                ];
            })
            ->sortByDesc('score')
            ->values();

        return response()->json([
            'status' => 'success',
            'ai' => $decision['allowed'],
            'source' => $decision['source'] ?? 'rule-based',
            'upgrade_required' => $decision['upgrade_required'],
            'message' => $decision['reason'],
            'job' => ['id' => $job->id, 'title' => $job->title],
            'data' => $ranked,
        ]);
    }

    /**
     * One response shape for every AI action, so the app can render "this came
     * from AI" or "this is a template, upgrade for AI" without special-casing
     * each endpoint.
     */
    private function respond(array $result, array $decision): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'ai' => $decision['allowed'],
            'source' => $decision['source'] ?? 'rule-based',
            'provider' => $result['provider'] ?? null,
            'upgrade_required' => $decision['upgrade_required'],
            'message' => $decision['reason'],
            'data' => collect($result)->except('provider')->all(),
        ]);
    }

    /**
     * Counts one AI action, but only when the model produced the answer.
     *
     * The engines report which path they took in `provider`; anything
     * containing "local" means the rule-based fallback ran and no API call was
     * made. Metering that would charge an employer for something we did not
     * spend.
     */
    private function meter(Request $request, array $decision, array $result, string $feature): void
    {
        if (! $decision['allowed']) {
            return;
        }

        if (str_contains((string) ($result['provider'] ?? ''), 'local')) {
            return;
        }

        $this->gate->record($this->company($request), $feature, $decision['source'], $request->user()?->id);
    }

    private function company(Request $request): ?Company
    {
        return $request->user()?->companies()->first();
    }
}
