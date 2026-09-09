<?php

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_category_links_and_search_contract_render(): void
    {
        $this->seed();
        $category = Job::with('jobCategory')->where('status', 'published')->firstOrFail()->jobCategory;
        // The home page links into categories but no longer carries a search
        // field of its own — sir had it removed, because asking a visitor to
        // type before the page has told them anything competed with the four
        // doors sitting directly beneath it for the same decision.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('category='.$category->id, false)
            ->assertDontSee('name="keyword"', false)
            ->assertSee('Pick one and we will take you straight to it');

        // Search itself is unchanged; it lives on the jobs page, where someone
        // has already said they are looking for work.
        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee('name="keyword"', false)
            ->assertSee('action="'.route('jobs.index').'"', false);

        $this->get(route('jobs.index', ['category' => $category->id]))->assertOk()->assertSee($category->name);
    }

    public function test_job_title_search_and_autocomplete_return_published_jobs(): void
    {
        $this->seed();
        $this->get(route('jobs.index', ['keyword' => 'Warehouse Supervisor']))->assertOk()->assertSee('Warehouse Supervisor');
        $this->getJson(route('jobs.suggestions', ['q' => 'Warehouse']))->assertOk()->assertJsonFragment(['Warehouse Supervisor']);
        $this->getJson(route('jobs.suggestions', ['field' => 'location', 'q' => 'Sing']))->assertOk()->assertJsonFragment(['Singapore']);
    }
}
