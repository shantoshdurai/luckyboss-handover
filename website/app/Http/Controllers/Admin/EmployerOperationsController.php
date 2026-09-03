<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EmployerDocument;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class EmployerOperationsController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function users(Request $request): View
    {
        $this->ensureAdmin();
        $query = User::whereHas('companies')->with('companies')->latest();
        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($request->filled('company_id')) {
            $query->whereHas('companies', fn ($builder) => $builder->whereKey($request->integer('company_id')));
        }
        return view('admin.employer-users.index', ['users' => $query->paginate(20)->withQueryString(), 'companies' => Company::orderBy('name')->get(), 'filters' => $request->only(['search', 'company_id'])]);
    }

    public function toggleUser(User $user, Company $company): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless($user->companies()->whereKey($company->id)->exists(), 404);
        $membership = $user->companies()->whereKey($company->id)->first()->pivot;
        $user->companies()->updateExistingPivot($company->id, ['is_active' => ! $membership->is_active]);
        return back()->with('success', 'Employer user access updated.');
    }

    public function documents(Request $request): View
    {
        $this->ensureAdmin();
        $query = EmployerDocument::with('company')->latest();
        if ($request->filled('company_id')) $query->where('company_id', $request->integer('company_id'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        return view('admin.employer-documents.index', ['documents' => $query->paginate(20)->withQueryString(), 'companies' => Company::orderBy('name')->get(), 'filters' => $request->only(['company_id', 'status'])]);
    }

    public function storeDocument(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['company_id' => ['required', 'exists:companies,id'], 'name' => ['required', 'string', 'max:180'], 'status' => ['required', 'in:pending,approved,rejected'], 'file' => ['nullable', 'file', 'max:10240']]);
        if ($request->hasFile('file')) $data['file_path'] = $request->file('file')->store('employer-documents');
        unset($data['file']);
        $data['reviewed_at'] = $data['status'] === 'pending' ? null : now();
        EmployerDocument::create($data);
        return back()->with('success', 'Employer document added.');
    }

    public function updateDocument(Request $request, EmployerDocument $document): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'status' => ['required', 'in:pending,approved,rejected'], 'file' => ['nullable', 'file', 'max:10240']]);
        if ($request->hasFile('file')) { if ($document->file_path) Storage::delete($document->file_path); $data['file_path'] = $request->file('file')->store('employer-documents'); }
        unset($data['file']);
        $data['reviewed_at'] = $data['status'] === 'pending' ? null : now();
        $document->update($data);
        return back()->with('success', 'Employer document updated.');
    }

    public function destroyDocument(EmployerDocument $document): RedirectResponse
    {
        $this->ensureAdmin();
        if ($document->file_path) Storage::delete($document->file_path);
        $document->delete();
        return back()->with('success', 'Employer document deleted.');
    }

    public function activity(Request $request): View
    {
        $this->ensureAdmin();
        $query = AuditLog::with(['company', 'user'])->whereNotNull('company_id')->latest();
        if ($request->filled('company_id')) $query->where('company_id', $request->integer('company_id'));
        return view('admin.employer-activity.index', ['activities' => $query->paginate(30)->withQueryString(), 'companies' => Company::orderBy('name')->get(), 'filters' => $request->only('company_id')]);
    }
}
