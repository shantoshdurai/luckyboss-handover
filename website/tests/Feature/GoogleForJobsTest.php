<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Services\JobPostingSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Google rejects an incomplete or misleading JobPosting rather than ranking it
 * lower, so the rules it enforces are worth holding in tests.
 *
 * This is the cheapest candidate acquisition the platform has: no partner, no
 * contract and no cost per click. It is only worth anything if the markup is
 * accepted.
 */
class GoogleForJobsTest extends TestCase
{
    use RefreshDatabase;

    private function job(array $attributes = []): Job
    {
        $company = Company::create([
            'name' => 'Tuas Port Logistics',
            'country_code' => 'SG',
            'status' => 'verified',
            'website' => 'https://tuasport.example',
        ]);

        return Job::create(array_merge([
            'company_id' => $company->id,
            'title' => 'Forklift Driver',
            'description' => 'Move pallets and keep the yard tidy.',
            'country_code' => 'SG',
            'location' => 'Tuas',
            'work_mode' => 'on-site',
            'job_type' => 'full-time',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ], $attributes));
    }

    public function test_the_hiring_organisation_is_the_employer_not_the_job_board(): void
    {
        $schema = app(JobPostingSchema::class)->for($this->job());

        // Naming Luckyboss here is a documented Google policy violation and
        // gets the posting removed.
        $this->assertSame('Tuas Port Logistics', $schema['hiringOrganization']['name']);
        $this->assertSame('JobPosting', $schema['@type']);
        $this->assertSame('FULL_TIME', $schema['employmentType']);
    }

    public function test_a_job_with_no_closing_date_has_no_invented_expiry(): void
    {
        $schema = app(JobPostingSchema::class)->for($this->job(['closing_date' => null]));

        // Google copes with a missing validThrough. A fabricated one would
        // eventually be wrong for every listing on the site.
        $this->assertArrayNotHasKey('validThrough', $schema);
        $this->assertNotEmpty($schema['datePosted']);
    }

    public function test_a_hidden_salary_is_not_published(): void
    {
        $withHidden = app(JobPostingSchema::class)->for($this->job([
            'salary_min' => 2200,
            'salary_max' => 2800,
            'currency_code' => 'SGD',
            'salary_visible' => false,
        ]));

        $this->assertArrayNotHasKey('baseSalary', $withHidden,
            'publishing a figure the employer deliberately hid is worse than omitting it');

        $withShown = app(JobPostingSchema::class)->for($this->job([
            'salary_min' => 2200,
            'salary_max' => 2800,
            'currency_code' => 'SGD',
            'salary_visible' => true,
        ]));

        $this->assertSame(2200.0, $withShown['baseSalary']['value']['minValue']);
        $this->assertSame('SGD', $withShown['baseSalary']['currency']);
    }

    public function test_a_remote_job_is_marked_as_telecommute(): void
    {
        $schema = app(JobPostingSchema::class)->for($this->job(['work_mode' => 'remote']));

        // Without this Google files it under the office address, where the
        // candidates who want remote work will not look.
        $this->assertSame('TELECOMMUTE', $schema['jobLocationType']);
    }

    public function test_an_unknown_job_type_is_dropped_rather_than_passed_through(): void
    {
        $schema = app(JobPostingSchema::class)->for($this->job(['job_type' => 'weekend gig']));

        // An invalid enum invalidates the entire posting, so it is better to
        // send no employmentType at all.
        $this->assertArrayNotHasKey('employmentType', $schema);
    }

    public function test_the_markup_is_rendered_on_the_public_job_page(): void
    {
        $job = $this->job();

        $this->get("/jobs/{$job->id}")
            ->assertOk()
            ->assertSee('"@type":"JobPosting"', false)
            ->assertSee('Tuas Port Logistics');
    }

    public function test_the_sitemap_lists_live_jobs_and_excludes_closed_ones(): void
    {
        $live = $this->job();
        $closed = $this->job(['closing_date' => now()->subWeek()]);
        $draft = $this->job(['status' => 'draft']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertHeader('Content-Type', 'application/xml');

        $body = $response->getContent();

        $this->assertStringContainsString("/jobs/{$live->id}", $body);
        // A closed vacancy in a sitemap is a soft 404 to a crawler and costs
        // the whole domain crawl budget.
        $this->assertStringNotContainsString("/jobs/{$closed->id}", $body);
        $this->assertStringNotContainsString("/jobs/{$draft->id}", $body);
    }
}
