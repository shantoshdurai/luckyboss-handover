{{--
    The state of matching for this candidate, shown above every job list.

    Three states, and the first one is the reason this partial exists: until we
    know something about the candidate there is nothing to recommend, so the
    page asks for a resume instead of listing vacancies under a heading that
    claims they were chosen for them.

    @param array $matchReadiness  ['ready' => bool, 'missing' => list<string>]
    @param array $matchSettings   ['minimum_match_score' => int, 'bulk_apply_enabled' => bool, ...]
    @param int   $matchCount      how many jobs cleared the threshold
    @param bool  $showApplyAll    only on the full matched list, not the 6-item preview
--}}
@php($showApplyAll = $showApplyAll ?? false)
{{-- How many Apply All would actually send: matches minus the ones already
     applied to, capped by the admin limit. Falls back to the match count for
     callers that do not work it out, but offering "Apply to all 2" when both
     have already been applied to is a button that does nothing. --}}
@php($applyAllCount = $applyAllCount ?? $matchCount)

@if(! $matchReadiness['ready'])
    {{-- Nothing to match on. Ask for the one thing that fixes it fastest. --}}
    <div class="rounded-2xl border border-accent/30 bg-accent/5 p-5 space-y-3">
        <div class="flex items-start gap-3">
            <span class="shrink-0 w-9 h-9 rounded-xl bg-accent/10 text-accent grid place-items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.9A5 5 0 1115.9 6H16a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
            </span>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-navy">Upload your resume to see your matches</h3>
                <p class="text-xs text-text-secondary leading-relaxed">
                    We do not have enough about you yet to say which jobs fit, so we are not going to guess.
                    Upload your resume and we will read it, or add
                    <strong>{{ implode(', ', $matchReadiness['missing']) }}</strong> to your profile by hand.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2 pl-12">
            <a href="{{ route('seeker.resume.choose') }}" class="btn btn-primary btn-sm font-bold text-xs">Upload resume</a>
            <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-sm font-bold text-xs">Browse all jobs instead</a>
        </div>
    </div>
@elseif($matchCount === 0)
    {{-- We can match, nothing cleared the bar. Say so plainly, and say what the bar is. --}}
    <div class="rounded-2xl border border-border bg-slate-50 p-5 space-y-2">
        <h3 class="text-sm font-bold text-navy">No jobs above {{ $matchSettings['minimum_match_score'] }}% right now</h3>
        <p class="text-xs text-text-secondary">
            We only show vacancies that actually fit your profile, so this list is empty rather than padded.
            New jobs are posted daily — or widen your search by browsing everything.
        </p>
        <a href="{{ route('jobs.index') }}" class="btn btn-outline btn-sm font-bold text-xs mt-1">Browse all jobs</a>
    </div>
@elseif($showApplyAll && $matchSettings['bulk_apply_enabled'] && $applyAllCount > 0)
    {{--
        Apply All. Guarded on the client too: it applies to real employers on
        the candidate's behalf, so it must never happen on a stray tap.
    --}}
    <div x-data="{ confirming: false }" class="rounded-2xl border border-border bg-white p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-navy">
                    {{ $matchCount }} {{ Str::plural('job', $matchCount) }} above {{ $matchSettings['minimum_match_score'] }}% match
                </h3>
                <p class="text-xs text-text-muted mt-0.5">Apply to them one by one, or send them all at once.</p>
            </div>

            <div class="shrink-0">
                <button type="button" x-show="! confirming" @click="confirming = true"
                        class="btn btn-primary btn-sm font-bold text-xs">
                    Apply to all {{ min($applyAllCount, $matchSettings['bulk_apply_limit']) }}
                </button>

                <form method="POST" action="{{ route('seeker.jobs.apply-all') }}" x-show="confirming" x-cloak
                      class="flex items-center gap-2">
                    @csrf
                    <span class="text-xs text-text-secondary">Send {{ min($applyAllCount, $matchSettings['bulk_apply_limit']) }} applications?</span>
                    <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">Yes, apply</button>
                    <button type="button" @click="confirming = false" class="btn btn-outline btn-sm font-bold text-xs">Cancel</button>
                </form>
            </div>
        </div>

        @if($applyAllCount > $matchSettings['bulk_apply_limit'])
            <p class="text-[11px] text-text-muted mt-3 pt-3 border-t border-border">
                Your best {{ $matchSettings['bulk_apply_limit'] }} matches are sent in one go. Come back for the rest.
            </p>
        @endif
    </div>
@endif
