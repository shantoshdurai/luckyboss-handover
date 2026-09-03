<x-admin-layout title="{{ Str::headline($view ?? 'Candidates') }}" heading="Candidate & Job Seeker Administration">
    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h2 class="text-xl font-heading font-bold text-navy">{{ Str::headline($view ?? 'Job Seekers') }}</h2>
                <p class="text-xs text-text-secondary mt-0.5">Manage candidate profiles, verified resumes, applications, and security access.</p>
            </div>
        </div>

        {{-- Sub-view Navigation Tabs --}}
        <div class="flex flex-wrap items-center gap-2 p-1.5 bg-white rounded-2xl border border-border shadow-2xs">
            <a href="{{ route('admin.candidates.index', ['view' => 'all-job-seekers']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'all-job-seekers' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                All Seekers
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'verified-candidates']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'verified-candidates' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Verified Profiles
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'candidate-resumes']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'candidate-resumes' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Resumes & Parsing
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'candidate-skills']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'candidate-skills' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Skills Directory
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'candidate-applications']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'candidate-applications' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Job Applications
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'candidate-purchases']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'candidate-purchases' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Purchases
            </a>
            <a href="{{ route('admin.candidates.index', ['view' => 'candidate-login-history']) }}" class="px-4 py-2 rounded-xl text-xs font-bold transition-colors {{ ($view ?? '') === 'candidate-login-history' ? 'bg-navy text-white shadow-xs' : 'text-text-secondary hover:text-navy hover:bg-surface-sunken' }}">
                Login History
            </a>
        </div>

        @if(in_array($view, ['all-job-seekers','new-registrations','verified-candidates','incomplete-profiles','complete-profiles','blocked-candidates'], true) || empty($view))
            {{-- Search Bar --}}
            <div class="bg-white p-4 rounded-2xl border border-border shadow-sm">
                <form method="GET" action="{{ route('admin.candidates.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                    <input type="hidden" name="view" value="{{ $view }}">
                    <div class="flex-1 relative w-full">
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search candidate name, email, or phone..." class="form-input text-xs pl-9 w-full">
                        <svg class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-6 w-full sm:w-auto">Search</button>
                    @if(!empty($filters['search']))
                        <a href="{{ route('admin.candidates.index', ['view' => $view]) }}" class="btn btn-outline btn-sm">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                                <th class="py-3.5 px-6">Candidate</th>
                                <th class="py-3.5 px-6">Contact Info</th>
                                <th class="py-3.5 px-6">Profile Completion</th>
                                <th class="py-3.5 px-6">Registered</th>
                                <th class="py-3.5 px-6">Access State</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($records as $candidate)
                                @php $comp = $candidate->candidateProfile?->profile_completion ?? 0; @endphp
                                <tr class="hover:bg-surface/60 transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-accent to-secondary-400 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                                {{ substr($candidate->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-navy block">{{ $candidate->name }}</span>
                                                <span class="text-xs text-text-muted">ID #{{ $candidate->id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-text-secondary">
                                        <div>{{ $candidate->email }}</div>
                                        <div class="text-text-muted">{{ $candidate->phone ?: 'No phone provided' }}</div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="w-32">
                                            <div class="flex items-center justify-between text-xs font-bold mb-1">
                                                <span>{{ $comp }}%</span>
                                            </div>
                                            <div class="w-full h-1.5 rounded-full bg-surface-sunken overflow-hidden">
                                                <div class="h-full rounded-full {{ $comp >= 80 ? 'bg-emerald-500' : ($comp >= 50 ? 'bg-accent' : 'bg-amber-500') }}" style="width: {{ $comp }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-text-muted font-medium">
                                        {{ $candidate->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($candidate->is_active)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Blocked
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <form method="POST" action="{{ route('admin.candidates.toggle', $candidate) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-bold {{ $candidate->is_active ? 'text-amber-600' : 'text-emerald-600' }} hover:underline cursor-pointer">
                                                    {{ $candidate->is_active ? 'Block Access' : 'Unblock' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.candidates.destroy', $candidate) }}" onsubmit="return confirm('Delete candidate account?');" class="inline">
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
                                    <td colspan="6" class="py-12 text-center text-text-muted">
                                        No candidates found in this view.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($view === 'candidate-resumes')
            {{-- Candidate Resumes Subview --}}
            <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                                <th class="py-3.5 px-6">Candidate</th>
                                <th class="py-3.5 px-6">Resume File</th>
                                <th class="py-3.5 px-6">AI Parse Status</th>
                                <th class="py-3.5 px-6">Last Updated</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($records as $resume)
                                <tr class="hover:bg-surface/60 transition-colors">
                                    <td class="py-4 px-6 font-bold text-navy">{{ $resume->candidate->name ?? 'Candidate' }}</td>
                                    <td class="py-4 px-6 text-xs text-text-secondary">{{ $resume->file_name ?: basename((string)$resume->file_path) }}</td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ Str::headline($resume->parse_status ?? 'completed') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-text-muted">{{ $resume->updated_at->format('M d, Y H:i') }}</td>
                                    <td class="py-4 px-6 text-right">
                                        <form method="POST" action="{{ route('admin.candidate-resumes.destroy', $resume) }}" onsubmit="return confirm('Delete resume?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-danger hover:underline cursor-pointer">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-text-muted">No resume files uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            {{-- Generic Data View fallback --}}
            <div class="bg-white rounded-2xl border border-border shadow-sm p-6">
                <p class="text-text-secondary text-sm">Managing {{ Str::headline($view) }} records.</p>
            </div>
        @endif

        <div class="mt-6">
            {{ $records->links() }}
        </div>
    </div>
</x-admin-layout>
