<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\CandidateContactReveal;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\User;
use App\Services\HiringScript;
use App\Services\JobMatchService;
use App\Services\SiteSettingsService;
use App\Services\SubscriptionEntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * The employer's side of the linear conversation.
 *
 * Same machinery as the candidate's Lucky AI — one question per request,
 * server-rendered, answered by tapping — pointed the other way: the employer
 * describes a role and the agent comes back with real people from our own
 * candidate table, scored by the same engine that scores vacancies for
 * candidates.
 *
 * What it will not do, and the reason the shortlist is honest: it never
 * publishes a vacancy behind the employer's back, it never contacts a candidate
 * on their behalf, and it never shows a match score for someone we cannot
 * actually assess. An empty shortlist is a real answer and is said plainly.
 */
class AgentChatController extends Controller
{
    public function __construct(private HiringScript $script) {}

    /**
     * Start a new conversation.
     *
     * Always new, with one exception — a conversation nobody has answered
     * anything in yet. Hiring for a second role is the normal case here, more so
     * than on the candidate side, so resuming the last one would be actively
     * wrong. Unfinished conversations stay in the History rail.
     */
    public function start(): RedirectResponse
    {
        $user = $this->employer();

        $untouched = AgentConversation::where('user_id', $user->id)
            ->where('intent', 'hire')
            ->where('status', 'open')
            ->get()
            ->first(fn (AgentConversation $c) => empty($c->answers));

        $conversation = $untouched ?? tap(AgentConversation::create([
            'user_id' => $user->id,
            'intent' => 'hire',
            'answers' => [],
            'status' => 'open',
        ]), function (AgentConversation $fresh): void {
            $fresh->say('agent', $this->script->question('start')['prompt'], 'start');
        });

        return redirect()->route('employer.chat.show', $conversation);
    }

    /**
     * What this employer has left of the things the agent can spend.
     *
     * Read live rather than passed around: the shortlist screen can reveal a
     * contact, which spends a credit, and coming back to the conversation with a
     * stale number would be worse than showing none.
     *
     * @return array<string, array<string, mixed>>
     */
    private function usage(User $employer): array
    {
        $company = $this->company($employer);

        return $company
            ? app(SubscriptionEntitlementService::class)->summary($company, 'employer')
            : [];
    }

    public function show(AgentConversation $conversation): View
    {
        $user = $this->employer();
        abort_unless($conversation->user_id === $user->id && $conversation->intent === 'hire', 403);

        $answers = $conversation->answers ?? [];
        $nextKey = $this->script->next($answers);

        return view('employer.agent.chat', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->get(),
            'question' => $nextKey ? $this->script->question($nextKey) : null,
            'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
            'outcome' => $nextKey === null ? $this->outcome($user, $answers) : null,
            'usage' => $this->usage($user),
        ]);
    }

    /**
     * Record one answer.
     *
     * Redirect for a plain form post, JSON for the in-place path — the chat is
     * an ordinary form underneath and has to keep working with scripting off.
     */
    public function answer(Request $request, AgentConversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $this->employer();
        abort_unless($conversation->user_id === $user->id && $conversation->intent === 'hire', 403);

        $answers = $conversation->answers ?? [];
        $key = $this->script->next($answers);

        if ($key === null) {
            return redirect()->route('employer.chat.show', $conversation);
        }

        $question = $this->script->question($key);

        if ($question['type'] === 'confirm') {
            $conversation->say('user', $request->input('answer') ?: 'Yes');
            $this->advance($conversation, $key, '');

            return $this->reply($request, $conversation);
        }

        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:255'],
        ], [
            'answer.required' => 'Pick an answer to carry on.',
        ]);

        $answer = trim($validated['answer']);

        // A chip list that does not constrain what can be submitted is
        // decoration: without these checks anything at all reaches the spec the
        // shortlist is built from.
        if ($question['type'] === 'choice' && ! in_array((int) $answer, array_values($question['choices']), true)) {
            return $this->refuse($request, $conversation, 'Pick one of the answers shown.');
        }

        if ($question['type'] === 'options') {
            $allowed = $this->script->options($key, $answers);

            // `allow_other` is the site location, where typing your own is the
            // point. Everything else must come off the list.
            if (! ($question['allow_other'] ?? false) && $allowed !== [] && ! in_array($answer, $allowed, true)) {
                return $this->refuse($request, $conversation, 'Pick one of the options shown.');
            }
        }

        if ($question['type'] === 'multi') {
            $allowed = $this->script->options($key, $answers);
            $picked = array_values(array_filter(array_map('trim', explode(',', $answer))));

            if ($picked === []) {
                return $this->refuse($request, $conversation, 'Pick at least one.');
            }

            foreach ($picked as $one) {
                if ($allowed !== [] && ! in_array($one, $allowed, true)) {
                    return $this->refuse($request, $conversation, 'Pick from the options shown.');
                }
            }

            $answer = implode(', ', $picked);
        }

        $conversation->say('user', $this->script->label($key, $answer), $key);
        $this->advance($conversation, $key, $answer);

        /*
            The one answer that ends the conversation rather than advancing it.

            The conversation is still written — the row, the question and the
            answer are all saved — so someone who changes their mind finds it
            under "Your recent searches" exactly where they left it, and taps
            the other option.
        */
        if ($key === 'how' && $answer === HiringScript::HOW_FORM) {
            return $this->leaveFor($request, route('employer.jobs.create'));
        }

        return $this->reply($request, $conversation);
    }

    /**
     * Everyone the agent found, on their own page.
     *
     * Recomputed from the conversation's answers rather than stored with it: a
     * shortlist saved on Tuesday and read on Friday would still name the people
     * who had signed up by Tuesday, and would go on claiming a score for a
     * candidate who has since emptied their profile.
     */
    public function shortlist(AgentConversation $conversation, JobMatchService $matcher, SiteSettingsService $settings): View
    {
        $user = $this->employer();
        abort_unless($conversation->user_id === $user->id && $conversation->intent === 'hire', 403);

        $answers = $conversation->answers ?? [];
        $threshold = (int) $settings->matching()['minimum_match_score'];
        $company = $this->company($user);
        $candidates = $this->rank($user, $answers, $matcher, $threshold);

        return view('employer.agent.shortlist', [
            'conversation' => $conversation,
            'summary' => $this->script->summary($answers),
            'answers' => $answers,
            'abilities' => $this->script->abilities($answers),
            'brief' => $this->script->brief($answers),
            'threshold' => $threshold,
            'candidates' => $candidates,
            'company' => $company,
            // Contact details this employer already holds, so a card they have
            // paid for once never asks them to pay for it again.
            'revealed' => $company
                ? CandidateContactReveal::where('company_id', $company->id)
                    ->whereIn('candidate_id', $candidates->pluck('id'))
                    ->pluck('candidate_id')
                    ->all()
                : [],
            // Spec §73: people who applied to this employer are free to reveal,
            // and the button says so rather than quietly not charging.
            'organic' => $company
                ? JobApplication::whereIn('candidate_id', $candidates->pluck('id'))
                    ->whereHas('job', fn ($q) => $q->where('company_id', $company->id))
                    ->pluck('candidate_id')
                    ->unique()
                    ->all()
                : [],
        ]);
    }

    /**
     * Hand the conversation over to another screen.
     *
     * The async path cannot simply return a redirect: the chip submit is a
     * fetch, and a 302 there is followed by fetch itself, so the JSON body would
     * be that page's HTML and the transcript would try to render it. The client
     * is told where to go instead, and does it.
     */
    private function leaveFor(Request $request, string $url): RedirectResponse|JsonResponse
    {
        if (! $request->header('X-Chat-Async')) {
            return redirect()->to($url);
        }

        return response()->json(['redirect' => $url]);
    }

    /**
     * Clear one saved conversation.
     *
     * A real delete, not a hidden flag. The list is headed "carry on where you
     * left off" — a row somebody has cleared is one they have decided they will
     * not carry on with, and keeping it in the table to be filtered out later is
     * how a "clear" that does not clear anything gets built.
     *
     * `agent_messages` is cascaded by the foreign key, so the transcript goes
     * with it.
     */
    public function destroy(Request $request, AgentConversation $conversation): RedirectResponse
    {
        $user = $this->employer();
        abort_unless($conversation->user_id === $user->id && $conversation->intent === 'hire', 403);

        $conversation->delete();

        return back()->with('success', 'Conversation cleared.');
    }

    private function reply(Request $request, AgentConversation $conversation): RedirectResponse|JsonResponse
    {
        if (! $request->header('X-Chat-Async')) {
            return redirect()->route('employer.chat.show', $conversation);
        }

        $conversation->refresh();
        $answers = $conversation->answers ?? [];
        $nextKey = $this->script->next($answers);

        return response()->json([
            'transcript' => view('agent.partials.messages', [
                'messages' => $conversation->messages()->get(),
            ])->render(),
            'control' => view('agent.partials.control', [
                'action' => route('employer.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $nextKey ? $this->script->question($nextKey) : null,
                'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
                'outcome' => $nextKey === null ? $this->outcome($conversation->user, $answers) : null,
                'outcomeView' => 'employer.agent.partials.outcome',
            ])->render(),
            'done' => $nextKey === null,
        ]);
    }

    private function refuse(Request $request, AgentConversation $conversation, string $message): RedirectResponse|JsonResponse
    {
        if (! $request->header('X-Chat-Async')) {
            return back()->withErrors(['answer' => $message]);
        }

        $answers = $conversation->answers ?? [];
        $nextKey = $this->script->next($answers);

        return response()->json([
            'error' => $message,
            'control' => view('agent.partials.control', [
                'action' => route('employer.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $nextKey ? $this->script->question($nextKey) : null,
                'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
                'outcome' => null,
                'outcomeView' => 'employer.agent.partials.outcome',
            ])->render(),
        ]);
    }

    private function advance(AgentConversation $conversation, string $key, string $answer): void
    {
        $answers = $conversation->answers ?? [];
        $answers[$key] = $answer;

        $nextKey = $this->script->next($answers);

        $conversation->update([
            'answers' => $answers,
            'status' => $nextKey === null ? 'done' : 'open',
        ]);

        if ($nextKey !== null) {
            $conversation->say('agent', $this->script->question($nextKey)['prompt'], $nextKey);

            return;
        }

        $conversation->say('agent', $this->outcome($conversation->user, $answers)['message']);
    }

    /**
     * What the agent says at the end: a real count of real people, or an honest
     * note that nobody cleared the bar.
     *
     * @return array{message:string, count:int, threshold:int, conversation:AgentConversation|null}
     */
    private function outcome(User $employer, array $answers): array
    {
        $matcher = app(JobMatchService::class);
        $threshold = (int) app(SiteSettingsService::class)->matching()['minimum_match_score'];

        $count = $this->rank($employer, $answers, $matcher, $threshold)->count();
        $summary = $this->script->summary($answers);

        return [
            'message' => $count === 0
                ? "That is everything I need. Nobody on Lucky Boss is above {$threshold}% for {$summary} right now — post it as a vacancy and I will keep watching as people join."
                : "That is everything I need. I found {$count} ".\Str::plural('person', $count)." above {$threshold}% for {$summary}.",
            'count' => $count,
            'threshold' => $threshold,
        ];
    }

    /**
     * Score every candidate against the described role.
     *
     * @return Collection<int, User>
     */
    private function rank(User $employer, array $answers, JobMatchService $matcher, int $threshold): Collection
    {
        // Nothing to score against until the role itself has been answered.
        if (blank($answers['role'] ?? null)) {
            return collect();
        }

        $spec = $this->script->spec($answers, $this->company($employer));

        $candidates = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'job-seeker'))
            ->with('candidateProfile')
            ->get();

        return $matcher->shortlist($spec, $candidates, $threshold, $this->script->abilities($answers));
    }

    private function company(User $employer): ?Company
    {
        return $employer->companies()->first();
    }

    private function employer(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('employer'), 403);

        return $user;
    }
}
