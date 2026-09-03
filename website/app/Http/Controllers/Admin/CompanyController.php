<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $query = Company::withCount(['users', 'jobs'])->latest();
        $search = trim((string) $request->string('search'));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('country_code')) {
            $query->where('country_code', $request->string('country_code'));
        }

        return view('admin.companies.index', [
            'companies' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'country_code']),
        ]);
    }

    public function edit(Company $company): View
    {
        $this->ensureAdmin();
        return view('admin.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'country_code' => ['nullable', 'string', 'max:3'],
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:pending,verified,suspended,expired,rejected'],
        ]);
        $company->update($data);
        if ($data['status'] === 'verified' && ! $company->verified_at) {
            $company->update(['verified_at' => now()]);
        }
        return redirect()->route('admin.companies.index')->with('success', 'Company updated.');
    }

    public function status(Company $company, string $status): RedirectResponse
    {
        $this->ensureAdmin();
        abort_unless(in_array($status, ['verified', 'suspended', 'pending', 'expired', 'rejected'], true), 404);
        $company->update(['status' => $status, 'verified_at' => $status === 'verified' ? ($company->verified_at ?: now()) : null]);
        return back()->with('success', "Company marked {$status}.");
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->ensureAdmin();
        $company->delete();
        return back()->with('success', 'Company deleted.');
    }
}
