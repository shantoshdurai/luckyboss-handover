{{--
    The fit percentage on a job row.

    Colour tracks the number honestly — a 62% must not look like a 94%, or the
    threshold sir asked for stops meaning anything to the person reading it.

    `confidence` is how much of the profile we could actually compare. A 90%
    built from skills alone is not the same claim as a 90% built from skills,
    experience, location and pay, and the tooltip says which one this is.

    @param \App\Models\Job $job  carrying match_score / match_confidence / match_strengths
--}}
@php
    $score = $job->match_score ?? null;
    $tone = match (true) {
        $score === null => 'bg-slate-100 text-slate-600 border-slate-200',
        $score >= 85 => 'bg-emerald-50 text-emerald-700 border-emerald-300',
        $score >= 70 => 'bg-accent/10 text-accent border-accent/30',
        default => 'bg-amber-50 text-amber-700 border-amber-300',
    };
    $reason = collect($job->match_strengths ?? [])->take(2)->implode('. ');
@endphp

@if($score !== null)
    <span class="px-2 py-0.5 rounded border text-[10px] font-bold {{ $tone }}"
          title="{{ $reason ?: 'Scored against your profile' }}{{ ($job->match_confidence ?? 100) < 60 ? ' — based on limited profile data' : '' }}">
        {{ $score }}% match
    </span>
@endif
