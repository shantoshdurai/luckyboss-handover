<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\JobCategory;
use App\Services\WorkTaxonomy;
use Illuminate\Database\Seeder;

/**
 * `job_categories` is the taxonomy, not a second opinion about it.
 *
 * There were two vocabularies for the same thing. `WorkTaxonomy` — what the
 * Flutter app, the seeker agent and the hiring agent all speak — has fourteen
 * categories. This table had eleven different ones, hand-typed in
 * `DatabaseSeeder`. A candidate could tell the agent "Driving & Delivery" and
 * then find no such thing anywhere on the website, and three seeded vacancies
 * (an IT Support Specialist, a Retail Store Manager and a Recruitment
 * Consultant) were filed under **Construction**, because the seeder looked up
 * category names that did not exist here and fell back to `first()`.
 *
 * Two things this seeder deliberately does not do:
 *
 *  - **It does not reorder the taxonomy.** `WorkTaxonomy` is a faithful port of
 *    the app's `AppData` and must stay one, so it leads with Construction then
 *    IT & Software, exactly as the Dart does. Which categories lead *this
 *    website* is a presentation decision, so it lives here in `sort_order`:
 *    the spec's field trades first, IT and finance last. The home page takes
 *    the first eight, and they are now all trades.
 *  - **It does not recreate rows that only changed name.** A rename is applied
 *    by old slug first, so Warehouse keeps its id and the three vacancies filed
 *    under it. Creating `warehouse-logistics` fresh would have orphaned them.
 */
class JobCategorySeeder extends Seeder
{
    /**
     * The order this website presents its trades in, which is not the order the
     * app stores them in. Spec §58/§59: Lucky Boss is a blue-collar board, so
     * the eight the home page shows are all field work.
     */
    private const ORDER = [
        'Construction',
        'Manufacturing',
        'Warehouse & Logistics',
        'Driving & Delivery',
        'Healthcare & Nursing',
        'Hospitality & F&B',
        'Maid & Caregiver',
        'Cleaning & Facilities',
        'Security',
        'Retail & Sales',
        'Engineering',
        'Office & Administration',
        'IT & Software',
        'Finance & Banking',
    ];

    /**
     * Lucide names, because the website renders Lucide and the taxonomy carries
     * Material names inherited from Flutter. Same categories, two icon sets —
     * mapping here keeps `WorkTaxonomy` a copy of the app's file rather than a
     * fork of it.
     */
    private const ICONS = [
        'Construction' => 'hard-hat',
        'Manufacturing' => 'factory',
        'Warehouse & Logistics' => 'warehouse',
        'Driving & Delivery' => 'truck',
        'Healthcare & Nursing' => 'heart-pulse',
        'Hospitality & F&B' => 'utensils',
        'Maid & Caregiver' => 'house',
        'Cleaning & Facilities' => 'spray-can',
        'Security' => 'shield-check',
        'Retail & Sales' => 'store',
        'Engineering' => 'settings-2',
        'Office & Administration' => 'clipboard-list',
        'IT & Software' => 'code',
        'Finance & Banking' => 'landmark',
    ];

    /**
     * Old slug => the taxonomy name it is now called. Applied before the sync,
     * so the row keeps its id and its vacancies.
     */
    private const RENAMES = [
        'warehouse' => 'Warehouse & Logistics',
        'healthcare' => 'Healthcare & Nursing',
        'hospitality' => 'Hospitality & F&B',
        'domestic-worker' => 'Maid & Caregiver',
        'sales' => 'Retail & Sales',
        'administration' => 'Office & Administration',
    ];

    /**
     * Old slug => the slug that absorbs it. The taxonomy has one
     * "Warehouse & Logistics" where this table had Warehouse *and* Logistics,
     * so the vacancies move across and the emptied row goes.
     */
    private const MERGES = [
        'logistics' => 'warehouse-logistics',
    ];

    public function run(): void
    {
        $this->rename();
        $this->sync();
        $this->merge();
    }

    private function rename(): void
    {
        foreach (self::RENAMES as $oldSlug => $name) {
            $row = JobCategory::where('slug', $oldSlug)->first();
            $newSlug = str($name)->slug()->value();

            // Skip if the rename already happened, or if a row is somehow
            // already sitting on the new slug — renaming onto it would collide
            // and lose whichever one lost the race.
            if ($row === null || JobCategory::where('slug', $newSlug)->exists()) {
                continue;
            }

            $row->update(['name' => $name, 'slug' => $newSlug]);
        }
    }

    private function sync(): void
    {
        $order = array_flip(self::ORDER);

        foreach (app(WorkTaxonomy::class)->categoryNames() as $name) {
            JobCategory::updateOrCreate(
                ['slug' => str($name)->slug()->value()],
                [
                    'name' => $name,
                    'icon' => self::ICONS[$name] ?? 'briefcase',
                    'icon_image_path' => 'images/lucky-boss-logo.png',
                    'sort_order' => ($order[$name] ?? count($order)) + 1,
                    // The home page shows the first eight by sort order, so
                    // every category is eligible and the order alone decides.
                    'show_on_home' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    private function merge(): void
    {
        foreach (self::MERGES as $oldSlug => $intoSlug) {
            $old = JobCategory::where('slug', $oldSlug)->first();
            $into = JobCategory::where('slug', $intoSlug)->first();

            if ($old === null || $into === null) {
                continue;
            }

            Job::where('job_category_id', $old->id)->update(['job_category_id' => $into->id]);
            $old->delete();
        }
    }
}
