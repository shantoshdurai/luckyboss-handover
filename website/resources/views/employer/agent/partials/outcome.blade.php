{{-- The end of the hiring conversation.

     Two doors, and which one leads is decided by whether anybody actually
     matched. When nobody did, "show me the people" would be a button to an
     empty page, so posting the vacancy leads instead — that is the thing worth
     doing when the people you want have not joined yet. --}}
<div class="flex flex-wrap gap-3 pt-1 lb-rise">
    @if ($outcome['count'] > 0)
        <a href="{{ route('employer.chat.shortlist', $conversation) }}" class="btn btn-primary btn-sm font-bold text-xs">
            Show me the {{ $outcome['count'] }} {{ Str::plural('person', $outcome['count']) }}
        </a>
        <a href="{{ route('employer.jobs.create', ['from' => $conversation->id]) }}" class="btn btn-outline btn-sm font-bold text-xs">
            Post it as a vacancy
        </a>
    @else
        <a href="{{ route('employer.jobs.create', ['from' => $conversation->id]) }}" class="btn btn-primary btn-sm font-bold text-xs">
            Post it as a vacancy
        </a>
        <a href="{{ route('employer.chat.shortlist', $conversation) }}" class="btn btn-outline btn-sm font-bold text-xs">
            See what I checked
        </a>
    @endif

    <form method="POST" action="{{ route('employer.chat.start') }}">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm font-bold text-xs">Hire for something else</button>
    </form>
</div>
