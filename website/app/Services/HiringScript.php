<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use App\Models\User;

/**
 * What the hiring agent asks an employer, in what order, and how each answer is
 * offered.
 *
 * The employer's half of the conversation sir asked us to match after watching
 * TickBig's Agent Ambo. Theirs is sixteen questions and almost every one of them
 * is typed into a free-text box: name, designation, company, the role, years,
 * salary, city, mandatory skills "with a skill level out of 10", advantage
 * skills, onboarding date, headcount, ideal candidate, email, mobile, then a
 * terms checkbox. Two things about that are worth not copying:
 *
 *  - **It asks who you are.** It has to: their agent is open to anyone who lands
 *    on the page, so it collects a name and a phone number before it can do
 *    anything. Ours runs behind a login. We already know the company, the person
 *    and how to reach them, and asking again would be theatre — five of their
 *    sixteen questions disappear because the employer already signed in.
 *  - **It is a form wearing a chat.** "Python (9), Databricks (7)" typed into a
 *    message box is a worse version of a field. Ours offers the same trade
 *    vocabulary the Flutter app offers candidates (`WorkTaxonomy`), so both
 *    sides of the market are describing the work with the same words — which is
 *    the only reason a match between them means anything.
 *
 * The one place free text survives is the site location, because a new
 * employer's yard is very often nowhere we already have a vacancy. There the
 * chips are a shortcut, not the whole answer.
 *
 * Answer shapes are the shared ones: `confirm`, `choice`, `options`, `multi`,
 * and plain text.
 */
class HiringScript
{
    /** "Any experience" — recorded as an answer, then dropped from the spec. */
    public const EXPERIENCE_ANY = -1;

    /** The two ways of describing a vacancy. See the `how` question. */
    public const HOW_CHAT = 'Talk it through with you';
    public const HOW_FORM = 'I will fill in the form myself';

    public function __construct(private WorkTaxonomy $taxonomy) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function questions(): array
    {
        return [
            'start' => [
                'prompt' => "Tell me who you need and I will go through the people on Lucky Boss and bring back the ones who actually fit. Tap the answers — you should not need the keyboard.",
                'type' => 'confirm',
                'confirm_label' => 'Yes, let’s go',
            ],

            /*
                The fork that used to be two cards on the landing screen.

                "Find me people" and "Post a vacancy" both end in a vacancy with
                candidates against it, so they were never two errands — they were
                one errand and two ways of doing it. A landing screen is the wrong
                place to ask which: nobody can tell from the outside what the
                difference will cost them. Asked here, after the greeting, it is a
                real choice with the conversation already in front of them, and
                the form is one tap away.

                `options` rather than `choice` on purpose: `choice` values are
                validated as integers by the controller, and these two are
                answers a person reads, not numbers.
            */
            'how' => [
                'prompt' => 'Do you want to talk it through, or fill in the form yourself?',
                'help' => 'Talking through is faster and I score the people as we go. The form is better when the advert is already written in your head.',
                'type' => 'options',
                'panel_title' => 'Choose how',
                'preview' => 2,
                'choices_list' => [self::HOW_CHAT, self::HOW_FORM],
            ],

            'category' => [
                'prompt' => 'What kind of work is it?',
                'help' => 'Pick the closest one. Everything after this is about it.',
                'type' => 'options',
                'panel_title' => 'Choose a category',
                'preview' => 14,
            ],

            'role' => [
                'prompt' => 'What is the job?',
                'help' => 'This becomes the vacancy title, so pick what a worker would call it.',
                'type' => 'options',
                'panel_title' => 'Choose the role',
                'preview' => 10,
            ],

            'abilities' => [
                'prompt' => 'What must they be able to do? Pick everything that matters.',
                'help' => 'This is what we match on most. Fewer, truer picks beat a long wish list.',
                'type' => 'multi',
                'panel_title' => 'Search the work',
                'preview' => 10,
            ],

            'experience' => [
                // The same buckets the candidate answers, so the two sides are
                // comparing one scale rather than two.
                'prompt' => 'How much experience do they need?',
                'type' => 'choice',
                'choices' => [
                    'Any experience' => self::EXPERIENCE_ANY,
                    'Under 1 year' => 0,
                    '1–3 years' => 1,
                    '3–5 years' => 3,
                    '5–10 years' => 5,
                    'Over 10 years' => 10,
                ],
            ],

            'location' => [
                'prompt' => 'Where is the work?',
                'help' => 'Tap a place we already hire in, or type your own.',
                'type' => 'options',
                'panel_title' => 'Choose the area',
                'preview' => 12,
                'allow_other' => true,
                'other_label' => 'Somewhere else',
                'placeholder' => 'Town or area, e.g. Jurong East',
            ],

            'headcount' => [
                'prompt' => 'How many people do you need?',
                'type' => 'choice',
                'choices' => [
                    'Just one' => 1,
                    '2 to 5' => 5,
                    '6 to 10' => 10,
                    'More than 10' => 20,
                ],
            ],

            'start_when' => [
                'prompt' => 'When do you need them to start?',
                'type' => 'options',
                'panel_title' => 'Choose a start',
                'preview' => 4,
                'choices_list' => ['Immediately', 'Within 2 weeks', 'Within a month', 'Still planning'],
            ],

            'salary' => [
                // Last, and skippable. A budget is the question employers are
                // most likely to abandon a form over, so nothing that matters
                // sits behind it, and a skipped budget is dropped from the
                // scoring rather than filled in with a number we made up.
                'prompt' => 'What is the monthly budget per person?',
                'help' => 'Numbers only. Skip it and I simply will not score on pay.',
                'type' => 'text',
                'placeholder' => 'e.g. 2800',
                'optional' => true,
                'skip_label' => 'Rather not say',
                'skip_value' => 'Not stated',
            ],
        ];
    }

    public function question(string $key): ?array
    {
        return $this->questions()[$key] ?? null;
    }

    /**
     * The next question not yet answered in this conversation.
     *
     * Driven by the conversation's answers, never by the company record, so
     * running the agent a second time for a different vacancy asks everything
     * again instead of skipping to the end.
     */
    public function next(array $answers): ?string
    {
        foreach (array_keys($this->questions()) as $key) {
            if (! array_key_exists($key, $answers)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * The chips behind one question.
     *
     * @return list<string>
     */
    public function options(string $key, array $answers = []): array
    {
        $category = $answers['category'] ?? null;
        $role = $answers['role'] ?? null;

        return match ($key) {
            'category' => $this->taxonomy->categoryNames(),
            'role' => $this->taxonomy->roleNames($category),
            'abilities' => $this->taxonomy->abilitiesFor($category, $role),
            'how' => $this->questions()['how']['choices_list'],
            'start_when' => $this->questions()['start_when']['choices_list'],

            // Places we already have work. Not the whole answer — `allow_other`
            // is on for exactly this reason — but a useful shortcut, and honest
            // about where Lucky Boss actually operates.
            'location' => Job::where('status', 'published')
                ->pluck('location')
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),

            default => [],
        };
    }

    /**
     * How an answer reads back in the transcript.
     *
     * Experience and headcount are stored as numbers but were chosen as
     * phrases; printing "5" in the bubble when the employer tapped "5–10 years"
     * is a small lie about what they said.
     */
    public function label(string $key, string $answer): string
    {
        $question = $this->question($key);

        if (($question['type'] ?? null) === 'choice') {
            $match = array_search((int) $answer, $question['choices'], true);

            return $match === false ? $answer : $match;
        }

        return $answer;
    }

    /**
     * The answers, turned into a vacancy to score people against.
     *
     * Deliberately **not saved**. The employer has described a role, not posted
     * one, and writing a half-formed draft into the job board on their behalf
     * would put it in front of candidates before anybody chose to publish it.
     * JobMatchService only ever reads a Job, so an unsaved instance scores
     * exactly as a real vacancy would.
     */
    public function spec(array $answers, ?Company $company = null): Job
    {
        $abilities = $this->abilities($answers);

        $job = new Job([
            'title' => (string) ($answers['role'] ?? ''),
            // The scorer reads skills out of the description, because that is
            // where a real vacancy states them. Listing the picked abilities
            // here is the same information in the place it looks.
            'description' => $this->description($answers, $abilities),
            'location' => (string) ($answers['location'] ?? ''),
            'country_code' => $company?->country_code ?: 'SG',
        ]);

        $minimum = $this->minimumYears($answers);
        if ($minimum !== null) {
            $job->experience_min = $minimum;
            // The bucket's own top, so "5–10 years" does not silently become
            // "5 to 8" via the scorer's default span.
            $job->experience_max = $this->maximumYears($minimum);
        }

        $salary = $this->salary($answers);
        if ($salary !== null) {
            $job->salary_max = $salary;
        }

        return $job;
    }

    /**
     * The abilities the employer picked, as a list.
     *
     * @return list<string>
     */
    public function abilities(array $answers): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) ($answers['abilities'] ?? '')))));
    }

    /** The minimum years asked for, or null when any experience will do. */
    public function minimumYears(array $answers): ?int
    {
        if (! array_key_exists('experience', $answers) || $answers['experience'] === '') {
            return null;
        }

        $years = (int) $answers['experience'];

        return $years === self::EXPERIENCE_ANY ? null : $years;
    }

    /** The monthly budget, or null when it was skipped. */
    public function salary(array $answers): ?float
    {
        $raw = trim((string) ($answers['salary'] ?? ''));

        if ($raw === '' || ! preg_match('/\d/', $raw)) {
            return null;
        }

        $digits = (float) preg_replace('/[^0-9.]/', '', $raw);

        return $digits > 0 ? $digits : null;
    }

    public function headcount(array $answers): ?int
    {
        return isset($answers['headcount']) ? (int) $answers['headcount'] : null;
    }

    /**
     * The role as the employer described it, ready to print back.
     *
     * TickBig replaces the finished transcript with a recap exactly like this,
     * and it is the right call: nobody re-reads sixteen chat bubbles to check
     * what they asked for. Ours shows every answer we hold rather than a subset
     * — theirs collects a name, a company and a mobile number and then never
     * shows any of them back, which is a strange thing to do with someone's
     * phone number.
     *
     * Skipped answers are dropped, never printed as a blank row.
     *
     * @return array<string, string>  label => value
     */
    public function brief(array $answers): array
    {
        $rows = [
            'Role' => $answers['role'] ?? null,
            'Kind of work' => $answers['category'] ?? null,
            'Experience' => isset($answers['experience']) ? $this->label('experience', (string) $answers['experience']) : null,
            'Must be able to' => implode(', ', $this->abilities($answers)) ?: null,
            'Where' => $answers['location'] ?? null,
            'How many' => isset($answers['headcount']) ? $this->label('headcount', (string) $answers['headcount']) : null,
            'Starting' => $answers['start_when'] ?? null,
            'Monthly budget' => $this->salary($answers) !== null ? number_format($this->salary($answers)) : null,
        ];

        return array_filter($rows, fn ($value) => filled($value));
    }

    /** A one-line summary for the History rail and the shortlist header. */
    public function summary(array $answers): string
    {
        $role = $answers['role'] ?? null;
        $where = $answers['location'] ?? null;

        if (! $role) {
            return 'Hiring';
        }

        return $where ? "{$role} in {$where}" : (string) $role;
    }

    /**
     * @param  list<string>  $abilities
     */
    private function description(array $answers, array $abilities): string
    {
        $parts = array_filter([
            $answers['role'] ?? null,
            $abilities === [] ? null : implode(', ', $abilities),
            $answers['category'] ?? null,
        ]);

        return implode('. ', $parts);
    }

    private function maximumYears(int $minimum): int
    {
        return match ($minimum) {
            0 => 1,
            1 => 3,
            3 => 5,
            5 => 10,
            default => $minimum + 10,
        };
    }

    /** Who this script is for. */
    public function isEmployer(User $user): bool
    {
        return $user->hasRole('employer');
    }
}
