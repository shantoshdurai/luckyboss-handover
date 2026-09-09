<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;

/**
 * What Lucky AI asks, in what order, and how each answer is offered.
 *
 * This is the Flutter app's onboarding, turned into a conversation. The options
 * come from `WorkTaxonomy` — the app's own `AppData` — not from whatever
 * vacancies happen to be published, which is what the first version did and why
 * it offered a candidate "Healthcare" and then "Healthcare Assistant": our job
 * titles rather than the work people actually do.
 *
 * The app's shape, kept:
 *
 *  - **Category first.** It decides which trades are worth offering next, and
 *    asking for a trade out of one combined list means a scaffolder scrolling
 *    past "Staff Nurse".
 *  - **Nothing has to be typed.** The trade is a tap, the years are a tap, and
 *    the abilities are taps from that trade's own vocabulary. `TradeStep` exists
 *    because the old chip field "does not work for a mason, who does not have a
 *    list of skills in mind" — so the field stayed empty and no employer ever
 *    saw him.
 *  - **Years as buckets, not a number.** Nobody knows whether they have done
 *    seven years or eight, and a keyboard is a wall in front of someone who is
 *    otherwise only tapping. These are the app's own buckets.
 *  - **Abilities narrow to the chosen role**, so a plumber sees pipe fitting and
 *    leak repairs before the rest of the site's work.
 *
 * Answer shapes:
 *   - `confirm` — one chip that moves on.
 *   - `choice`  — a short row of chips.
 *   - `options` — a searchable chip list, collapsed past `preview` entries.
 *   - `multi`   — the same, but pick as many as you like, then Done.
 */
class AgentScript
{
    /** The two ways of telling us what you do. See the `how` question. */
    public const HOW_RESUME = 'Read my CV';
    public const HOW_ASK = 'Ask me the questions';

    public function __construct(private WorkTaxonomy $taxonomy)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function questions(): array
    {
        return [
            'start' => [
                'prompt' => "Let's find work that fits you. Tap the answers — you should not need the keyboard.",
                'type' => 'confirm',
                'confirm_label' => 'Yes, let’s go',
                'field' => null,
            ],

            /*
                The employer is asked how they want to describe a vacancy; this
                is the candidate's half of the same question, and it is the more
                valuable one.

                Everything below this asks a candidate to type or tap out what is
                already written on the CV in their pocket. `ResumeParseController`
                has read documents since it shipped, but it lived behind a card on
                the home screen called "Add my resume" — so the candidate who most
                needs it, the one who has just tapped "Find me a job" and is now
                facing five questions, was never offered it.

                Answering "Read my CV" hands over to the resume screen, which
                extracts, shows the values for checking, and never saves them
                silently. Answering the other way is the flow that was already
                here.

                `options`, not `choice`: `choice` values are validated as
                integers, and these are sentences.
            */
            'how' => [
                'prompt' => 'Shall I read your CV, or would you rather answer a few questions?',
                'help' => 'Reading the CV is faster — I fill in your profile and you check it. Either way you can change anything afterwards.',
                'type' => 'options',
                'panel_title' => 'Choose how',
                'preview' => 2,
                'choices_list' => [self::HOW_RESUME, self::HOW_ASK],
                'field' => null,
            ],

            'category' => [
                'prompt' => 'What work are you looking for?',
                'help' => 'Pick the one closest to your work. The next questions are about it.',
                'type' => 'options',
                'panel_title' => 'Choose a category',
                'preview' => 14,
                'field' => 'preferred_category',
            ],

            'trade' => [
                'prompt' => 'What is your work?',
                'help' => 'Tap what you do. Employers search by this.',
                'type' => 'options',
                'panel_title' => 'Choose your trade',
                'preview' => 10,
                'field' => 'current_title',
            ],

            'experience' => [
                // The app's own buckets, values kept identical so the two agree.
                'prompt' => 'How long have you done this work?',
                'type' => 'choice',
                'choices' => [
                    'No experience' => 0,
                    'Under 1 year' => 1,
                    '1–3 years' => 2,
                    '3–5 years' => 4,
                    '5–10 years' => 7,
                    'Over 10 years' => 12,
                ],
                'field' => 'years_experience',
            ],

            'abilities' => [
                'prompt' => 'What can you do? Pick everything that applies.',
                'help' => 'This is what we match on most.',
                'type' => 'multi',
                'panel_title' => 'Search the work you do',
                'preview' => 10,
                'field' => 'skills',
            ],

            'location' => [
                'prompt' => 'Where can you get to for work?',
                'type' => 'options',
                'panel_title' => 'Choose your area',
                'preview' => 12,
                'field' => 'current_location',
            ],
        ];
    }

    public function question(string $key): ?array
    {
        return $this->questions()[$key] ?? null;
    }

    /**
     * The next question this candidate has not answered in this conversation.
     *
     * Driven by the conversation's own answers rather than the profile, so
     * running the agent again to change something does not skip every question
     * on the way.
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
        $trade = $answers['trade'] ?? null;

        return match ($key) {
            'how' => $this->questions()['how']['choices_list'],
            'category' => $this->taxonomy->categoryNames(),

            // Scoped to the chosen category — the whole reason the category is
            // asked first.
            'trade' => $this->taxonomy->roleNames($category),

            'abilities' => $this->taxonomy->abilitiesFor($category, $trade),

            // The one list that is genuinely ours rather than the app's: places
            // are only worth offering if we have work in them.
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
     * Write one answer to the candidate's profile.
     *
     * The conversation keeps its own copy for the transcript; this is what the
     * matcher actually reads.
     */
    public function apply(User $user, string $key, string $answer): void
    {
        $question = $this->question($key);

        if ($question === null || $question['field'] === null || $answer === '') {
            return;
        }

        $profile = $user->candidateProfile()->firstOrCreate([], ['country_code' => 'SG', 'profile_completion' => 0]);

        if ($question['field'] === 'skills') {
            $skills = array_values(array_filter(array_map('trim', explode(',', $answer))));
            $resumeData = is_array($profile->resume_data) ? $profile->resume_data : [];
            $resumeData['skills'] = $skills;
            // Both places: JobMatchService reads both.
            $profile->update(['skills' => $skills, 'resume_data' => $resumeData]);

            return;
        }

        if ($question['field'] === 'years_experience') {
            $profile->update(['years_experience' => (int) $answer]);

            return;
        }

        $profile->update([$question['field'] => $answer]);
    }

    /**
     * How an answer reads back in the transcript.
     *
     * `experience` is stored as a number but was chosen as a phrase; showing
     * "4" in the bubble when the candidate tapped "3–5 years" would be a small
     * lie about what they said.
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
}
