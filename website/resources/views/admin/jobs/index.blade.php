<x-admin-layout title="{{ Str::headline($view) }} | Jobs" heading="{{ Str::headline($view) }} Management">
    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h2 class="text-xl font-heading font-bold text-navy">Job Listings & Approval Pipeline</h2>
                <p class="text-xs text-text-secondary mt-0.5">Manage job visibility, featured status, application intake, and archive lifecycle.</p>
            </div>
            <a href="{{ route('admin.jobs.create') }}" class="btn btn-primary btn-sm flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Post New Job</span>
            </a>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-border shadow-sm">
            <form method="GET" action="{{ route('admin.jobs.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="flex-1 relative w-full">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by job title or hiring company..." class="form-input text-xs pl-9 w-full">
                    <svg class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-6 w-full sm:w-auto">Search</button>
                @if(!empty($filters['search']))
                    <a href="{{ route('admin.jobs.index', ['view' => $view]) }}" class="btn btn-outline btn-sm">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                            <th class="py-3.5 px-6">Position Details</th>
                            <th class="py-3.5 px-6">Hiring Company</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6">Closing Date</th>
                            <th class="py-3.5 px-6 text-center">Applicants</th>
                            <th class="py-3.5 px-6">Badges & Flags</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($jobs as $job)
                            <tr class="hover:bg-surface/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div>
                                        <span class="font-bold text-navy block text-base">{{ $job->title }}</span>
                                        <span class="text-xs text-text-muted">{{ $job->country_code }} · {{ $job->location ?? 'Flexible' }} · {{ Str::headline($job->job_type ?? 'Full-time') }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-semibold text-text-primary text-sm">{{ $job->company->name ?? 'Confidential' }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    @if($job->status === 'published')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Published
                                        </span>
                                    @elseif($job->status === 'draft')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-surface-sunken text-text-muted border border-border">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Draft
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            {{ Str::headline($job->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-xs text-text-muted font-medium">
                                    {{ $job->closing_date ? $job->closing_date->format('M d, Y') : 'Open Until Filled' }}
                                </td>
                                <td class="py-4 px-6 text-center font-bold text-navy">
                                    {{ $job->applications_count ?? 0 }}
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex flex-wrap gap-1">
                                        @if($job->is_featured)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Featured</span>
                                        @endif
                                        @if($job->is_urgent)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-700 border border-red-200">Urgent</span>
                                        @endif
                                        @if($job->is_sponsored)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">Sponsored</span>
                                        @endif
                                        @if(!$job->is_featured && !$job->is_urgent && !$job->is_sponsored)
                                            <span class="text-xs text-text-muted">Standard</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.jobs.edit', $job) }}" class="text-xs font-bold text-accent hover:underline">
                                            Edit
                                        </a>

                                        <form method="POST" action="{{ route('admin.jobs.status', [$job, $job->status === 'published' ? 'archived' : 'published']) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold {{ $job->status === 'published' ? 'text-text-muted' : 'text-emerald-600' }} hover:underline cursor-pointer">
                                                {{ $job->status === 'published' ? 'Archive' : 'Publish' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.jobs.flag', [$job, 'is_featured']) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-amber-600 hover:underline cursor-pointer">
                                                {{ $job->is_featured ? 'Unfeature' : 'Feature' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" onsubmit="return confirm('Delete this job?');" class="inline">
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
                                    No job positions found in this view.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border">
                {{ $jobs->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
