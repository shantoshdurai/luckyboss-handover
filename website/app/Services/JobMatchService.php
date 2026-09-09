<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Honest, cheap job-to-candidate matching for *lists*.
 *
 * This exists alongside AIRecruitmentEngineService, which stays the engine for
 * the single-job deep view (it calls a cloud LLM and returns a written
 * rationale). That engine cannot rank a list: one HTTP round trip per job means
 * a seeker with 200 open vacancies waits for 200 Gemini calls. This one is pure
 * PHP and runs over a whole collection in memory.
 *
 * Two rules it is built around, both of which the previous scoring broke:
 *
 *   1. A score is refused, not guessed. score() returns null when we do not
 *      know enough about the candidate to compare them to anything. The old
 *      model defaulted missing experience to "1 year", awarded points for a
 *      salary neither side had stated, and then clamped with max(45, ...) - so
 *      a candidate who had typed nothing at all still scored 45% against every
 *      vacancy on the platform, and the seeker dashboard presented those as
 *      "Curated ... based on your location and background". None of it was true.
 *
 *   2. Only what can be assessed is weighted. A dimension with no data on one
 *      side is dropped and its weight redistributed, rather than silently
 *      paying out points. A job that publishes no salary should neither help
 *      nor hurt the match.
 *
 * The consequence is that scores here can be genuinely low, which is the whole
 * point: an admin threshold of "show me jobs above 80%" is meaningless if the
 * scorer cannot produce a 30%.
 */
class JobMatchService
{
    /** Relative weight of each dimension when it can be assessed at all. */
    private const WEIGHTS = [
        'skills' => 40,
        'experience' => 25,
        'location' => 15,
        'title' => 10,
        'salary' => 10,
    ];

    /**
     * Below this much assessable weight we are not matching, we are guessing.
     * 40 is one full dimension: skills alone, or experience plus location.
     */
    private const MIN_ASSESSABLE_WEIGHT = 40;

    private const STOPWORDS = [
        'with', 'and', 'the', 'for', 'our', 'you', 'this', 'that', 'from', 'have',
        'will', 'must', 'work', 'year', 'years', 'team', 'looking', 'role', 'able',
        'good', 'strong', 'excellent', 'required', 'preferred', 'candidate', 'job',
        'company', 'position', 'responsibilities', 'requirements', 'experience',
    ];

    /**
     * Can this candidate be matched at all?
     *
     * Called before a list is rendered so the UI can ask for what is missing
     * instead of showing an unranked dump of every open vacancy.
     *
     * @return array{ready:bool, missing:list<string>, have:list<string>}
     */
    public function readiness(User $candidate): array
    {
        $profile = $candidate->candidateProfile;

        $checks = [
            'skills' => count($this->candidateSkills($profile)) > 0,
            'job title' => filled($profile?->current_title),
            'experience' => $profile?->years_experience !== null,
            'location' => filled($profile?->current_location) || filled($profile?->country_code),
        ];

        $have = array_keys(array_filter($checks));
        $missing = array_keys(array_filter($checks, fn ($ok) => ! $ok));

        // Skills alone are enough to start; so is a title plus one other signal.
        $ready = $checks['skills'] || ($checks['job title'] && count($have) >= 2);

        return ['ready' => $ready, 'missing' => array_values($missing), 'have' => array_values($have)];
    }

    /**
     * Score one vacancy against one candidate, or null when there is not enough
     * to compare.
     *
     * @return null|array{score:int, confidence:int, strengths:list<string>, gaps:list<string>, assessed:list<string>}
     */
    public function score(Job $job, User $candidate, ?CandidateProfile $profile = null): ?array
    {
        $profile ??= $candidate->candidateProfile;

        $dimensions = array_filter([
            'skills' => $this->scoreSkills($job, $profile),
            'experience' => $this->scoreExperience($job, $profile),
            'location' => $this->scoreLocation($job, $profile),
            'title' => $this->scoreTitle($job, $profile),
            'salary' => $this->scoreSalary($job, $profile),
        ]);

        $assessableWeight = 0;
        foreach (array_keys($dimensions) as $name) {
            $assessableWeight += self::WEIGHTS[$name];
        }

        if ($assessableWeight < self::MIN_ASSESSABLE_WEIGHT) {
            return null;
        }

        $earned = 0.0;
        $strengths = [];
        $gaps = [];

        foreach ($dimensions as $name => $result) {
            $earned += $result['ratio'] * self::WEIGHTS[$name];

            if ($result['ratio'] >= 0.6) {
                $strengths[] = $result['note'];
            } else {
                $gaps[] = $result['note'];
            }
        }

        // How much of the candidate we could actually see.
        $confidence = (int) round($assessableWeight / array_sum(self::WEIGHTS) * 100);

        // No floor — a bad match is allowed to look like one — but there is a
        // ceiling, and it rises with the evidence. Without this a candidate who
        // has only stated a location and a number of years scores 100% on a job
        // in their city: perfect on both things we checked, and we checked
        // almost nothing. The cap runs from 70% at the minimum assessable
        // weight up to 100% when every dimension was compared, so a headline
        // match figure is always backed by enough of a profile to mean it.
        $raw = $earned / $assessableWeight * 100;
        $ceiling = 50 + ($confidence / 2);

        return [
            'score' => (int) round(min($raw, $ceiling)),
            // Shown to the seeker so a 92% built from one dimension is not
            // mistaken for a 92% built from five.
            'confidence' => $confidence,
            'strengths' => $strengths,
            'gaps' => $gaps,
            'assessed' => array_keys($dimensions),
        ];
    }

    /**
     * Score a whole collection and return it ranked, highest first.
     *
     * Jobs that cannot be scored are dropped rather than ordered arbitrarily -
     * an unscored job in a ranked list is indistinguishable from a bad match.
     *
     * @param  Collection<int, Job>  $jobs
     * @return Collection<int, Job>  each carrying match_score / match_confidence / match_strengths
     */
    public function rank(Collection $jobs, User $candidate, int $minimumScore = 0): Collection
    {
        $profile = $candidate->candidateProfile;

        return $jobs
            ->map(function (Job $job) use ($candidate, $profile): ?Job {
                $result = $this->score($job, $candidate, $profile);
                if ($result === null) {
                    return null;
                }

                $job->match_score = $result['score'];
                $job->match_confidence = $result['confidence'];
                $job->match_strengths = $result['strengths'];
                $job->match_gaps = $result['gaps'];

                return $job;
            })
            ->filter()
            ->filter(fn (Job $job) => $job->match_score >= $minimumScore)
            ->sortByDesc('match_score')
            ->values();
    }

    /**
     * The same scoring, read the other way round: people for one vacancy.
     *
     * The employer's hiring agent needs this. It is deliberately the identical
     * engine rather than a second one — a shortlist built by different rules to
     * the candidate's match list would let an employer and a candidate see two
     * different numbers for the same pairing, and one of them would be wrong.
     *
     * `$job` may be an unsaved instance: the agent describes a role before
     * anybody has published it, and nothing here touches the database.
     *
     * @param  Collection<int, User>  $candidates
     * @param  list<string>  $wanted  the abilities the employer asked for
     * @return Collection<int, User>  each carrying match_score / match_confidence
     *                                / match_have / match_missing
     */
    public function shortlist(Job $job, Collection $candidates, int $minimumScore = 0, array $wanted = []): Collection
    {
        // Kept in the employer's own casing; only the comparison is folded.
        $asked = collect($wanted)
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->values();

        return $candidates
            ->map(function (User $candidate) use ($job, $asked): ?User {
                $profile = $candidate->candidateProfile;
                $result = $this->score($job, $candidate, $profile);

                if ($result === null) {
                    return null;
                }

                $has = collect($this->candidateSkills($profile))->map(fn ($s) => strtolower($s));

                // Stated in the employer's own words, not the scorer's. Its
                // notes are written to a candidate ("your 9 years fits"), and
                // reading those back to a hiring manager would be nonsense.
                $candidate->match_score = $result['score'];
                $candidate->match_confidence = $result['confidence'];
                $candidate->match_assessed = $result['assessed'];
                $evidenced = function (string $skill) use ($has): bool {
                    $needle = strtolower($skill);

                    return $has->contains(fn (string $their) => str_contains($their, $needle) || str_contains($needle, $their));
                };

                $candidate->match_have = $asked->filter($evidenced)->values()->all();
                $candidate->match_missing = $asked->reject($evidenced)->values()->all();

                return $candidate;
            })
            ->filter()
            ->filter(fn (User $candidate) => $candidate->match_score >= $minimumScore)
            ->sortByDesc('match_score')
            ->values();
    }

    // ---------------------------------------------------------------- dimensions
    //
    // Each returns ['ratio' => 0.0..1.0, 'note' => string] or null when the data
    // to judge it is absent on either side.

    /** @return null|array{ratio:float, note:string} */
    private function scoreSkills(Job $job, ?CandidateProfile $profile): ?array
    {
        // Note there is no `requirements` column on jobs — the table carries
        // title and description only, and an earlier draft of this method read
        // $job->requirements, which is always null. Everything a vacancy says
        // about the work is in those two fields.
        $titleTerms = $this->keywords((string) $job->title);
        $bodyTerms = $this->keywords(strip_tags((string) $job->description));
        $candidateSkills = $this->candidateSkills($profile);

        if (($titleTerms === [] && $bodyTerms === []) || $candidateSkills === []) {
            return null;
        }

        $titleHits = array_values(array_intersect($titleTerms, $candidateSkills));
        $bodyHits = array_values(array_intersect($bodyTerms, $candidateSkills));

        // The title is the most reliable statement of what the job is —
        // "Warehouse Coordinator" says more in two words than a paragraph of
        // boilerplate about being a dynamic team player — so it carries half
        // the dimension on its own.
        $titleRatio = $titleTerms === [] ? null : count($titleHits) / count($titleTerms);

        // The body is measured against the smaller of the two vocabularies,
        // capped at 6. Dividing by the job's whole word count punished the
        // candidate for the employer's verbosity: a 400-word description made a
        // perfect match unreachable no matter what the candidate could do.
        $bodyDenominator = max(1, min(count($bodyTerms), count($candidateSkills), 6));
        $bodyRatio = min(1.0, count($bodyHits) / $bodyDenominator);

        $ratio = $titleRatio === null
            ? $bodyRatio
            : (0.5 * $titleRatio) + (0.5 * $bodyRatio);

        $matched = array_values(array_unique(array_merge($titleHits, $bodyHits)));

        return [
            'ratio' => min(1.0, $ratio),
            'note' => $matched === []
                ? 'No overlap with the skills this job asks for'
                : 'Matches your '.implode(', ', array_slice(array_map('ucfirst', $matched), 0, 4)),
        ];
    }

    /** @return null|array{ratio:float, note:string} */
    private function scoreExperience(Job $job, ?CandidateProfile $profile): ?array
    {
        // Not defaulted. An unanswered experience question is unanswered.
        $candidateYears = $profile?->years_experience;
        $minimum = $job->experience_min;
        $maximum = $job->experience_max;

        if ($candidateYears === null || ($minimum === null && $maximum === null)) {
            return null;
        }

        $candidateYears = (int) $candidateYears;
        $minimum = (int) ($minimum ?? 0);
        $maximum = $maximum === null ? $minimum + 3 : (int) $maximum;

        if ($candidateYears >= $minimum && $candidateYears <= $maximum) {
            return ['ratio' => 1.0, 'note' => "Your {$candidateYears} years fits the {$minimum}-{$maximum} year range"];
        }

        if ($candidateYears < $minimum) {
            $short = $minimum - $candidateYears;

            return [
                // Each year short costs a quarter of the dimension.
                'ratio' => max(0.0, 1.0 - ($short * 0.25)),
                'note' => "Asks for {$minimum} years, you have {$candidateYears}",
            ];
        }

        // Over-qualified is a mild penalty, not a disqualification.
        return ['ratio' => 0.75, 'note' => "You have more experience ({$candidateYears} yrs) than this asks for"];
    }

    /** @return null|array{ratio:float, note:string} */
    private function scoreLocation(Job $job, ?CandidateProfile $profile): ?array
    {
        $jobCountry = strtolower(trim((string) $job->country_code));
        $candidateCountry = strtolower(trim((string) $profile?->country_code));
        $jobCity = strtolower(trim((string) $job->location));
        $candidateCity = strtolower(trim((string) ($profile?->current_location ?: $profile?->preferred_location)));

        if (($jobCountry === '' && $jobCity === '') || ($candidateCountry === '' && $candidateCity === '')) {
            return null;
        }

        if ($jobCity !== '' && $candidateCity !== ''
            && (str_contains($candidateCity, $jobCity) || str_contains($jobCity, $candidateCity))) {
            return ['ratio' => 1.0, 'note' => "In {$job->location}, where you are"];
        }

        if ($jobCountry !== '' && $jobCountry === $candidateCountry) {
            return ['ratio' => 0.7, 'note' => 'Same country, different city'];
        }

        if ($jobCountry !== '' && $candidateCountry !== '' && $jobCountry !== $candidateCountry) {
            // Someone who said they will relocate is not penalised as hard.
            return $profile?->open_to_relocate === true
                ? ['ratio' => 0.6, 'note' => 'Overseas, and you are open to relocating']
                : ['ratio' => 0.15, 'note' => 'In a different country'];
        }

        return ['ratio' => 0.5, 'note' => 'Location partly matches'];
    }

    /** @return null|array{ratio:float, note:string} */
    private function scoreTitle(Job $job, ?CandidateProfile $profile): ?array
    {
        $candidateTitle = strtolower(trim((string) $profile?->current_title));
        if ($candidateTitle === '' || trim((string) $job->title) === '') {
            return null;
        }

        $tokens = array_filter(
            preg_split('/\W+/', strtolower($job->title)) ?: [],
            fn (string $t) => strlen($t) > 3 && ! in_array($t, self::STOPWORDS, true)
        );

        if ($tokens === []) {
            return null;
        }

        $hits = 0;
        foreach ($tokens as $token) {
            if (str_contains($candidateTitle, $token)) {
                $hits++;
            }
        }

        return [
            'ratio' => min(1.0, $hits / min(count($tokens), 3)),
            'note' => $hits > 0
                ? 'Close to your current role'
                : 'A different role to your current one',
        ];
    }

    /** @return null|array{ratio:float, note:string} */
    private function scoreSalary(Job $job, ?CandidateProfile $profile): ?array
    {
        $expected = (float) ($profile?->expected_salary ?? 0);
        $budgetMax = (float) ($job->salary_max ?? 0);

        // Both sides must have stated a number. Previously a job with no
        // published salary handed out 8 of 10 points to everyone.
        if ($expected <= 0 || $budgetMax <= 0) {
            return null;
        }

        if ($expected <= $budgetMax) {
            return ['ratio' => 1.0, 'note' => 'Pays what you are asking for'];
        }

        $overshoot = ($expected - $budgetMax) / $budgetMax;

        return [
            'ratio' => max(0.0, 1.0 - min(1.0, $overshoot * 2)),
            'note' => 'Pays below what you are asking for',
        ];
    }

    // ------------------------------------------------------------------ helpers

    /**
     * Everything we know the candidate can do, as normalised keywords.
     *
     * Reads the `skills` column first - the onboarding wizard and the resume
     * parser both write there, and the old engine looked only at `resume_data`,
     * so a candidate who picked their skills by hand in the app was matched as
     * though they had none.
     *
     * @return list<string>
     */
    private function candidateSkills(?CandidateProfile $profile): array
    {
        if ($profile === null) {
            return [];
        }

        $text = $profile->current_title.' '.$profile->professional_summary.' '.$profile->headline;

        foreach ([$profile->skills, data_get($profile->resume_data, 'skills')] as $skills) {
            if (is_array($skills)) {
                $text .= ' '.implode(' ', array_map(
                    fn ($s) => is_array($s) ? (string) ($s['name'] ?? '') : (string) $s,
                    $skills
                ));
            } elseif (is_string($skills)) {
                $text .= ' '.$skills;
            }
        }

        foreach ((array) data_get($profile->resume_data, 'experience', []) as $entry) {
            $text .= ' '.data_get($entry, 'title', '').' '.data_get($entry, 'description', '');
        }

        return $this->keywords($text);
    }

    /** @return list<string> */
    private function keywords(string $text): array
    {
        preg_match_all('/[a-z]{4,}/', strtolower(strip_tags($text)), $matches);

        return array_values(array_unique(array_filter(
            $matches[0] ?? [],
            fn (string $word) => ! in_array($word, self::STOPWORDS, true)
        )));
    }
}
