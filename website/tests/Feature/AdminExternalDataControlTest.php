<?php

namespace Tests\Feature;

use App\Models\ExternalSource;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExternalDataControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_external_data_modules_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        foreach (['external-candidates', 'external-jobs', 'partner-sources', 'import-history', 'sync-history', 'failed-imports'] as $view) {
            $this->actingAs($admin)->get(route('admin.command.show', ['external-data', $view]))->assertOk()->assertSee('Live controls');
            $this->actingAs($admin)->get(route('admin.external-data.index', ['view' => $view]))->assertOk();
        }
    }

    public function test_admin_can_manage_external_sources_and_batches(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@luckyboss.test')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.external-data.sources.store'), ['name' => 'Partner Feed', 'source_type' => 'ATS', 'feed_type' => 'api', 'status' => 'active'])->assertRedirect();
        $source = ExternalSource::where('name', 'Partner Feed')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.external-data.sources.update', $source), ['name' => 'Partner Feed Updated', 'source_type' => 'ATS', 'feed_type' => 'api', 'status' => 'paused'])->assertRedirect();
        $this->assertDatabaseHas('external_sources', ['id' => $source->id, 'status' => 'paused']);
        $batch = ImportBatch::create(['external_source_id' => $source->id, 'data_type' => 'jobs', 'status' => 'queued', 'records_received' => 4]);
        $this->actingAs($admin)->put(route('admin.external-data.batches.update', $batch), ['status' => 'failed', 'records_received' => 4, 'records_imported' => 1, 'records_failed' => 3, 'error_log' => 'Invalid feed'])->assertRedirect();
        $this->assertDatabaseHas('import_batches', ['id' => $batch->id, 'status' => 'failed']);
    }
}
