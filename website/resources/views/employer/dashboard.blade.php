<x-employer-sidebar title="Employer Dashboard">
    <div class="space-y-6">
        {{-- Welcome & Quick Actions Hero --}}
        <div class="bg-gradient-to-r from-navy via-primary-900 to-navy text-white rounded-3xl p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-secondary-300 border border-white/15">
                            {{ $company->status === 'verified' ? '✓ Verified Employer' : 'Verification Active' }}
                        </span>
                        <span class="text-xs text-blue-200">• {{ $company->industry ?? 'Corporate Enterprise' }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-heading font-extrabold text-white">
                        Welcome back, {{ auth()->user()->name }}
                    </h2>
                    <p class="text-slate-200 text-xs sm:text-sm mt-1 max-w-xl">
                        Manage your talent pipeline, schedule direct candidate interviews, and issue official employment offers.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <a href="{{ route('employer.jobs.create') }}" class="btn btn-secondary btn-md shadow-lg font-bold text-xs">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Post New Job</span>
                    </a>
                    <a href="{{ route('employer.portal', 'candidates') }}" class="btn bg-white/10 hover:bg-white/20 text-white border border-white/20 btn-md text-xs font-bold">
                        <span>Review Applicants</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- 4 Stat Metric Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            {{-- Stat 1: Active Jobs --}}
            <div class="bg-white rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-text-muted uppercase tracking-wider">Active Job Posts</p>
                    <h3 class="text-3xl font-heading font-extrabold text-navy mt-1">{{ $jobs->count() }}</h3>
                    <p class="text-xs text-text-secondary mt-1">
                        <a href="{{ route('employer.jobs.index') }}" class="text-secondary-600 font-bold hover:underline">Manage jobs &rarr;</a>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"></path></svg>
                </div>
            </div>

            {{-- Stat 2: Total Applicants --}}
            <div class="bg-white rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-text-muted uppercase tracking-wider">Candidate Applications</p>
                    <h3 class="text-3xl font-heading font-extrabold text-navy mt-1">{{ $applications }}</h3>
                    <p class="text-xs text-text-secondary mt-1">
                        <a href="{{ route('employer.portal', 'candidates') }}" class="text-secondary-600 font-bold hover:underline">View candidates &rarr;</a>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"></path></svg>
                </div>
            </div>

            {{-- Stat 3: Upcoming Interviews --}}
            <div class="bg-white rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-text-muted uppercase tracking-wider">Upcoming Interviews</p>
                    <h3 class="text-3xl font-heading font-extrabold text-navy mt-1">{{ $interviews->count() }}</h3>
                    <p class="text-xs text-text-secondary mt-1">
                        <a href="{{ route('employer.portal', 'interviews') }}" class="text-secondary-600 font-bold hover:underline">Interview schedule &rarr;</a>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"></path></svg>
                </div>
            </div>

            {{-- Stat 4: Active Offers --}}
            <div class="bg-white rounded-2xl border border-border p-5 shadow-xs flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-text-muted uppercase tracking-wider">Offers Extended</p>
                    <h3 class="text-3xl font-heading font-extrabold text-navy mt-1">{{ $offers->count() }}</h3>
                    <p class="text-xs text-text-secondary mt-1">
                        <a href="{{ route('employer.portal', 'offers') }}" class="text-secondary-600 font-bold hover:underline">Track offers &rarr;</a>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25"></path></svg>
                </div>
            </div>
        </div>

        {{-- Active Jobs Table --}}
        <div class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-6 border-b border-border flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-navy">Active Job Openings</h3>
                    <p class="text-xs text-text-muted mt-0.5">Overview of published roles and candidate response rate</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('employer.jobs.index') }}" class="btn btn-outline btn-xs font-bold">
                        View All Jobs ({{ $jobs->count() }})
                    </a>
                    <a href="{{ route('employer.jobs.create') }}" class="btn btn-primary btn-xs font-bold">
                        + Post Role
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-border text-xs text-text-muted uppercase tracking-wider font-semibold">
                        <tr>
                            <th class="py-3 px-6">Job Role</th>
                            <th class="py-3 px-6">Location</th>
                            <th class="py-3 px-6">Status</th>
                            <th class="py-3 px-6">Applicants</th>
                            <th class="py-3 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($jobs->take(5) as $job)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-6 font-bold text-navy">
                                    <a href="{{ route('employer.jobs.edit', $job) }}" class="hover:text-secondary-600 transition-colors">
                                        {{ $job->title }}
                                    </a>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-secondary">
                                    {{ $job->location ?? 'Regional' }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        {{ str($job->status)->headline() }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center gap-1 font-bold text-navy text-xs">
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                        {{ $job->applications_count }} Candidates
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('employer.jobs.applicants', $job) }}" class="btn btn-outline btn-xs font-bold">
                                            Candidates &rarr;
                                        </a>
                                        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 btn-xs font-bold">
                                            Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-text-muted text-xs">
                                    No jobs published yet. Click <a href="{{ route('employer.jobs.create') }}" class="text-secondary-600 font-bold hover:underline">Post New Job</a> to create your first vacancy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2-Column Grid: Upcoming Interviews & Plan Status --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Upcoming Interviews Card --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="font-bold text-navy text-sm">Scheduled Candidate Interviews</h3>
                    <a href="{{ route('employer.portal', 'interviews') }}" class="text-xs font-bold text-secondary-600 hover:underline">
                        Manage &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse($interviews->take(3) as $interview)
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3 text-xs">
                            <div>
                                <h4 class="font-bold text-navy">{{ $interview->application?->candidate?->name ?? 'Candidate' }}</h4>
                                <p class="text-text-muted text-[11px] mt-0.5">
                                    {{ $interview->application?->job?->title }} &bull; {{ $interview->scheduled_at?->format('d M Y, h:i A') }}
                                </p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-md font-bold text-[11px] bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $interview->mode ?? 'Video Call' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-text-muted text-center py-4">No upcoming interviews scheduled today.</p>
                    @endforelse
                </div>
            </div>

            {{-- Subscription & Team Overview --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="font-bold text-navy text-sm">Corporate Plan & Talent Sourcing</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Enterprise Pro
                    </span>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-text-muted">Job Posting Quota:</span>
                        <span class="font-bold text-navy">Unlimited Active Posts</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-muted">AI Candidate Scoring:</span>
                        <span class="font-bold text-emerald-600">Active (NLP Engine v2)</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-muted">Direct Talent Search:</span>
                        <span class="font-bold text-navy">Enabled</span>
                    </div>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <a href="{{ route('employer.portal', 'candidate-search') }}" class="btn btn-secondary btn-sm flex-1 text-center font-bold text-xs">
                        Search Candidate Directory
                    </a>
                    <a href="{{ route('employer.portal', 'billing') }}" class="btn btn-outline btn-sm font-bold text-xs">
                        Billing
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-employer-sidebar>
