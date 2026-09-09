<?php

namespace App\Http\Controllers\Seeker;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AutoApplyService;
use App\Services\JobMatchService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Lucky AI — the screen a candidate lands on after signing in.
 *
 * TickBig put an agent shell at the root of the signed-in product: a greeting,
 * four intent cards, and a conversation that asks one question at a time with
 * tappable answers rather than a text box. That last part is the good idea and
 * it is why this is server-rendered: our candidates are on cheap Android phones
 * on site, often one-handed, and tapping a chip beats typing into a chat.
 *
 * Where we differ from them, deliberately:
 *
 *  - **Their agent asks two questions and then charges ₹199.** Ours asks only
 *    for what `JobMatchService::readiness()` says is missing, and stops the
 *    moment it can score you. There is nothing to sell at the end of it; the
 *    payoff is the match list.
 *  - **It never invents an answer.** Every field the agent collects is one the
 *    matcher actually reads. If a candidate skips a question, we say what that
 *    costs rather than filling it in with a default — the flat-45% bug started
 *    life as a helpful default.
 */
class AgentController extends Controller
{
    /**
     * The questions the agent can ask, in the order it asks them.
     *
     * Keyed by the `readiness()` labels, so this list cannot drift from what
     * the matcher actually needs — if readiness stops caring about a field, the
     * agent stops asking for it.
     */
    private const QUESTIONS = [
        'job title' => [
            'field' => 'current_title',
            'prompt' => 'What work do you do?',
            'help' => 'Your trade, in your own words — Electrician, Warehouse Picker, Lorry Driver.',
            'type' => 'text',
            'placeholder' => 'Site Electrician',
        ],
        'skills' => [
            'field' => 'skills',
            'prompt' => 'What are you good at?',
            'help' => 'A few things you can do on site. Separate them with commas. This is what we match on most.',
            'type' => 'text',
            'placeholder' => 'Electrical wiring, Fault finding, Conduit installation',
        ],
        'experience' => [
            'field' => 'years_experience',
            'prompt' => 'How long have you been doing it?',
            'help' => 'Roughly is fine.',
            'type' => 'choice',
            'choices' => ['Less than a year' => 0, '1 to 2 years' => 2, '3 to 5 years' => 4, '6 to 9 years' => 7, '10 years or more' => 10],
        ],
        'location' => [
            'field' => 'current_location',
            'prompt' => 'Where are you now?',
            'help' => 'The city or area you can travel to work from.',
            'type' => 'text',
            'placeholder' => 'Singapore',
        ],
    ];

    public function home(): View
    {
        $user = $this->seeker();
        $readiness = app(JobMatchService::class)->readiness($user);

        return view('seeker.agent.home', [
            'user' => $user,
            'readiness' => $readiness,
            'cards' => $this->cards($user, $readiness),
            'rolling' => $this->rolling($user, $readiness),
            // Real rows now. This list showed an honest "No saved conversations
            // yet" from the day the rail was built until the agent started
            // storing transcripts.
            'history' => \App\Models\AgentConversation::where('user_id', $user->id)
                ->latest('id')->limit(5)->get(),
        ]);
    }

    /**
     * The line that cycles under the greeting, as the public home does.
     *
     * Two different statements depending on what we know, and both are true:
     * once we can score this candidate it rolls the titles of vacancies that
     * actually cleared their threshold; before that it rolls what is open on
     * the board. It never rolls a title that is not a live published vacancy.
     *
     * @return array{label:string, terms:list<string>}
     */
    private function rolling(User $user, array $readiness): array
    {
        if ($readiness['ready']) {
            $matches = app(JobMatchService::class)->rank(
                \App\Models\Job::with('company')->where('status', 'published')->get(),
                $user,
                app(SiteSettingsService::class)->matching()['minimum_match_score']
            )->pluck('title')->unique()->take(6)->values()->all();

            if ($matches !== []) {
                return ['label' => 'Matching you with', 'terms' => $matches];
            }
        }

        return [
            'label' => 'Hiring now for',
            'terms' => \App\Models\Job::where('status', 'published')
                ->orderByDesc('published_at')
                ->pluck('title')->unique()->take(6)->values()->all(),
        ];
    }

    /**
     * The intent cards.
     *
     * TickBig's four change with the account — signed out the first is "Find
     * Clients?", signed in as a student it becomes "Update Service info?".
     * Ours change on the two things that actually gate a candidate's outcome:
     * whether we can score them, and whether auto-apply is working for them.
     * A card that offers what you already have is a card wasted.
     */
    private function cards(User $user, array $readiness): array
    {
        $cards = [];

        // The first card always opens the conversation, whether or not we can
        // already score them: sir's point is that the agent is the way in, not
        // a link to a list. It is a POST because starting a chat writes a row.
        $cards[] = [
            'label' => $readiness['ready'] ? 'Find me better work' : 'Find me a job',
            'sub' => $readiness['ready']
                ? 'Answer a few questions and I will re-rank everything'
                : 'A few quick questions, then your matches',
            'post' => route('seeker.chat.start'),
            'primary' => true,
        ];

        $cards[] = $user->candidateProfile?->resume_file_name
            ? ['label' => 'Update my resume', 'sub' => 'Replace the CV we send with your applications', 'url' => route('seeker.resume.choose')]
            : ['label' => 'Add my resume', 'sub' => 'We read it and fill in your profile', 'url' => route('seeker.resume.choose')];

        $autoApply = app(AutoApplyService::class)->settingsFor($user);
        $platformOn = app(SiteSettingsService::class)->matching()['auto_apply_enabled'];

        $cards[] = $autoApply->enabled && $platformOn
            ? ['label' => 'What auto-apply sent', 'sub' => 'Every run, and what it found', 'url' => route('seeker.auto-apply.edit')]
            : ['label' => 'Apply while I work', 'sub' => 'We apply to matching jobs for you', 'url' => route('seeker.auto-apply.edit')];

        $applications = $user->applications()->count();

        $cards[] = $applications > 0
            ? ['label' => 'My '.$applications.' '.\Str::plural('application', $applications), 'sub' => 'Where each one has got to', 'url' => route('seeker.dashboard', ['tab' => 'applications'])]
            : ['label' => 'Browse everything', 'sub' => 'All open vacancies on Lucky Boss', 'url' => route('jobs.index')];

        return $cards;
    }

    /**
     * Ask the next thing we do not know.
     *
     * There is no fixed step count and no progress bar promising five screens:
     * a candidate whose resume already gave us three of the four answers is
     * asked one question and sent to their matches.
     */
    public function find(): View|RedirectResponse
    {
        $user = $this->seeker();
        $question = $this->nextQuestion($user);

        if ($question === null) {
            return redirect()->route('seeker.resume.matches')
                ->with('success', 'That is everything we need. Here are the jobs that fit you.');
        }

        [$key, $spec] = $question;

        return view('seeker.agent.find', [
            'user' => $user,
            'key' => $key,
            'question' => $spec,
            'answered' => $this->answeredCount($user),
            'remaining' => count($this->missing($user)),
        ]);
    }

    public function answer(Request $request): RedirectResponse
    {
        $user = $this->seeker();
        $question = $this->nextQuestion($user);

        if ($question === null) {
            return redirect()->route('seeker.resume.matches');
        }

        [$key, $spec] = $question;

        // Skipping is allowed, and it says what it costs rather than writing a
        // placeholder. A default here is exactly how every candidate once
        // scored 45% against every vacancy.
        if ($request->boolean('skip')) {
            return redirect()->route('seeker.home')
                ->with('info', 'No problem. Without your '.$key.' we cannot score some vacancies, so your match list will be shorter.');
        }

        $data = $request->validate([
            'answer' => $spec['type'] === 'choice'
                ? ['required', 'integer', 'min:0', 'max:70']
                : ['required', 'string', 'max:255'],
        ], [
            'answer.required' => 'Type an answer, or tap Skip.',
        ]);

        $profile = $user->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'profile_completion' => 0]);

        if ($spec['field'] === 'skills') {
            $skills = array_values(array_filter(array_map('trim', explode(',', $data['answer']))));
            $resumeData = is_array($profile->resume_data) ? $profile->resume_data : [];
            $resumeData['skills'] = $skills;
            // Both places — JobMatchService reads both.
            $profile->update(['skills' => $skills, 'resume_data' => $resumeData]);
        } else {
            $profile->update([$spec['field'] => $data['answer']]);
        }

        return redirect()->route('seeker.agent.find');
    }

    /**
     * The questions the candidate has not actually answered.
     *
     * Deliberately **not** `readiness()['missing']`. The matcher derives its
     * skill keywords from a text blob that includes `current_title`, so a
     * candidate who types "Electrician" and nothing else is reported as having
     * skills. That is reasonable for scoring — a title is real signal — but it
     * is wrong for asking: the agent would fall silent after one answer and the
     * profile's Skills tab would still read "0 added".
     *
     * So this looks at what is actually stored. `readiness()` still decides
     * when we can *score* them, which is what the home screen's first card and
     * the Skip message key off.
     *
     * @return list<string>
     */
    private function missing(User $user): array
    {
        // Queried, not read off the relation: an already-loaded
        // $user->candidateProfile can be stale by the time this runs, and a
        // stale read here makes the agent ask the previous question again and
        // overwrite the answer it just saved.
        $profile = $user->candidateProfile()->first();
        $missing = [];

        foreach (self::QUESTIONS as $key => $spec) {
            $empty = match ($spec['field']) {
                // The stored list, never the derived keywords.
                'skills' => empty($profile?->skills) && empty(data_get($profile?->resume_data, 'skills')),
                // 0 is a real answer — "less than a year" — so only null counts.
                'years_experience' => $profile?->years_experience === null,
                default => blank($profile?->{$spec['field']}),
            };

            if ($empty) {
                $missing[] = $key;
            }
        }

        return $missing;
    }

    /** @return array{0:string, 1:array}|null */
    private function nextQuestion(User $user): ?array
    {
        foreach (array_keys(self::QUESTIONS) as $key) {
            if (in_array($key, $this->missing($user), true)) {
                return [$key, self::QUESTIONS[$key]];
            }
        }

        return null;
    }

    private function answeredCount(User $user): int
    {
        return count(self::QUESTIONS) - count($this->missing($user));
    }

    private function seeker(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('job-seeker'), 403);

        return $user;
    }
}
