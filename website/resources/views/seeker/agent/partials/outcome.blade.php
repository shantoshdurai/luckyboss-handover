{{-- Finished: the payoff, with a real number.

     The count is what the matcher actually produced, never a rounded-up
     promise. Zero is a real answer and leads somewhere useful — adding a
     resume is the fastest way to change it. --}}
<div class="flex flex-wrap gap-3 pt-1 lb-rise">
    @if ($outcome['count'] > 0)
        <a href="{{ route('seeker.resume.matches') }}" class="btn btn-primary btn-sm font-bold text-xs">
            Show me the {{ $outcome['count'] }} {{ Str::plural('job', $outcome['count']) }}
        </a>
    @else
        <a href="{{ route('seeker.resume.choose') }}" class="btn btn-primary btn-sm font-bold text-xs">Add my resume</a>
    @endif
    <form method="POST" action="{{ route('seeker.chat.start') }}">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm font-bold text-xs">Start again</button>
    </form>
</div>
