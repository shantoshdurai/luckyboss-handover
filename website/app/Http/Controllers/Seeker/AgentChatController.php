<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\User;
use App\Services\AgentScript;
use App\Services\JobMatchService;
use App\Services\SiteSettingsService;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The linear conversation — the part sir said was the main thing.
 *
 * Server-rendered, one question per request. No chat widget, no websocket, no
 * client-side state machine: a candidate on a cheap Android phone with a bad
 * connection gets a page that works, and a failed answer re-renders with the
 * question still there instead of leaving a dead spinner.
 */
class AgentChatController extends Controller
{
    public function __construct(private AgentScript $script)
    {
    }

    /**
     * Start a new conversation.
     *
     * Always a new one. It used to resume whichever conversation was still
     * open, which meant tapping "Find me better work" on the home screen
     * dropped the candidate back into a half-finished chat from an hour ago —
     * the button said it would do something and appeared to do nothing.
     *
     * Resuming is what the History list is for: unfinished conversations are
     * listed there under "Carry on where you left off" and open at the question
     * they stopped on. This is TickBig's split too — "New Chat" always starts
     * one, "History" returns to an old one.
     */
    public function start(): RedirectResponse
    {
        $user = $this->seeker();

        // One exception: a conversation nobody has answered anything in yet.
        // There is nothing to return to and nothing worth listing, so a double
        // tap reuses it rather than filling History with empty rows.
        $untouched = AgentConversation::where('user_id', $user->id)
            ->where('intent', 'find_job')
            ->where('status', 'open')
            ->get()
            ->first(fn (AgentConversation $c) => empty($c->answers));

        $conversation = $untouched ?? tap(AgentConversation::create([
            'user_id' => $user->id,
            'intent' => 'find_job',
            'answers' => [],
            'status' => 'open',
        ]), function (AgentConversation $fresh): void {
            $fresh->say('agent', $this->script->question('start')['prompt'], 'start');
        });

        return redirect()->route('seeker.chat.show', $conversation);
    }

    public function show(AgentConversation $conversation): View|RedirectResponse
    {
        $user = $this->seeker();
        abort_unless($conversation->user_id === $user->id, 403);

        $answers = $conversation->answers ?? [];
        $nextKey = $this->script->next($answers);

        return view('seeker.agent.chat', [
            'conversation' => $conversation,
            'messages' => $conversation->messages()->get(),
            'questionKey' => $nextKey,
            'question' => $nextKey ? $this->script->question($nextKey) : null,
            // Answers so far, so the trade and area lists can be narrowed to
            // the category already chosen.
            'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
            'outcome' => $nextKey === null ? $this->outcome($user) : null,
        ]);
    }

    /**
     * Record one answer.
     *
     * Returns a redirect for a plain form post and JSON for the in-place path,
     * hence the union type — the chat has to keep working with scripting off.
     */
    public function answer(Request $request, AgentConversation $conversation): RedirectResponse|JsonResponse
    {
        $user = $this->seeker();
        abort_unless($conversation->user_id === $user->id, 403);

        $answers = $conversation->answers ?? [];
        $key = $this->script->next($answers);

        if ($key === null) {
            return redirect()->route('seeker.chat.show', $conversation);
        }

        $question = $this->script->question($key);

        // A confirm has nothing to record beyond "we started".
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

        // A `choice` must be one of the offered values, and an `options` answer
        // one of the offered rows. Otherwise the chip list is decoration and
        // anything at all can be written to the profile.
        if ($question['type'] === 'choice' && ! in_array((int) $answer, array_values($question['choices']), true)) {
            return $this->refuse($request, $conversation, 'Pick one of the answers shown.');
        }

        if ($question['type'] === 'options') {
            $allowed = $this->script->options($key, $answers);
            if ($allowed !== [] && ! in_array($answer, $allowed, true)) {
                return $this->refuse($request, $conversation, 'Pick one of the options shown.');
            }
        }

        // Multi-select arrives as a comma-joined list; every part must be one of
        // the offered chips, or the list is decoration and anything at all can
        // be written to the profile.
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
        $this->script->apply($user, $key, $answer);
        $this->advance($conversation, $key, $answer);

        /*
            The one answer that leaves the conversation. The row is still written,
            so a candidate who uploads a CV and comes back finds this exactly
            where they left it and can carry on by tapping the other option.
        */
        if ($key === 'how' && $answer === AgentScript::HOW_RESUME) {
            return $this->leaveFor($request, route('seeker.resume.choose'));
        }

        return $this->reply($request, $conversation);
    }

    /**
     * Answer the caller in whichever way it asked.
     *
     * A browser with JavaScript sends `X-Chat-Async` and gets the rendered
     * transcript and control back as JSON, so the conversation continues in
     * place. Anything else gets the redirect it has always got — the chat is a
     * plain form underneath and must keep working without scripting.
     */
    /**
     * Hand the conversation over to another screen.
     *
     * The async path cannot return a redirect: the chip submit is a fetch, which
     * follows a 302 itself, so the JSON body would be that page's HTML and the
     * transcript would try to render it. The client is told where to go instead.
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
        $user = $this->seeker();
        abort_unless($conversation->user_id === $user->id, 403);

        $conversation->delete();

        return back()->with('success', 'Conversation cleared.');
    }

    private function reply(Request $request, AgentConversation $conversation): RedirectResponse|JsonResponse
    {
        if (! $request->header('X-Chat-Async')) {
            return redirect()->route('seeker.chat.show', $conversation);
        }

        $conversation->refresh();
        $answers = $conversation->answers ?? [];
        $nextKey = $this->script->next($answers);

        return response()->json([
            'transcript' => view('agent.partials.messages', [
                'messages' => $conversation->messages()->get(),
            ])->render(),
            'control' => view('agent.partials.control', [
                'action' => route('seeker.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $nextKey ? $this->script->question($nextKey) : null,
                'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
                'outcome' => $nextKey === null ? $this->outcome($conversation->user) : null,
                'outcomeView' => 'seeker.agent.partials.outcome',
            ])->render(),
            'done' => $nextKey === null,
        ]);
    }

    /** A refused answer, without losing the question the candidate was on. */
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
                'action' => route('seeker.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $nextKey ? $this->script->question($nextKey) : null,
                'options' => $nextKey ? $this->script->options($nextKey, $answers) : [],
                'outcome' => null,
                'outcomeView' => 'seeker.agent.partials.outcome',
            ])->render(),
        ]);
    }

    /**
     * Record the answer and queue the next question into the transcript.
     */
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

        $outcome = $this->outcome($conversation->user);
        $conversation->say('agent', $outcome['message']);
    }

    /**
     * What the agent says at the end.
     *
     * A real count of real matches, or an honest note that nothing cleared the
     * bar. It never claims a number it has not scored — the whole reason
     * JobMatchService refuses thin profiles instead of guessing.
     *
     * @return array{message:string, count:int, threshold:int}
     */
    private function outcome(User $user): array
    {
        $matcher = app(JobMatchService::class);
        $threshold = app(SiteSettingsService::class)->matching()['minimum_match_score'];

        if (! $matcher->readiness($user)['ready']) {
            return [
                'message' => 'Thanks. I still cannot score you accurately enough to rank vacancies — adding your resume is the quickest way to fix that.',
                'count' => 0,
                'threshold' => $threshold,
            ];
        }

        $applied = $user->applications()->pluck('job_id')->all();

        $count = $matcher->rank(
            Job::with('company')->where('status', 'published')->whereNotIn('id', $applied)->get(),
            $user,
            $threshold
        )->count();

        return [
            'message' => $count === 0
                ? "That's everything. Nothing is above {$threshold}% for you right now — I will keep looking as new vacancies come in."
                : "That's everything. I found {$count} ".\Str::plural('job', $count)." above {$threshold}% for you.",
            'count' => $count,
            'threshold' => $threshold,
        ];
    }

    private function seeker(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('job-seeker'), 403);

        return $user;
    }
}
