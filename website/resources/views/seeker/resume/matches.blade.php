<x-seeker-sidebar title="Your Matches">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-10 space-y-6">

        <div class="space-y-2">
            <h1 class="text-2xl font-heading font-extrabold text-navy">
                @if ($matchReadiness['ready'] && $matchedJobs->isNotEmpty())
                    {{ $matchedJobs->count() }} {{ Str::plural('job', $matchedJobs->count()) }} that fit you
                @else
                    Your matches
                @endif
            </h1>
            @if ($matchReadiness['ready'] && $matchedJobs->isNotEmpty())
                <p class="text-sm text-text-secondary">
                    Scored against your profile and filtered to {{ $matchSettings['minimum_match_score'] }}% and above.
                    Nothing below that bar is padded in.
                </p>
            @endif
        </div>

        {{-- Readiness, empty state and Apply All all live in the shared partial,
             so this page cannot drift from the dashboard's version of them. --}}
        @include('seeker.partials.match-state', [
            'matchReadiness' => $matchReadiness,
            'matchSettings' => $matchSettings,
            'matchCount' => $matchedJobs->count(),
            'applyAllCount' => $applyAllCount,
            'showApplyAll' => true,
        ])

        @if ($matchedJobs->isNotEmpty())
            <div class="space-y-4">
                @foreach ($matchedJobs as $job)
                    @php($alreadyApplied = in_array($job->id, $appliedJobIds, true))

                    {{-- Cards, not flat rows, and the Apply button sits at the
                         bottom after all the facts. Both are standing
                         preferences from Shantosh. --}}
                    <div class="bg-white rounded-2xl border border-border p-5 sm:p-6 shadow-xs space-y-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-base font-bold text-navy">{{ $job->title }}</h2>
                                    @include('seeker.partials.match-badge', ['job' => $job])
                                </div>
                                <p class="text-xs text-text-secondary">
                                    {{ $job->company?->name ?? 'Lucky Boss client' }}
                                    @if ($job->location) &middot; {{ $job->location }} @endif
                                </p>
                            </div>
                        </div>

                        @if (! empty($job->match_strengths))
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (array_slice($job->match_strengths, 0, 3) as $strength)
                                    <span class="px-2 py-1 rounded-lg text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $strength }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if (! empty($job->match_gaps))
                            <p class="text-[11px] text-text-muted">
                                Worth knowing: {{ implode('. ', array_slice($job->match_gaps, 0, 2)) }}
                            </p>
                        @endif

                        <div class="pt-3 border-t border-border flex flex-wrap items-center justify-between gap-3">
                            <a href="{{ route('jobs.show', $job) }}" class="text-xs font-bold text-accent hover:underline">View the full role &rarr;</a>

                            @if ($alreadyApplied)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Applied
                                </span>
                            @else
                                <form method="POST" action="{{ route('seeker.jobs.apply', $job) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm font-bold text-xs cursor-pointer">Apply</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- A list that just stops reads as broken to Shantosh, so it ends
                 deliberately. --}}
            <div class="text-center py-8 space-y-2 border-t border-border">
                <p class="text-xs text-text-muted">That&rsquo;s everything above {{ $matchSettings['minimum_match_score'] }}% today.</p>
                <a href="{{ route('jobs.index') }}" class="text-xs font-bold text-accent hover:underline">Browse all open jobs &rarr;</a>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <a href="{{ route('seeker.dashboard') }}" class="text-xs font-semibold text-text-muted hover:text-navy">Go to my dashboard</a>
            <span class="text-slate-300">&middot;</span>
            <a href="{{ route('seeker.resume.choose') }}" class="text-xs font-semibold text-text-muted hover:text-navy">Replace my resume</a>
        </div>
    </div>
</x-seeker-sidebar>
