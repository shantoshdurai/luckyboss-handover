<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExternalSource;
use App\Models\ImportBatch;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExternalDataController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'partner-sources';
        $sources = ExternalSource::latest()->get();
        $batches = ImportBatch::with('externalSource')->latest()->get();
        $jobs = Job::with('company')->where('is_external', true)->latest()->paginate(20)->withQueryString();
        return view('admin.external-data.index', compact('view', 'sources', 'batches', 'jobs'));
    }

    public function storeSource(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        ExternalSource::create($request->validate(['name' => ['required', 'string', 'max:120'], 'source_type' => ['required', 'string', 'max:80'], 'feed_type' => ['required', 'string', 'max:80'], 'status' => ['required', 'in:active,paused,failed'], 'import_limit' => ['nullable', 'integer', 'min:0'], 'description' => ['nullable', 'string']]) + ['contacts_visible' => $request->boolean('contacts_visible')]);
        return back()->with('success', 'External source created.');
    }

    public function updateSource(Request $request, ExternalSource $source): RedirectResponse
    {
        $this->ensureAdmin();
        $source->update($request->validate(['name' => ['required', 'string', 'max:120'], 'source_type' => ['required', 'string', 'max:80'], 'feed_type' => ['required', 'string', 'max:80'], 'status' => ['required', 'in:active,paused,failed'], 'import_limit' => ['nullable', 'integer', 'min:0'], 'description' => ['nullable', 'string']]));
        return back()->with('success', 'External source updated.');
    }

    public function destroySource(ExternalSource $source): RedirectResponse
    {
        $this->ensureAdmin(); $source->delete(); return back()->with('success', 'External source deleted.');
    }

    public function updateBatch(Request $request, ImportBatch $batch): RedirectResponse
    {
        $this->ensureAdmin();
        $batch->update($request->validate(['status' => ['required', 'in:queued,running,completed,failed'], 'records_received' => ['required', 'integer', 'min:0'], 'records_imported' => ['required', 'integer', 'min:0'], 'records_failed' => ['required', 'integer', 'min:0'], 'error_log' => ['nullable', 'string']]));
        return back()->with('success', 'Import batch updated.');
    }

    public function destroyBatch(ImportBatch $batch): RedirectResponse
    {
        $this->ensureAdmin(); $batch->delete(); return back()->with('success', 'Import batch deleted.');
    }
}
