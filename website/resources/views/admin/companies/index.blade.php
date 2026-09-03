<x-admin-layout title="Employer Directory" heading="Employer Companies Directory">
    <div class="space-y-6">
        {{-- Header & Actions --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h2 class="text-xl font-heading font-bold text-navy">Registered Companies & Employers</h2>
                <p class="text-xs text-text-secondary mt-0.5">Verify corporate credentials, manage account status, and track job postings.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.employer-notes.index') }}" class="btn btn-outline btn-sm">
                    Employer Notes
                </a>
                <a href="{{ route('admin.employer-documents.index') }}" class="btn btn-primary btn-sm">
                    Verification Documents
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-border shadow-sm">
            <form method="GET" action="{{ route('admin.companies.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-5 relative">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search company name, email, reg no..." class="form-input text-xs pl-9">
                    <svg class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div class="sm:col-span-3">
                    <select name="status" class="form-input text-xs">
                        <option value="">All Statuses</option>
                        @foreach(['pending', 'verified', 'suspended', 'expired', 'rejected'] as $st)
                            <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ Str::headline($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <input type="text" name="country_code" value="{{ $filters['country_code'] ?? '' }}" placeholder="Country (SG/MY/IN)" class="form-input text-xs">
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-full justify-center">Filter</button>
                    @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['country_code']))
                        <a href="{{ route('admin.companies.index') }}" class="btn btn-outline btn-sm text-xs">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Companies Table Card --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                            <th class="py-3.5 px-6">Company</th>
                            <th class="py-3.5 px-6">Contact Details</th>
                            <th class="py-3.5 px-6">Country</th>
                            <th class="py-3.5 px-6 text-center">Active Jobs</th>
                            <th class="py-3.5 px-6 text-center">Users</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($companies as $company)
                            <tr class="hover:bg-surface/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-navy to-primary-800 text-white flex items-center justify-center font-bold text-sm shadow-xs shrink-0">
                                            {{ substr($company->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-navy block">{{ $company->name }}</span>
                                            <span class="text-xs text-text-muted">Reg: {{ $company->registration_number ?: 'Pending submission' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-secondary">
                                    <div>{{ $company->email ?: 'No email' }}</div>
                                    <div class="text-text-muted">{{ $company->phone ?: 'No phone' }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-surface-sunken text-navy border border-border">
                                        {{ $company->country_code ?: 'SG' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-navy">
                                    {{ $company->jobs_count ?? 0 }}
                                </td>
                                <td class="py-4 px-6 text-center text-xs font-medium text-text-secondary">
                                    {{ $company->users_count ?? 0 }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($company->status === 'verified')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Verified
                                        </span>
                                    @elseif($company->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            Pending Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            {{ Str::headline($company->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.companies.edit', $company) }}" class="text-xs font-bold text-accent hover:underline">
                                            Edit
                                        </a>

                                        @if($company->status !== 'verified')
                                            <form method="POST" action="{{ route('admin.companies.status', [$company, 'verified']) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-bold text-emerald-600 hover:underline cursor-pointer">
                                                    Approve
                                                </button>
                                            </form>
                                        @endif

                                        @if($company->status !== 'suspended')
                                            <form method="POST" action="{{ route('admin.companies.status', [$company, 'suspended']) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-bold text-amber-600 hover:underline cursor-pointer">
                                                    Suspend
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('Delete company and all related listings?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-danger hover:underline cursor-pointer">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-text-muted">
                                    No companies found matching the filter criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-border">
                {{ $companies->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
