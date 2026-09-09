{{--
    The people the hiring agent found.

    TickBig's equivalent is a wall of cards with every candidate's email address
    printed on the front. Ours does not do that, and the difference is
    deliberate: a phone number leaves our database only when an employer asks
    for it, one person at a time (spec §72), and it is free when that candidate
    applied to them first (§73).

    Everything on a card is something we actually hold. No card claims a score we
    could not compute, no card invents a skill, and a candidate with no CV
    attached says so rather than showing a dead download button.
--}}
<x-employer-shell title="Shortlist">

    <div class="max-w-5xl mx-auto space-y-6">

        <div class="space-y-2">
            <a href="{{ route('employer.chat.show', $conversation) }}" class="inline-block text-xs font-bold text-text-muted hover:text-navy">&larr; Back to the conversation</a>
            <h1 class="text-2xl font-heading font-extrabold text-navy">{{ $summary }}</h1>
            <p class="text-sm text-text-secondary">
                @if ($candidates->isEmpty())
                    Nobody on Lucky Boss is above {{ $threshold }}% for this yet.
                @else
                    {{ $candidates->count() }} {{ Str::plural('person', $candidates->count()) }} above {{ $threshold }}%, strongest first.
                @endif
            </p>
        </div>

        {{-- People, and what was asked for.

             TickBig lands a finished conversation on the same pair — Profiles
             next to a structured recap — and throws the chat bubbles away. That
             is the right call: nobody re-reads sixteen bubbles to check what
             they asked for. Ours differs in one way: their recap hides six of
             the sixteen answers it collected, including the mobile number, and
             we show everything we hold. --}}
        <div x-data="{ tab: 'people' }" class="space-y-6">

            <div class="flex items-center gap-2">
                <button type="button" @click="tab = 'people'"
                        :class="tab === 'people' ? 'bg-navy text-white' : 'bg-white text-navy border border-border'"
                        class="px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-colors">
                    People ({{ $candidates->count() }})
                </button>
                <button type="button" @click="tab = 'brief'"
                        :class="tab === 'brief' ? 'bg-navy text-white' : 'bg-white text-navy border border-border'"
                        class="px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-colors">
                    The brief
                </button>
            </div>

            {{-- The brief. Rendered server-side and simply hidden, so it is in
                 the page for anyone with scripting off and for a printout. --}}
            <div x-show="tab === 'brief'" x-cloak class="bg-white rounded-2xl border border-border shadow-xs p-5 sm:p-6">
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-text-muted mb-4">What you asked for</p>
                <dl class="space-y-3">
                    @foreach ($brief as $label => $value)
                        <div class="flex flex-wrap items-baseline gap-2 border-t border-border pt-3">
                            <dt class="text-xs font-bold text-text-muted w-40 shrink-0">{{ $label }}</dt>
                            <dd class="text-sm font-semibold text-navy min-w-0">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
                <div class="flex flex-wrap gap-3 pt-5">
                    <a href="{{ route('employer.chat.show', $conversation) }}" class="btn btn-outline btn-sm font-bold text-xs">
                        Read the conversation
                    </a>
                </div>
            </div>

            <div x-show="tab === 'people'" class="space-y-6">

        @if ($errors->has('reveal'))
            <p class="text-xs text-red-600 font-semibold">{{ $errors->first('reveal') }}</p>
        @endif

        @forelse ($candidates as $candidate)
            @php
                $profile = $candidate->candidateProfile;
                $isRevealed = in_array($candidate->id, $revealed, true);
                $isOrganic = in_array($candidate->id, $organic, true);
            @endphp

            <div class="bg-white rounded-2xl border border-border shadow-xs p-5 sm:p-6 space-y-4">

                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-heading font-extrabold text-navy truncate">{{ $candidate->name }}</h2>
                        <p class="text-sm text-accent font-bold">{{ $profile?->current_title ?: 'No job title on their profile yet' }}</p>
                        <p class="text-xs text-text-muted mt-1">
                            @php
                                $facts = array_filter([
                                    $profile?->years_experience !== null ? $profile->years_experience.' '.Str::plural('year', (int) $profile->years_experience).' experience' : null,
                                    $profile?->current_location ?: null,
                                ]);
                            @endphp
                            {{ $facts === [] ? 'Nothing else stated on their profile' : implode(' · ', $facts) }}
                        </p>
                    </div>

                    {{-- The score, and next to it how much of the profile it was
                         built from. A 90% from one dimension is not a 90%, and
                         the confidence figure is the only thing that says so. --}}
                    <div class="text-right shrink-0">
                        <p class="text-2xl font-heading font-extrabold text-navy leading-none">{{ $candidate->match_score }}%</p>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-text-muted mt-1">
                            {{ $candidate->match_confidence }}% of profile seen
                        </p>
                    </div>
                </div>

                @if (! empty($candidate->match_have) || ! empty($candidate->match_missing))
                    <div class="flex flex-wrap gap-2">
                        @foreach ($candidate->match_have as $skill)
                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-xs font-bold text-emerald-700 border border-emerald-200">{{ $skill }}</span>
                        @endforeach
                        @foreach ($candidate->match_missing as $skill)
                            <span class="px-3 py-1 rounded-full bg-surface text-xs font-bold text-text-muted border border-border line-through">{{ $skill }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-3 pt-1">
                    @if ($profile?->resume_path)
                        <a href="{{ asset($profile->resume_path) }}" target="_blank" rel="noopener"
                           class="btn btn-outline btn-sm font-bold text-xs">
                            Their CV
                        </a>
                    @else
                        <span class="text-xs text-text-muted font-semibold">No CV attached</span>
                    @endif

                    @if ($isRevealed)
                        {{-- Held already, so shown plainly. --}}
                        <span class="text-xs font-bold text-navy">{{ $candidate->phone ?: 'No phone on file' }}</span>
                        <span class="text-xs font-bold text-navy">{{ $candidate->email }}</span>
                    @else
                        <form method="POST" action="{{ route('employer.candidates.reveal', $candidate) }}"
                              x-data="{ confirming: false }">
                            @csrf
                            <button type="button" x-show="! confirming" @click="confirming = true"
                                    class="btn btn-primary btn-sm font-bold text-xs cursor-pointer">
                                Show contact details
                            </button>
                            <span x-show="confirming" x-cloak class="inline-flex items-center gap-3">
                                <button type="submit" class="btn btn-primary btn-sm font-bold text-xs">
                                    {{ $isOrganic ? 'Yes, show them (free)' : 'Yes, use 1 candidate view' }}
                                </button>
                                <button type="button" @click="confirming = false"
                                        class="text-xs font-bold text-text-muted hover:text-navy cursor-pointer">Cancel</button>
                            </span>
                        </form>

                        <span class="text-[11px] text-text-muted">
                            {{ $isOrganic ? 'They applied to you, so this is free.' : 'Uses one candidate view.' }}
                        </span>
                    @endif
                </div>

            </div>
        @empty
            <div class="bg-white rounded-2xl border border-border shadow-xs p-6 space-y-3">
                <p class="text-sm font-bold text-navy">Nobody cleared {{ $threshold }}% for this role.</p>
                <p class="text-sm text-text-secondary leading-relaxed">
                    That is a real answer, not a failure — it means the people currently on Lucky Boss
                    do not do this work, or have not filled in enough of their profile for us to say
                    that they do. Posting the vacancy is what changes it: the role goes on the board,
                    people apply, and the agent scores them as they arrive.
                </p>
                <div class="flex flex-wrap gap-3 pt-1">
                    <a href="{{ route('employer.jobs.create', ['from' => $conversation->id]) }}" class="btn btn-primary btn-sm font-bold text-xs">
                        Post it as a vacancy
                    </a>
                    <a href="{{ route('employer.chat.show', $conversation) }}" class="btn btn-outline btn-sm font-bold text-xs">
                        Change what I asked for
                    </a>
                </div>
            </div>
        @endforelse

        @if ($candidates->isNotEmpty())
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('employer.jobs.create', ['from' => $conversation->id]) }}" class="btn btn-primary btn-sm font-bold text-xs">
                    Post it as a vacancy too
                </a>
                <form method="POST" action="{{ route('employer.chat.start') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm font-bold text-xs">Hire for something else</button>
                </form>
            </div>
        @endif

            </div>{{-- /people --}}
        </div>{{-- /tabs --}}

    </div>
</x-employer-shell>
