<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployerNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerNoteController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $query = EmployerNote::with(['company', 'author'])->latest();
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where('note', 'like', "%{$search}%");
        }
        return view('admin.employer-notes.index', ['notes' => $query->paginate(20)->withQueryString(), 'companies' => Company::orderBy('name')->get(), 'filters' => $request->only(['company_id', 'search'])]);
    }

    public function create(): View
    {
        $this->ensureAdmin();
        return view('admin.employer-notes.form', ['note' => null, 'companies' => Company::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['company_id' => ['required', 'exists:companies,id'], 'note' => ['required', 'string', 'max:10000']]);
        EmployerNote::create($data + ['user_id' => auth()->id()]);
        return redirect()->route('admin.employer-notes.index')->with('success', 'Employer note created.');
    }

    public function edit(EmployerNote $note): View
    {
        $this->ensureAdmin();
        return view('admin.employer-notes.form', ['note' => $note, 'companies' => Company::orderBy('name')->get()]);
    }

    public function update(Request $request, EmployerNote $note): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['company_id' => ['required', 'exists:companies,id'], 'note' => ['required', 'string', 'max:10000']]);
        $note->update($data);
        return redirect()->route('admin.employer-notes.index')->with('success', 'Employer note updated.');
    }

    public function destroy(EmployerNote $note): RedirectResponse
    {
        $this->ensureAdmin();
        $note->delete();
        return back()->with('success', 'Employer note deleted.');
    }
}
