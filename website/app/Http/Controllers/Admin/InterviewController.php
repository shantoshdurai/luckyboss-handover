<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\Interview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InterviewController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-interviews';
        $query = Interview::with(['application.candidate', 'application.job', 'company', 'interviewer'])->latest('scheduled_at');
        match ($view) {
            'today-interviews' => $query->whereDate('scheduled_at', today()),
            'upcoming-interviews' => $query->where('scheduled_at', '>', now())->where('status', '!=', 'cancelled'),
            'completed-interviews' => $query->where('status', 'completed'),
            'cancelled-interviews' => $query->where('status', 'cancelled'),
            default => null,
        };
        return view('admin.interviews.index', ['view' => $view, 'interviews' => $query->paginate(20)->withQueryString(), 'modes' => AdminRecord::where('module', 'interview-modes')->latest()->get(), 'connections' => AdminRecord::where('module', 'calendar-connections')->latest()->get()]);
    }

    public function update(Request $request, Interview $interview): RedirectResponse
    {
        $this->ensureAdmin();
        $interview->update($request->validate(['status' => ['required', 'in:scheduled,completed,cancelled,rescheduled'], 'mode' => ['required', 'string', 'max:80'], 'scheduled_at' => ['required', 'date'], 'notes' => ['nullable', 'string']]));
        return back()->with('success', 'Interview updated.');
    }

    public function destroy(Interview $interview): RedirectResponse
    {
        $this->ensureAdmin(); $interview->delete(); return back()->with('success', 'Interview deleted.');
    }

    public function storeMode(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); AdminRecord::create($this->recordData($request, 'interview-modes')); return back()->with('success', 'Interview mode created.');
    }

    public function storeConnection(Request $request): RedirectResponse
    {
        $this->ensureAdmin(); AdminRecord::create($this->recordData($request, 'calendar-connections')); return back()->with('success', 'Calendar connection created.');
    }

    private function recordData(Request $request, string $module): array
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string']]);
        return ['module' => $module, 'name' => $data['name'], 'slug' => str($data['name'])->slug(), 'description' => $data['description'] ?? null, 'payload' => filled($data['payload'] ?? null) ? json_decode($data['payload'], true) : [], 'is_active' => true];
    }
}
