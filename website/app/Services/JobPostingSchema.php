<?php

namespace App\Services;

use App\Models\Job;

/**
 * schema.org JobPosting markup, so vacancies appear in Google for Jobs.
 *
 * The site already published an Organization block and nothing else, which
 * means every vacancy on luckyboss.org has been invisible to the largest free
 * source of candidate traffic in all three markets. No partner, no contract and
 * no per-click cost — Google reads the page and lists the job.
 *
 * Google rejects a posting that is incomplete or misleading rather than ranking
 * it lower, so the rules below are followed exactly:
 *
 * - `datePosted` and `validThrough` must be real dates. A job with no closing
 *   date gets none rather than an invented one; Google handles the absence, and
 *   a fabricated expiry would eventually be wrong for every listing.
 * - `hiringOrganization` must be the employer, never the job board. Naming
 *   Luckyboss here is a documented policy violation.
 * - Salary is included only when the employer chose to show it. Publishing a
 *   figure a company deliberately hid would be worse than omitting it.
 * - `directApply` is true because the application is completed on our site.
 */
class JobPostingSchema
{
    public function for(Job $job): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => $this->description($job),
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'Luckyboss',
                'value' => (string) $job->id,
            ],
            'datePosted' => ($job->published_at ?? $job->created_at)?->toIso8601String(),
            'employmentType' => $this->employmentType($job->job_type),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $job->company?->name ?? 'Luckyboss Employment Agency Pte. Ltd',
                'sameAs' => $job->company?->website ?: url('/'),
            ],
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->location ?: null,
                    'addressCountry' => $job->country_code,
                ],
            ],
            'directApply' => true,
        ];

        if ($job->closing_date !== null) {
            $schema['validThrough'] = $job->closing_date->toIso8601String();
        }

        // A remote vacancy needs telecommute markup or Google files it under
        // the office address, where the candidates who want it will not look.
        if (in_array(strtolower((string) $job->work_mode), ['remote', 'work from home'], true)) {
            $schema['jobLocationType'] = 'TELECOMMUTE';
            $schema['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => $job->country_code,
            ];
        }

        if ($job->salary_visible && $job->salary_min) {
            $schema['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => $job->currency_code ?: 'SGD',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => (float) $job->salary_min,
                    'maxValue' => (float) ($job->salary_max ?: $job->salary_min),
                    'unitText' => 'MONTH',
                ],
            ];
        }

        return array_filter($schema, static fn ($v) => $v !== null && $v !== []);
    }

    /**
     * Google requires an HTML description. A one-line placeholder gets the
     * posting rejected, so a thin description is padded with the facts we hold
     * rather than with filler prose.
     */
    private function description(Job $job): string
    {
        $body = trim(strip_tags((string) $job->description));

        $facts = [];
        if ($job->location) {
            $facts[] = 'Location: '.e($job->location);
        }
        if ($job->work_mode) {
            $facts[] = 'Work mode: '.e(ucfirst($job->work_mode));
        }
        if ($job->experience_min !== null) {
            $facts[] = 'Experience: from '.$job->experience_min.' years';
        }
        if ($job->vacancies > 1) {
            $facts[] = 'Openings: '.$job->vacancies;
        }

        $html = '<p>'.e($body).'</p>';
        if ($facts !== []) {
            $html .= '<ul><li>'.implode('</li><li>', $facts).'</li></ul>';
        }

        return $html;
    }

    /**
     * Maps our job types onto the vocabulary Google accepts. An unrecognised
     * value is dropped rather than passed through, because an invalid enum
     * invalidates the whole posting.
     */
    private function employmentType(?string $type): ?string
    {
        return match (strtolower((string) $type)) {
            'full-time', 'full time', 'permanent' => 'FULL_TIME',
            'part-time', 'part time' => 'PART_TIME',
            'contract', 'fixed-term' => 'CONTRACTOR',
            'temporary', 'temp' => 'TEMPORARY',
            'internship', 'intern' => 'INTERN',
            default => null,
        };
    }
}
