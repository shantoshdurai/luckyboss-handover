<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CmsController extends Controller
{
    private const RECORD_MODULES = [
        'home-page' => 'cms-home-page', 'home-sections' => 'home-sections', 'specializations' => 'cms-specializations', 'testimonials' => 'cms-testimonials', 'statistics' => 'cms-statistics', 'marquee-job-ticker' => 'cms-marquee-job-ticker', 'pages' => 'cms-pages', 'faq' => 'cms-faq', 'app-links' => 'cms-app-links',
    ];

    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'home-page';
        return view('admin.cms.index', ['view' => $view, 'records' => AdminRecord::where('module', self::RECORD_MODULES[$view] ?? '')->latest()->get(), 'featuredJobs' => Job::with('company')->where('is_featured', true)->latest()->get(), 'featuredEmployers' => Company::where('status', 'verified')->latest()->get(), 'categories' => JobCategory::where('is_active', true)->orderBy('name')->get()]);
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString();
        abort_unless(isset(self::RECORD_MODULES[$view]), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string']]);
        AdminRecord::create(['module' => self::RECORD_MODULES[$view], 'name' => $data['name'], 'slug' => str($data['name'])->slug(), 'description' => $data['description'] ?? null, 'payload' => filled($data['payload'] ?? null) ? json_decode($data['payload'], true) : [], 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'CMS record created.');
    }

    public function destroyRecord(Request $request, AdminRecord $record): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless(in_array($record->module, self::RECORD_MODULES, true), 404);
        $record->delete();
        return back()->with('success', 'CMS record deleted.');
    }
}
