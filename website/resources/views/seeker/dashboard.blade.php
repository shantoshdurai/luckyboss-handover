<x-seeker-sidebar :title="match($tab) {
    'applications' => 'My Job Applications',
    'matching' => 'Matching Opportunities',
    'saved' => 'Saved Bookmarked Jobs',
    default => 'Candidate Workspace'
}">
    <div class="space-y-6">
        {{-- Top Bar Profile Header --}}
        <div class="bg-white rounded-2xl border border-border p-6 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-navy">{{ $user->name }}</h2>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <svg class="w-3 h-3 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        Verified Candidate
                    </span>
                </div>
                <p class="text-xs text-text-secondary mt-1">
                    {{ $profile?->current_title ?: 'Warehouse Coordinator' }} &bull; {{ $profile?->current_location ?: 'Singapore' }} &bull; {{ $profile?->years_experience ?? 4 }} Years Experience
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('seeker.profile.edit') }}" class="btn btn-outline btn-sm text-xs font-bold">
                    Edit Resume & Skills
                </a>
                <a href="{{ route('jobs.index') }}" class="btn btn-primary btn-sm text-xs font-bold shadow-xs">
                    Browse All Openings &rarr;
                </a>
            </div>
        </div>

        {{-- TAB 1: MY APPLICATIONS --}}
        @if($tab === 'applications')
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-navy">All Applications ({{ count($applications) }})</h3>
                        <p class="text-xs text-text-muted mt-0.5">Live statuses and interview invites from hiring employers</p>
                    </div>
                </div>

                @forelse($applications as $application)
                    <div class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-base font-bold text-navy">{{ $application->job->title }}</h4>
                                <p class="text-xs text-text-secondary mt-0.5">
                                    {{ $application->job->company->name }} &bull; {{ $application->job->location }} &bull; Applied {{ $application->applied_at?->format('d M Y') ?: 'Recently' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Stage: {{ $application->status }}
                                </span>
                                @if($application->status === 'Withdrawn')
                                    <form method="POST" action="{{ route('seeker.jobs.apply', $application->job) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm text-xs font-bold">Apply Again</button>
                                    </form>
                                @elseif(!in_array($application->status, ['Hired', 'Rejected']))
                                    <div x-data="{ confirming: false }" class="flex items-center">
                                        <button x-show="!confirming" @click="confirming = true" type="button" class="btn btn-outline btn-sm text-xs text-rose-600 hover:bg-rose-50 border-rose-200 cursor-pointer">
                                            Withdraw
                                        </button>
                                        <div x-show="confirming" x-cloak class="flex items-center gap-1.5 bg-rose-50 p-1 rounded-xl border border-rose-200">
                                            <span class="text-[11px] font-bold text-rose-800 pl-1.5">Withdraw?</span>
                                            <form method="POST" action="{{ route('seeker.applications.withdraw', $application) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold shadow-xs cursor-pointer">
                                                    Yes
                                                </button>
                                            </form>
                                            <button @click="confirming = false" type="button" class="px-2 py-1 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[11px] font-semibold cursor-pointer">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- 4-Stage Stepper --}}
                        @php
                            $stages = ['Applied', 'Shortlisted', 'Interview', 'Offer'];
                            $currentStageIndex = match($application->status) {
                                'Shortlisted' => 1,
                                'Interview', 'Interview Scheduled' => 2,
                                'Offer', 'Offer Extended', 'Hired' => 3,
                                default => 0,
                            };
                        @endphp
                        <div class="grid grid-cols-4 gap-2 pt-2">
                            @foreach($stages as $index => $stageName)
                                <div class="space-y-1.5">
                                    <div class="h-2 rounded-full {{ $index <= $currentStageIndex ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                    <p class="text-[11px] font-bold {{ $index <= $currentStageIndex ? 'text-navy' : 'text-slate-400' }}">
                                        {{ $stageName }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-text-muted text-xs">
                        No applications submitted yet. <a href="{{ route('jobs.index') }}" class="text-accent font-bold hover:underline">Explore Available Openings</a>
                    </div>
                @endforelse
            </div>

        {{-- TAB 2: MATCHING JOBS --}}
        @elseif($tab === 'matching')
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-navy">AI Matched Vacancies</h3>
                        <p class="text-xs text-text-muted mt-0.5">Matched according to your skills, experience, and location preferences</p>
                    </div>
                </div>

                <div class="divide-y divide-border">
                    @forelse($allMatchingJobs as $job)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 rounded-xl px-2 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-navy">
                                        <a href="{{ route('jobs.index') }}" class="hover:text-accent">{{ $job->title }}</a>
                                    </h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ str($job->work_mode)->headline() }}
                                    </span>
                                </div>

                                <p class="text-xs text-text-secondary">
                                    {{ $job->company->name ?? 'Verified Employer' }} &bull; {{ $job->location }} &bull; {{ $job->experience_min }}-{{ $job->experience_max }} yrs exp
                                </p>

                                @if($job->salary_visible && $job->salary_min)
                                    <p class="text-xs font-bold text-navy pt-0.5">
                                        {{ $job->currency_code }} {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} / month
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('seeker.jobs.save', $job) }}">
                                    @csrf
                                    <button type="submit" class="p-2 text-slate-400 hover:text-navy rounded-xl border border-border bg-white transition-colors cursor-pointer" title="{{ in_array($job->id, $savedJobIds ?? [], true) ? 'Unsave' : 'Save' }}">
                                        <svg class="w-4 h-4 {{ in_array($job->id, $savedJobIds ?? [], true) ? 'fill-accent text-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                                    </button>
                                </form>

                                @if(in_array($job->id, $appliedJobIds ?? [], true))
                                    <span class="inline-flex items-center gap-1 py-1.5 px-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Applied</span>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('seeker.jobs.apply', $job) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm text-xs font-bold py-1.5 px-4 shadow-xs cursor-pointer hover:scale-102 transition-transform">
                                            Apply Now
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-text-muted text-xs">
                            No matching vacancies right now.
                        </div>
                    @endforelse
                </div>
            </div>

        {{-- TAB 3: SAVED JOBS --}}
        @elseif($tab === 'saved')
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-navy">Saved Bookmarks ({{ count($savedJobs) }})</h3>
                        <p class="text-xs text-text-muted mt-0.5">Vacancies you have saved for later review</p>
                    </div>
                </div>

                <div class="divide-y divide-border">
                    @forelse($savedJobs as $job)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 rounded-xl px-2 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-navy">
                                        <a href="{{ route('jobs.index') }}" class="hover:text-accent">{{ $job->title }}</a>
                                    </h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ str($job->work_mode)->headline() }}
                                    </span>
                                </div>

                                <p class="text-xs text-text-secondary">
                                    {{ $job->company->name ?? 'Verified Employer' }} &bull; {{ $job->location }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('seeker.jobs.save', $job) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm text-xs font-semibold py-1.5 px-3 cursor-pointer">
                                        Remove Bookmark
                                    </button>
                                </form>

                                @if(in_array($job->id, $appliedJobIds ?? [], true))
                                    <span class="inline-flex items-center gap-1 py-1.5 px-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Applied</span>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('seeker.jobs.apply', $job) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm text-xs font-bold py-1.5 px-4 shadow-xs cursor-pointer hover:scale-102 transition-transform">
                                            Apply Now
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-text-muted text-xs">
                            You have no saved jobs. Click the bookmark icon on any job to save it here!
                        </div>
                    @endforelse
                </div>
            </div>

        {{-- TAB 4: DEFAULT DASHBOARD OVERVIEW --}}
        @else
            {{-- Active Applications / ATS Stage Progress --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4" x-data="{ showAll: false }">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h3 class="text-base font-bold text-navy">Application Pipeline</h3>
                        <p class="text-xs text-text-muted mt-0.5">Track your active hiring stages with employers</p>
                    </div>
                    <div class="flex items-center gap-3">
                        @if(count($applications) > 2)
                            <button @click="showAll = !showAll" type="button" class="text-xs font-bold text-secondary-600 hover:text-navy hover:underline cursor-pointer flex items-center gap-1">
                                <span x-text="showAll ? 'Collapse (Show 2)' : 'Show all ({{ count($applications) }})'"></span>
                                <svg :class="showAll ? 'rotate-180' : ''" class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        @endif
                        <a href="{{ route('seeker.dashboard', ['tab' => 'applications']) }}" class="text-xs font-bold text-slate-500 hover:text-navy hover:underline">
                            Full ATS Tab &rarr;
                        </a>
                    </div>
                </div>

                @forelse($applications as $index => $application)
                    <div x-show="showAll || {{ $index < 2 ? 'true' : 'false' }}" x-transition class="p-5 rounded-2xl bg-slate-50/80 border border-slate-200/80 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h4 class="text-base font-bold text-navy">{{ $application->job->title }}</h4>
                                <p class="text-xs text-text-secondary mt-0.5">
                                    {{ $application->job->company->name }} &bull; Applied {{ $application->applied_at?->format('d M Y') ?: 'Recently' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Stage: {{ $application->status }}
                                </span>
                            </div>
                        </div>

                        {{-- 4-Stage Progress Stepper --}}
                        @php
                            $stages = ['Applied', 'Shortlisted', 'Interview', 'Offer'];
                            $currentStageIndex = match($application->status) {
                                'Shortlisted' => 1,
                                'Interview', 'Interview Scheduled' => 2,
                                'Offer', 'Offer Extended', 'Hired' => 3,
                                default => 0,
                            };
                        @endphp
                        <div class="grid grid-cols-4 gap-2 pt-2">
                            @foreach($stages as $stIndex => $stageName)
                                <div class="space-y-1.5">
                                    <div class="h-2 rounded-full {{ $stIndex <= $currentStageIndex ? 'bg-emerald-500' : 'bg-slate-200' }}"></div>
                                    <p class="text-[11px] font-bold {{ $stIndex <= $currentStageIndex ? 'text-navy' : 'text-slate-400' }}">
                                        {{ $stageName }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-text-muted text-xs">
                        You have not applied for any roles yet. <a href="{{ route('jobs.index') }}" class="text-secondary-600 font-bold hover:underline">Explore Open Positions</a>
                    </div>
                @endforelse

                @if(count($applications) > 2)
                    <div class="pt-2 text-center">
                        <button @click="showAll = !showAll" type="button" class="btn btn-outline btn-xs font-bold text-xs">
                            <span x-text="showAll ? '▲ Show Fewer Applications' : '▼ Expand & View All {{ count($applications) }} Applications'"></span>
                        </button>
                    </div>
                @endif
            </div>

            {{-- Curated Job Openings --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <div>
                        <h3 class="text-base font-bold text-navy">Curated Job Openings</h3>
                        <p class="text-xs text-text-muted mt-0.5">Verified vacancies based on your location and background</p>
                    </div>
                    <a href="{{ route('seeker.dashboard', ['tab' => 'matching']) }}" class="text-xs font-bold text-accent hover:underline">
                        View all matches &rarr;
                    </a>
                </div>

                <div class="divide-y divide-border">
                    @forelse($recommendedJobs as $job)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 rounded-xl px-2 transition-colors">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-navy">
                                        <a href="{{ route('jobs.index') }}" class="hover:text-accent">{{ $job->title }}</a>
                                    </h4>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ str($job->work_mode)->headline() }}
                                    </span>
                                </div>

                                <p class="text-xs text-text-secondary">
                                    {{ $job->company->name ?? 'Verified Employer' }} &bull; {{ $job->location }} &bull; {{ $job->experience_min }}-{{ $job->experience_max }} yrs exp
                                </p>

                                @if($job->salary_visible && $job->salary_min)
                                    <p class="text-xs font-bold text-navy pt-0.5">
                                        {{ $job->currency_code }} {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} / month
                                    </p>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('seeker.jobs.save', $job) }}">
                                    @csrf
                                    <button type="submit" class="p-2 text-slate-400 hover:text-navy rounded-xl border border-border bg-white transition-colors cursor-pointer" title="{{ in_array($job->id, $savedJobIds ?? [], true) ? 'Unsave' : 'Save' }}">
                                        <svg class="w-4 h-4 {{ in_array($job->id, $savedJobIds ?? [], true) ? 'fill-accent text-accent' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path></svg>
                                    </button>
                                </form>

                                @if(in_array($job->id, $appliedJobIds ?? [], true))
                                    <span class="inline-flex items-center gap-1 py-1.5 px-3 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-300 text-xs font-bold shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span>Applied</span>
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('seeker.jobs.apply', $job) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm text-xs font-bold py-1.5 px-4 shadow-xs cursor-pointer hover:scale-102 transition-transform">
                                            Apply Now
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-text-muted text-xs">
                            No vacancies listed right now.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Candidate Profile Overview Details --}}
            <div class="bg-white rounded-2xl border border-border p-6 shadow-xs">
                <div class="flex items-center justify-between border-b border-border pb-3 mb-4">
                    <h3 class="text-base font-bold text-navy">Profile Summary</h3>
                    <a href="{{ route('seeker.profile.edit') }}" class="text-xs font-bold text-accent hover:underline">Edit Details</a>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-text-muted block text-[11px]">Notice Period</span>
                        <span class="font-bold text-navy mt-0.5 block">{{ $profile?->notice_period ?: 'Immediate' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-text-muted block text-[11px]">Expected Salary</span>
                        <span class="font-bold text-navy mt-0.5 block">{{ $profile?->preferred_currency ?: 'SGD' }} {{ $profile?->expected_salary ? number_format($profile->expected_salary) : '3,500' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-text-muted block text-[11px]">Preferred Country</span>
                        <span class="font-bold text-navy mt-0.5 block">{{ $profile?->country_code ?: 'Singapore (SG)' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-text-muted block text-[11px]">Resume Status</span>
                        <span class="font-bold text-emerald-600 mt-0.5 block">Verified & Active</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-seeker-sidebar>
