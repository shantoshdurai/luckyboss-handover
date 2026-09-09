{{--
    The linear conversation.

    Answers are taken in place — no page load between questions. Modelled on the
    Flutter app's onboarding, which is the better pattern and was already ours:
    `RevealedField` wraps each question in an AnimatedSize so answering one
    expands the next below it, and `revealNextQuestion()` then scrolls it to just
    above centre so its answers are on screen with it. Nothing navigates, so a
    mis-tap is never a page you have to come back from.

    The motion, the transcript and the answer control all live in
    `resources/views/agent/` and are shared with the employer's hiring agent.
    They were duplicated for about a day, which was long enough to see the two
    start drifting.

    Still a plain form underneath. With no JavaScript every chip is an ordinary
    POST that re-renders the page and the conversation works exactly as before —
    the script only removes the reload.
--}}
<x-seeker-sidebar title="Lucky AI" :footer="false">

    @include('agent.partials.assets')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-8 space-y-5" style="min-height: calc(100vh - 64px);">

        <a href="{{ route('seeker.home') }}" class="inline-block text-xs font-bold text-text-muted hover:text-navy">&larr; Back</a>

        <div class="space-y-4" data-chat-transcript>
            @include('agent.partials.messages', ['messages' => $messages])
        </div>

        <p class="text-xs text-red-600 font-semibold" data-chat-error hidden></p>

        @error('answer')
            <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
        @enderror

        <div data-chat-control>
            @include('agent.partials.control', [
                'action' => route('seeker.chat.answer', $conversation),
                'conversation' => $conversation,
                'question' => $question,
                'options' => $options,
                'outcome' => $outcome,
                'outcomeView' => 'seeker.agent.partials.outcome',
            ])
        </div>

    </div>
</x-seeker-sidebar>
