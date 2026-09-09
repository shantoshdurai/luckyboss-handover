{{--
    Auto-apply — the candidate's own control.

    Two things this page must never do, both of which it would be easy to do:
      1. Claim to be running when the platform switch is off.
      2. Show an empty run log as if it meant "nothing matched". The log
         records every run with its reason, and this page prints the reason.
--}}
<x-seeker-sidebar title="Auto Apply">
    @php
        $platformOn = $matching['auto_apply_enabled'];
        $floor = $setting->effectiveMinimumScore($matching['minimum_match_score']);
        $remainingToday = max(0, $setting->daily_limit - $appliedToday);
        $live = $platformOn && $setting->enabled && $readiness['ready'];
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-10 space-y-6">

        <div class="space-y-2">
            <h1 class="text-2xl font-heading font-extrabold text-navy">Apply while you work</h1>
            <p class="text-sm text-text-secondary leading-relaxed">
                Switch this on and we look for new vacancies every night, score them against your profile,
                and send your application to the ones that fit. You wake up to applications already in.
                Every one of them shows in <span class="font-semibold text-navy">My Applications</span>, and you can withdraw any of them.
            </p>
        </div>

        {{-- The status line. It states what is actually true right now, and
             names the one thing standing in the way when something is. --}}
        <div @class([
            'rounded-2xl px-5 py-4 border flex items-center justify-between gap-4',
            'border-emerald-200 bg-emerald-50' => $live,
            'border-slate-200 bg-slate-50' => ! $live,
        ])>
            <div>
                <p @class(['text-sm font-bold', 'text-emerald-800' => $live, 'text-navy' => ! $live])>
                    {{ $live ? 'Auto-apply is running for you' : 'Auto-apply is not running' }}
                </p>
                <p class="text-xs text-text-muted mt-0.5 leading-relaxed">
                    @if ($live)
                        Next run tonight. Up to {{ $setting->daily_limit }} a day, at {{ $floor }}% match and above.
                    @elseif (! $platformOn)
                        Lucky Boss has automatic applying switched off across the platform. Your settings below are saved and will take effect when it is turned on.
                    @elseif (! $readiness['ready'])
                        We cannot score you accurately yet, so we will not apply on your behalf.
                    @else
                        You have it switched off.
                    @endif
                </p>
            </div>
            @if ($live)
                <span class="shrink-0 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">On</span>
            @endif
        </div>

        {{-- Readiness. Same rule as everywhere else: we ask for what is missing
             rather than applying on a profile we would have to guess at. --}}
        @if (! $readiness['ready'])
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
                <p class="text-sm font-bold text-navy">Finish your profile first</p>
                <p class="text-xs text-text-muted mt-1 leading-relaxed">
                    We will not send applications in your name on a profile we cannot score honestly &mdash;
                    an employer receiving one would have nothing to read.
                    @if (! empty($readiness['missing']))
                        Still needed: <span class="font-semibold text-navy">{{ implode(', ', $readiness['missing']) }}</span>.
                    @endif
                </p>
                <a href="{{ route('seeker.resume.choose') }}" class="btn btn-primary btn-sm font-bold text-xs mt-3">Upload my resume</a>
            </div>
        @endif

        {{-- Settings --}}
        <form method="POST" action="{{ route('seeker.auto-apply.update') }}" class="bg-white rounded-2xl border border-border shadow-sm p-6 sm:p-8 space-y-6">
            @csrf
            @method('PUT')

            <label class="flex items-start gap-3 cursor-pointer group">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1"
                       @checked(old('enabled', $setting->enabled))
                       class="mt-0.5 w-4 h-4 rounded border-slate-300 text-accent focus:ring-accent cursor-pointer">
                <span>
                    <span class="block text-sm font-bold text-navy group-hover:text-accent">Apply to matching jobs for me</span>
                    <span class="block text-[11px] text-text-muted mt-0.5 leading-relaxed">
                        You are giving Lucky Boss permission to submit applications in your name. Turn it off at any time; nothing already sent is withdrawn automatically.
                    </span>
                </span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="minimum_score" class="block text-xs font-bold text-navy mb-2">Only apply at or above</label>
                    <input type="number" id="minimum_score" name="minimum_score"
                           min="{{ $matching['minimum_match_score'] }}" max="95" step="1"
                           value="{{ old('minimum_score', $setting->minimum_score) }}"
                           placeholder="{{ $matching['minimum_match_score'] }}"
                           class="form-input font-mono">
                    <p class="text-[11px] text-text-muted mt-2 leading-relaxed">
                        Leave blank to use the Lucky Boss minimum of {{ $matching['minimum_match_score'] }}%. Raising it means fewer, better-fitting applications.
                    </p>
                    @error('minimum_score')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="daily_limit" class="block text-xs font-bold text-navy mb-2">Most applications per day</label>
                    <input type="number" id="daily_limit" name="daily_limit"
                           min="1" max="25" step="1"
                           value="{{ old('daily_limit', $setting->daily_limit) }}"
                           class="form-input font-mono" required>
                    <p class="text-[11px] text-text-muted mt-2 leading-relaxed">
                        1&ndash;25. {{ $appliedToday }} sent today, {{ $remainingToday }} left.
                    </p>
                    @error('daily_limit')<p class="text-[11px] text-red-600 mt-1 font-semibold">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
                <p class="text-[11px] text-text-muted leading-relaxed">
                    We only ever apply to vacancies posted on Lucky Boss. We do not sign in to,
                    or apply on, LinkedIn, Naukri, Indeed or any other job board on your behalf.
                </p>
                <button type="submit" class="btn btn-primary btn-sm font-bold text-xs shrink-0">Save settings</button>
            </div>
        </form>

        {{-- Run it now. The overnight schedule is the product; this is how you
             see it work without waiting for 2am. --}}
        @if ($live)
            <form method="POST" action="{{ route('seeker.auto-apply.run') }}"
                  class="bg-white rounded-2xl border border-border shadow-sm px-5 py-4 flex flex-wrap items-center justify-between gap-4">
                @csrf
                <div>
                    <p class="text-sm font-bold text-navy">Do not wait for tonight</p>
                    <p class="text-xs text-text-muted mt-0.5">Run a search and send now, against today's remaining {{ $remainingToday }}.</p>
                </div>
                <button type="submit" class="btn btn-outline btn-sm font-bold text-xs shrink-0" @disabled($remainingToday < 1)>Run now</button>
            </form>
        @endif

        {{-- The log. Plain, like TickBig's Name/Remaining table: the question is
             "what did you do for me", and the answer is a list of what happened. --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-border">
                <h2 class="text-sm font-heading font-bold text-navy">What we have done for you</h2>
                <p class="text-[11px] text-text-muted mt-0.5">Every run, including the ones that sent nothing and why.</p>
            </div>

            @if ($runs->isEmpty())
                <p class="px-5 py-6 text-xs text-text-muted">
                    Nothing yet. Once auto-apply is on, each night's run is listed here.
                </p>
            @else
                {{-- Row borders are drawn per row, not with divide-y: that
                     utility is absent from the prebuilt CSS bundle and fails
                     silently, leaving a flat undivided list. See CLAUDE.md. --}}
                <div>
                    @foreach ($runs as $run)
                        <div @class(['px-5 py-3 flex items-center justify-between gap-4', 'border-t border-border' => ! $loop->first])>
                            <div>
                                <p class="text-xs font-semibold text-navy">{{ $run->summary() }}</p>
                                <p class="text-[11px] text-text-muted mt-0.5">
                                    {{ $run->ran_at?->format('D j M, H:i') }}
                                    @if ($run->considered > 0)
                                        &middot; {{ $run->considered }} {{ Str::plural('vacancy', $run->considered) }} checked
                                    @endif
                                </p>
                            </div>
                            @if ($run->applied > 0)
                                <span class="shrink-0 text-sm font-extrabold text-emerald-700">+{{ $run->applied }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-seeker-sidebar>
