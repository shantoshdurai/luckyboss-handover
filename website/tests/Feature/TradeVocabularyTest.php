<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\JobCategory;
use App\Services\WorkTaxonomy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One vocabulary, everywhere.
 *
 * There were two. `WorkTaxonomy` — what the Flutter app, the seeker agent and
 * the hiring agent speak — had fourteen categories; the `job_categories` table
 * behind "Browse by trade" had eleven different ones. A candidate could tell
 * the agent "Driving & Delivery" and then find no such thing on the website.
 */
class TradeVocabularyTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_categories_are_exactly_the_taxonomy(): void
    {
        $this->seed();

        $taxonomy = app(WorkTaxonomy::class)->categoryNames();
        $stored = JobCategory::where('is_active', true)->pluck('name')->all();

        sort($taxonomy);
        sort($stored);

        $this->assertSame($taxonomy, $stored);
    }

    public function test_the_website_leads_with_field_trades_not_it(): void
    {
        $this->seed();

        // The taxonomy is a faithful port of the app's file and puts
        // IT & Software second. The home page shows the first eight by sort
        // order, and spec §58/§59 wants all eight to be trades, so the website's
        // running order lives in `JobCategorySeeder` instead of in the taxonomy.
        $firstEight = JobCategory::orderBy('sort_order')->take(8)->pluck('name')->all();

        $this->assertSame('Construction', $firstEight[0]);
        $this->assertNotContains('IT & Software', $firstEight);
        $this->assertNotContains('Finance & Banking', $firstEight);
    }

    public function test_browse_by_trade_names_the_trades_inside_each_category(): void
    {
        $this->seed();

        $this->get(route('categories.index'))
            ->assertOk()
            // The question a visitor arrives with is "is my trade here?", and a
            // card that says only "Construction" leaves them guessing.
            ->assertSee('Mason')
            ->assertSee('Forklift Driver')
            ->assertSee('Security Guard')
            ->assertSee('14 trades');
    }

    public function test_a_trade_with_no_vacancies_says_so_and_still_leads_somewhere(): void
    {
        $this->seed();

        $security = JobCategory::where('name', 'Security')->firstOrFail();
        $this->assertSame(0, $security->jobs()->where('status', 'published')->count());

        $response = $this->get(route('categories.index'));

        // "0 Jobs Available" is the worst thing an empty card can say.
        $response->assertOk()->assertDontSee('0 open');

        // But an empty results page is not better. The card offers to take the
        // visitor to the agent instead, so telling us what they do is the thing
        // that happens when nobody is hiring it yet.
        $response->assertSee(route('register.seeker'));
    }

    public function test_the_count_is_published_vacancies_only(): void
    {
        $this->seed();

        $category = JobCategory::where('name', 'Security')->firstOrFail();
        $company = \App\Models\Company::firstOrFail();

        Job::create([
            'company_id' => $company->id,
            'job_category_id' => $category->id,
            'title' => 'Draft Security Officer',
            'description' => 'Not published yet.',
            'country_code' => 'SG',
            'status' => 'draft',
        ]);

        // The page used to eager-load every job in every category, drafts and
        // closed ones included, so any count it showed was wrong by definition.
        // Asserted on the data rather than the rendered string: other trades do
        // legitimately say "1 open", so the text alone proves nothing here.
        $response = $this->get(route('categories.index'))->assertOk();

        $rendered = $response->viewData('categories')->firstWhere('name', 'Security');

        $this->assertSame(0, (int) $rendered->jobs_count);
    }

    public function test_no_seeded_vacancy_is_filed_in_the_wrong_trade(): void
    {
        $this->seed();

        // These three used to sit under Construction. The seeder looked up
        // category names that did not exist in this table ("IT", "Retail",
        // "Recruitment Agency") and fell back to whatever `first()` returned.
        $expected = [
            'IT Support Specialist' => 'IT & Software',
            'Retail Store Manager' => 'Retail & Sales',
            'Recruitment Consultant' => 'Office & Administration',
            'Logistics Operations Executive' => 'Warehouse & Logistics',
        ];

        foreach ($expected as $title => $category) {
            $this->assertSame(
                $category,
                Job::where('title', $title)->firstOrFail()->jobCategory?->name,
                "{$title} is filed in the wrong trade."
            );
        }
    }
}
