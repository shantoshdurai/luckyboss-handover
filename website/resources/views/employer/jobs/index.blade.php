<x-employer-sidebar title="My Job Postings">
    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <h2 class="text-xl font-heading font-extrabold text-navy">Corporate Job Postings</h2>
                <p class="text-xs text-text-muted mt-1">Manage your active recruitment openings, view applicant volumes, and edit criteria.</p>
            </div>
            <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary btn-sm shrink-0 font-bold text-xs">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Post New Job</span>
            </a>
        </div>

        {{-- Filter Pills --}}
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('employer.jobs.index') }}" 
               class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all {{ !request('status') ? 'bg-navy text-white shadow-xs' : 'bg-white text-text-secondary border border-border hover:bg-slate-50' }}">
                All Jobs ({{ $jobs->count() }})
            </a>
            @foreach(['active' => 'Active', 'draft' => 'Drafts', 'featured' => 'Featured', 'expired' => 'Expired', 'archived' => 'Archived'] as $filter => $label)
                <a href="{{ route('employer.jobs.index', ['status' => $filter]) }}" 
                   class="px-4 py-1.5 rounded-xl text-xs font-bold transition-all {{ request('status') === $filter ? 'bg-navy text-white shadow-xs' : 'bg-white text-text-secondary border border-border hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Jobs Table Card --}}
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-border text-xs text-text-muted uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-3.5 px-6">Position & Category</th>
                            <th class="py-3.5 px-6">Location & Mode</th>
                            <th class="py-3.5 px-6">Status</th>
                            <th class="py-3.5 px-6">Applicants</th>
                            <th class="py-3.5 px-6">Closing Date</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($jobs as $job)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-6">
                                    <a href="{{ route('employer.jobs.edit', $job) }}" class="font-bold text-navy hover:text-secondary-600 transition-colors block">
                                        {{ $job->title }}
                                    </a>
                                    <span class="text-xs text-text-muted">
                                        {{ $job->jobCategory->name ?? 'General' }} &bull; {{ $job->job_type ?? 'Full-time' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-secondary">
                                    <span class="font-semibold text-navy">{{ $job->location ?? 'Regional' }}</span>
                                    <span class="block text-text-muted mt-0.5">{{ Str::headline($job->work_mode ?? 'On-site') }}</span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $job->status === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ str($job->status)->headline() }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <a href="{{ route('employer.jobs.applicants', $job) }}" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 text-accent border border-blue-100 text-xs font-bold hover:bg-blue-100 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        <span>{{ $job->applications_count }} Candidates</span>
                                    </a>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-secondary">
                                    {{ $job->closing_date ? $job->closing_date->format('d M Y') : 'Open Until Filled' }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('employer.jobs.applicants', $job) }}" class="btn btn-outline btn-xs font-bold">
                                            ATS Pipeline &rarr;
                                        </a>
                                        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 btn-xs font-bold">
                                            Edit
                                        </a>
                                        @if($job->status !== 'archived')
                                            <form method="POST" action="{{ route('employer.jobs.destroy', $job) }}" onsubmit="return confirm('Archive this job posting?')" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 btn-xs font-bold cursor-pointer">
                                                    Archive
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-text-muted text-xs">
                                    No job postings found in this view. <a href="{{ route('employer.jobs.create') }}" class="text-secondary-600 font-bold hover:underline">Click here to post a new job</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-employer-sidebar>