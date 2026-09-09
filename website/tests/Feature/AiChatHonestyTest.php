<?php

namespace Tests\Feature;

use App\Models\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lucky AI must not invent facts about the marketplace.
 *
 * The offline fallback script used to answer a question about warehouse work
 * with three specific vacancies and a salary band, none of which came from the
 * database — it ran the real query, discarded the result, and printed invented
 * roles under the words "We have active openings". It also advertised access to
 * "50,000+ candidates" and a flat "SGD 3,500 to SGD 6,500" pay range.
 *
 * A candidate can act on what this thing says. These assertions exist so it can
 * only say what the jobs table supports.
 */
class AiChatHonestyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Claims with no source at all.
     *
     * The list is short on purpose, and what is missing from it is the point.
     * The old script also named "Jurong East", "Logistics Operations Executive"
     * and the band "SGD 4,200 - 5,800" — every one of which turned out to be a
     * real seeded row ("Construction Site Supervisor | Kallang | SGD
     * 4200-5800"). Whoever wrote the hardcoded replies had copied a snapshot of
     * the seed data into string literals.
     *
     * That is the more dangerous failure, not the lesser one: it read correctly
     * in development and would have gone wrong silently the moment the jobs
     * table changed, or on any production database. So these strings may
     * legitimately appear now — the assistant reads them from the table. The
     * real invariant is structural and lives in
     * test_every_role_named_is_a_real_vacancy: whatever is named must exist.
     *
     * What remains here is what was never in the database under any reading:
     * a candidate headcount, and a pay range phrased as a platform-wide fact.
     */
    private const FABRICATIONS = [
        '50,000+',
        'SGD 3,500 to SGD 6,500',
    ];

    private function ask(string $message): string
    {
        return (string) $this->postJson('/api/ai-chat', ['message' => $message])
            ->assertOk()
            ->json('reply');
    }

    public function test_no_branch_of_the_assistant_invents_marketplace_facts(): void
    {
        $this->seed();

        $probes = ['hello', 'what can you do', 'warehouse jobs', 'construction work', 'salary', 'resume score'];

        foreach ($probes as $probe) {
            $reply = $this->ask($probe);

            foreach (self::FABRICATIONS as $phrase) {
                $this->assertStringNotContainsString(
                    $phrase,
                    $reply,
                    "Asking '{$probe}' produced the fabricated phrase '{$phrase}'."
                );
            }
        }
    }

    /**
     * The invariant the old script broke: every role the assistant names in a
     * sector answer must be a vacancy that actually exists and is published.
     *
     * This is the assertion that would have caught the original bug, where the
     * real query was run and then discarded in favour of an invented list.
     */
    public function test_every_role_named_is_a_real_vacancy(): void
    {
        $this->seed();

        $published = Job::where('status', 'published')->pluck('title')->all();

        foreach (['warehouse jobs', 'construction work'] as $probe) {
            $reply = $this->ask($probe);

            preg_match_all('/• \*\*(.+?)\*\*/', $reply, $matches);

            foreach ($matches[1] as $named) {
                $this->assertContains(
                    $named,
                    $published,
                    "Asking '{$probe}' named '{$named}', which is not a published vacancy."
                );
            }
        }
    }

    public function test_sector_answers_name_real_vacancies(): void
    {
        $this->seed();

        $job = Job::where('status', 'published')->first();
        $this->assertNotNull($job, 'Seeder should publish at least one job.');

        // Rename a real published row so the sector branch has something to find.
        $job->update(['title' => 'Warehouse Supervisor']);

        $reply = $this->ask('warehouse jobs');

        $this->assertStringContainsString('Warehouse Supervisor', $reply);
    }

    public function test_it_says_so_rather_than_inventing_when_nothing_is_open(): void
    {
        $this->seed();

        // Nothing published at all — the honest answer is that there is nothing.
        Job::query()->update(['status' => 'draft']);

        $reply = $this->ask('warehouse jobs');

        $this->assertStringContainsString('do not have any', $reply);

        // And pay cannot be quoted from an empty set.
        $this->assertStringContainsString('cannot quote', $this->ask('salary'));
    }
}
