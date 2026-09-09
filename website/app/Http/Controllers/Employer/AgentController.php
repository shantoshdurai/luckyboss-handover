<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\AgentConversation;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\View\View;

/**
 * The screen an employer lands on after signing in.
 *
 * The dashboard is still there and still useful — eleven active vacancies and
 * four applications are worth seeing — but it is not the front door any more.
 * An employer arrives wanting one thing, and it is never "read four stat tiles":
 * it is "find me a mason". So the agent owns the first screenful and the
 * dashboard is one tap away, exactly as Lucky AI now owns the candidate's.
 */
class AgentController extends Controller
{
    public function home(): View
    {
        $user = $this->employer();
        $company = $user->companies()->first();

        return view('employer.agent.home', [
            'user' => $user,
            'company' => $company,
            'cards' => $this->cards(),
            'rolling' => $this->rolling(),
            // Unfinished first: a conversation someone walked away from is the
            // one worth offering back. Finished ones are still listed, because
            // a shortlist is worth re-opening.
            'history' => AgentConversation::where('user_id', $user->id)
                ->where('intent', 'hire')
                ->latest()
                ->take(4)
                ->get()
                ->filter(fn (AgentConversation $c) => ! empty($c->answers))
                ->values(),
            'openJobs' => $company
                ? Job::where('company_id', $company->id)->where('status', 'published')->count()
                : 0,
        ]);
    }

    /**
     * The three doors off this screen.
     *
     * There were four, and the first two were the same errand: "Find me people"
     * started the conversation, "Post a vacancy" opened the form, and both of
     * them end in a vacancy with candidates against it. Sir called them one
     * thing, and he is right — the difference is not *what* you are doing, it is
     * *how*, which is a question the agent should ask once you are inside rather
     * than a fork on the landing screen. It asks it second now, right after the
     * greeting, and the form is one tap away from there.
     *
     * They are also all the same colour. The first card used to be navy on a
     * page of white cards, which made the grid look like three cards and a
     * button, and put the darkest object on the screen in the top-left corner
     * where the eye lands first. Emphasis comes from order and from the label
     * now, not from a slab of colour.
     *
     * @return list<array<string, mixed>>
     */
    private function cards(): array
    {
        return [
            [
                'label' => 'Hire for a role',
                'sub' => 'Answer a few taps. I will score everyone on Lucky Boss against it, and write the advert if you want one.',
                'post' => route('employer.chat.start'),
            ],
            [
                'label' => 'Who applied to me',
                'sub' => 'Everyone who came to you directly, and where they are in the pipeline.',
                'url' => route('employer.portal', 'candidates'),
            ],
            [
                'label' => 'My dashboard',
                'sub' => 'Vacancies, applications, interviews and offers.',
                'url' => route('employer.dashboard'),
            ],
        ];
    }

    /**
     * The rolling line under the question.
     *
     * The trades of people who are actually on Lucky Boss, not a marketing list.
     * If we have three masons and no data scientists, an employer should see
     * masons roll past — the line is a claim about our candidate base and it
     * ought to be a true one.
     *
     * @return array{label:string, terms:list<string>}
     */
    private function rolling(): array
    {
        $terms = CandidateProfile::query()
            ->whereNotNull('current_title')
            ->pluck('current_title')
            ->map(fn ($t) => trim((string) $t))
            ->filter()
            ->unique()
            ->take(12)
            ->values()
            ->all();

        return [
            'label' => $terms === [] ? 'Lucky Boss' : 'People on Lucky Boss right now',
            'terms' => $terms,
        ];
    }

    private function employer(): User
    {
        $user = auth()->user();
        abort_unless($user && $user->hasRole('employer'), 403);

        return $user;
    }
}
