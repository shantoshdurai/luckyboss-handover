<x-employer-shell title="Posted Jobs">
    <div class="space-y-6">

@if ($totalJobs === 0)
        {{--
            Nothing has ever been posted. TickBig's own empty state does one
            thing — it says the shelf is empty and hands you the button — and
            that is the right shape here: a company on day one was previously
            shown a full table chrome, six column headings and five filter pills
            around a single line of grey text, which reads as a broken screen
            rather than a new account.

            The filters and the table are not rendered at all in this state.
            There is nothing for them to filter.
        --}}
        <div class="bg-white rounded-2xl border border-border shadow-xs px-6 py-16 text-center">
            <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center mb-5" style="background:#EEF4FF;">
                <svg class="w-7 h-7" style="color:#2563EB;" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <h2 class="font-heading font-bold text-navy text-2xl">No jobs posted yet</h2>
            <p class="text-sm mt-2 mx-auto" style="color:#6E829C;max-width:26rem;">
                Post your first vacancy and it will appear here, where you can edit it,
                archive it and follow everyone who applies.
            </p>

            <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('employer.jobs.create') }}"
                   class="btn btn-primary btn-sm font-bold text-xs">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Post your first job</span>
                </a>
                <a href="{{ route('employer.home') }}"
                   class="btn btn-outline btn-sm font-bold text-xs">
                    Let Hiring AI write it &rarr;
                </a>
            </div>
        </div>
@else
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-xs">
            <div>
                <h2 class="text-xl font-heading font-extrabold text-navy">Posted jobs</h2>
                <p class="text-xs text-text-muted mt-1">Every vacancy you have published, with applicant volumes. Edit or archive any of them.</p>
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
                All Jobs ({{ $totalJobs }})
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
                                <td colspan="6" class="text-center py-14">
                                    <p class="font-heading font-bold text-navy text-base">Nothing under this filter</p>
                                    <p class="text-xs mt-1.5" style="color:#6E829C;">
                                        You have {{ $totalJobs }} {{ Str::plural('job', $totalJobs) }} posted &mdash;
                                        <a href="{{ route('employer.jobs.index') }}" class="font-bold hover:underline" style="color:#2563EB;">show all of them</a>.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($jobs->hasPages())
            <div>{{ $jobs->links() }}</div>
        @endif
@endif
    </div>
</x-employer-shell>