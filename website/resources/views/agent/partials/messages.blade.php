{{-- One transcript row. Rendered by the page on load and by the async answer
     endpoint afterwards, so a message added without a page load is built from
     the same template as one that was there when the page opened.

     Shared by both agents — the candidate's Lucky AI and the employer's hiring
     agent. Two copies of a transcript template would drift, and the half that
     drifted would be the one nobody was looking at. --}}
@foreach ($messages as $message)
    @if ($message->role === 'agent')
        <div class="lb-rise flex items-start gap-3">
            {{-- The spark, filled with the brand gradient and standing on
                 nothing. See agent/partials/assets.blade.php for why it has no
                 plate behind it. The gradient id is per-row (`$loop->index`)
                 because two <defs> sharing an id in one document is undefined
                 behaviour, and browsers resolve it to whichever came first. --}}
            <span class="lb-agent-mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <defs>
                        <linearGradient id="lbSpark{{ $loop->index }}" x1="3" y1="21" x2="21" y2="3" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#18A66A"/>
                            <stop offset="1" stop-color="#2563EB"/>
                        </linearGradient>
                    </defs>
                    <path d="M12 2.6l1.72 5.02a4.2 4.2 0 002.66 2.66L21.4 12l-5.02 1.72a4.2 4.2 0 00-2.66 2.66L12 21.4l-1.72-5.02a4.2 4.2 0 00-2.66-2.66L2.6 12l5.02-1.72a4.2 4.2 0 002.66-2.66L12 2.6z"
                          fill="url(#lbSpark{{ $loop->index }})"/>
                    <path d="M18.7 2.4l.62 1.78 1.78.62-1.78.62-.62 1.78-.62-1.78-1.78-.62 1.78-.62.62-1.78z"
                          fill="#18A66A" opacity=".85"/>
                </svg>
            </span>
            <div class="bg-white rounded-2xl border border-border shadow-xs px-5 py-3.5 min-w-0">
                <p class="text-sm text-navy leading-relaxed">{{ $message->body }}</p>
            </div>
        </div>
    @else
        <div class="lb-rise flex justify-end">
            <div class="bg-navy rounded-2xl px-5 py-3 max-w-xs">
                <p class="text-sm font-semibold text-white">{{ $message->body }}</p>
            </div>
        </div>
    @endif
@endforeach
