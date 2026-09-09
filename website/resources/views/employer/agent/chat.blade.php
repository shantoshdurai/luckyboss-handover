{{--
    The hiring conversation.

    Answers are taken in place — no page load between questions. Same machinery
    as the candidate's Lucky AI, which is the point: one transcript template, one
    control, one submit handler, so the two agents cannot drift into behaving
    differently.

    Still a plain form underneath. With no JavaScript every chip is an ordinary
    POST that re-renders the page and the conversation works exactly as before;
    the script only removes the reload.
--}}
<x-employer-shell title="Hiring AI" :footer="false">

    @include('agent.partials.assets')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-5" style="min-height: calc(100vh - 64px);">

        <div class="flex items-center justify-between gap-4 flex-wrap">
            <a href="{{ route('employer.home') }}" class="inline-block text-xs font-bold text-text-muted hover:text-navy">&larr; Back</a>

            @include('agent.partials.usage', [
                'rows' => $usage,
                'manageUrl' => route('employer.subscription'),
            ])
        </div>

        <div class="space-y-4" data-chat-transcript>
            @include('agent.partials.messages', ['messages' => $messages])
        </div>

        <p class="text-xs text-red-600 font-semibold" data-chat-error hidden></p>

        @error('answer')
            <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
        @enderror

        <div data-chat-control>
            @include('agent.partials.control', [
                'action' => route('employer.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $question,
                'options' => $options,
                'outcome' => $outcome,
                'outcomeView' => 'employer.agent.partials.outcome',
            ])
        </div>

    </div>
</x-employer-shell>
