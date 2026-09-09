{{--
    The motion and the in-place submit, shared by both agents.

    Plain CSS and plain JS: the Tailwind bundle is prebuilt with no Node step,
    so a utility invented here would not exist at runtime, and the transcript
    has to keep working with scripting off.

    The markup contract is three hooks — [data-chat-transcript],
    [data-chat-control], [data-chat-error] — and forms marked [data-chat-form].
    Any page that renders those gets the whole behaviour.
--}}
<style>
    /* The app's reveal, in a browser: 240ms, ease-out, rising into place. */
    @keyframes lb-rise-in {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .lb-rise { animation: lb-rise-in 0.24s cubic-bezier(0.16, 1, 0.3, 1) both; }

    /* The agent "thinking" between an answer and its next question. Three dots
       rather than a spinner: a spinner in a conversation reads as the page
       loading, which is the thing this screen exists to avoid. */
    .lb-typing { display: inline-flex; gap: 4px; align-items: center; }
    .lb-typing span {
        width: 6px; height: 6px; border-radius: 9999px;
        background: #9AA8BA; display: inline-block;
        animation: lb-blink 1.1s infinite ease-in-out both;
    }
    .lb-typing span:nth-child(2) { animation-delay: 0.16s; }
    .lb-typing span:nth-child(3) { animation-delay: 0.32s; }
    @keyframes lb-blink {
        0%, 80%, 100% { opacity: 0.25; transform: translateY(0); }
        40%           { opacity: 1;    transform: translateY(-3px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .lb-rise { animation: none; }
        .lb-typing span { animation: none; opacity: 0.6; }
    }

    /*
        The agent's mark: the spark on its own.

        Two goes at this. It started as `bg-accent/10` holding a `text-accent`
        sparkle — a blue glyph at 10% opacity behind it, at 36px — which sir read
        as an empty circle, fairly. The fix was a filled navy tile, and that was
        worse in a different way: a dark square is the heaviest object in a column
        of white bubbles, and it repeats down the transcript once per message.

        So there is no plate. The spark is the mark, drawn in the brand's own
        emerald-to-blue, and it sits in the margin the way a speaker's initial
        does rather than announcing itself. The size is held by the box, not the
        glyph, so the transcript rows still line up.

        SVG rather than the PNG sir asked for, and the reason is worth one line:
        at 30px a PNG needs a 2x and 3x asset to stay sharp, and this is two
        gradient-filled paths that scale for free and inherit no colour from
        anywhere. Nothing else about it differs from the mark he described.

        Written as CSS, not utilities, and this is not a style preference: the
        typing indicator is built in JavaScript as an HTML string, so the mark
        has to be one class name that both the Blade transcript and that string
        can use, or the placeholder and the real thing drift apart mid-answer.
    */
    .lb-agent-mark {
        width: 30px; height: 30px;
        display: grid; place-items: center;
        flex: none;
        margin-top: 4px;
    }
    .lb-agent-mark svg { width: 26px; height: 26px; display: block; }

    /*
        Answer chips.

        The confirm chip was `border-accent text-accent hover:bg-accent
        hover:text-white`, and hovering it made the label vanish. Probed rather
        than guessed: `.hover\:text-white:hover` is in the prebuilt bundle and
        `.hover\:bg-accent:hover` is **not** — no view used it before the bundle
        was compiled on 2026-08-27 — so the text turned white and the background
        stayed transparent. White on white. The button was still there and still
        worked, which is what made it so confusing to report.

        Every chip state is defined here now, so no answer control depends on a
        Tailwind variant that may or may not have survived the freeze.
    */
    .lb-chip {
        padding: 10px 20px;
        border-radius: 9999px;
        border: 1px solid var(--color-border);
        font-size: 14px;
        font-weight: 700;
        background: #fff;
        color: #031F49;
        cursor: pointer;
        transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    }
    .lb-chip:hover { border-color: #2563EB; color: #2563EB; background: #F5F8FF; }
    .lb-chip:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(37, 99, 235, .35); }

    /* The one action on the screen, so it is filled, in the same emerald as
       every other primary button on the site. */
    .lb-chip-primary {
        padding: 10px 26px;
        background: #18A66A;
        border-color: #18A66A;
        color: #fff;
    }
    .lb-chip-primary:hover { background: #0F8A56; border-color: #0F8A56; color: #fff; }

    /* Chosen, in a multi-select. */
    .lb-chip-on { border-color: #2563EB; background: #EAF1FE; color: #2563EB; }
    .lb-chip-on:hover { background: #DEE9FD; }

    /* The escape hatch chips — "Somewhere else", "Skip this". Quieter, but never
       invisible: they are still a real answer. */
    .lb-chip-muted { color: #6E829C; }
</style>

<script>
(function () {
    if (!window.fetch) return;   // plain forms still work

    // The same spark the Blade transcript draws. Kept here as one string so the
    // "thinking" row and the answered row cannot show two different marks — the
    // gradient needs a document-unique id, and this one is only ever inserted
    // where the Blade copy is not.
    var AGENT_SPARK =
        '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">' +
        '<defs><linearGradient id="lbSparkJs" x1="3" y1="21" x2="21" y2="3" gradientUnits="userSpaceOnUse">' +
        '<stop stop-color="#18A66A"/><stop offset="1" stop-color="#2563EB"/></linearGradient></defs>' +
        '<path d="M12 2.6l1.72 5.02a4.2 4.2 0 002.66 2.66L21.4 12l-5.02 1.72a4.2 4.2 0 00-2.66 2.66L12 21.4l-1.72-5.02a4.2 4.2 0 00-2.66-2.66L2.6 12l5.02-1.72a4.2 4.2 0 002.66-2.66L12 2.6z" fill="url(#lbSparkJs)"/>' +
        '<path d="M18.7 2.4l.62 1.78 1.78.62-1.78.62-.62 1.78-.62-1.78-1.78-.62 1.78-.62.62-1.78z" fill="#18A66A" opacity=".85"/>' +
        '</svg>';

    // Bound once per document. Soft navigation re-executes the scripts inside
    // <main>, so without this guard a second listener attaches on every visit:
    // one tap then fired two requests, the first advancing the conversation and
    // the second being refused for answering a question that had already moved
    // on. One listener serves both agents — they never share a page.
    if (window.__lbChatBound) return;
    window.__lbChatBound = true;

    var busy = false;

    // Resolved per submit, never captured: after a soft navigation the nodes
    // captured at bind time belong to the previous, detached page.
    function transcriptEl() { return document.querySelector('[data-chat-transcript]'); }
    function controlEl() { return document.querySelector('[data-chat-control]'); }
    function errorEl() { return document.querySelector('[data-chat-error]'); }

    function bubble(text) {
        var wrap = document.createElement('div');
        wrap.className = 'lb-rise flex justify-end';
        wrap.innerHTML = '<div class="bg-navy rounded-2xl px-5 py-3 max-w-xs"><p class="text-sm font-semibold text-white"></p></div>';
        wrap.querySelector('p').textContent = text;
        return wrap;
    }

    function typing() {
        var wrap = document.createElement('div');
        wrap.className = 'lb-rise flex items-start gap-3';
        wrap.setAttribute('data-typing', '');
        wrap.innerHTML =
            '<span class="lb-agent-mark">' + AGENT_SPARK + '</span>' +
            '<div class="bg-white rounded-2xl border border-border shadow-xs px-5 py-4">' +
            '<span class="lb-typing"><span></span><span></span><span></span></span></div>';
        return wrap;
    }

    /**
     * What the person actually said, for the optimistic bubble.
     *
     * The pressed chip carries name="answer", so its label is the answer. A
     * plain Send button carries no name, and reading its text put the word
     * "Send" in the bubble for a heartbeat before the server's transcript
     * replaced it — so in that case read the field instead. The disabled
     * filter matters for the "somewhere else" input, which sits in the same
     * form as the chips and is disabled while hidden.
     */
    function saidBy(form, submitter) {
        if (submitter && submitter.name === 'answer') {
            return (submitter.textContent || submitter.value || '').trim();
        }
        var field = form.querySelector('input[name=answer]:not([disabled])');
        return field ? (field.value || '').trim() : '';
    }

    /**
     * Bring the newest exchange into view, a little above centre.
     *
     * Straight from revealNextQuestion() in the app: the delay lets the reveal
     * finish expanding, because measuring before that scrolls to where the
     * question was about to be rather than where it ends up. The inline
     * scroll-behavior is required — the prebuilt bundle sets
     * `scroll-behavior: smooth` on <html>, and while that is in force
     * scrollTo is ignored entirely.
     */
    function reveal(el) {
        window.setTimeout(function () {
            if (!el || !el.getBoundingClientRect) return;
            var de = document.documentElement;
            var prev = de.style.scrollBehavior;
            de.style.scrollBehavior = 'auto';

            var target = el.getBoundingClientRect().top + window.scrollY - (window.innerHeight * 0.28);
            if (target < 0) target = 0;

            var start = window.scrollY;
            var distance = target - start;
            var began = Date.now();

            (function step() {
                var t = Math.min((Date.now() - began) / 340, 1);
                var eased = t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
                window.scrollTo(0, start + distance * eased);
                if (t < 1) window.setTimeout(step, 16);
                else de.style.scrollBehavior = prev;
            })();
        }, 160);
    }

    document.addEventListener('submit', function (e) {
        var form = e.target.closest ? e.target.closest('form[data-chat-form]') : null;
        if (!form || busy) return;

        var root = transcriptEl();
        var slot = controlEl();
        var errorLine = errorEl();
        if (!root || !slot) return;

        e.preventDefault();
        busy = true;
        if (errorLine) errorLine.hidden = true;

        var body = new FormData(form);
        // FormData omits the pressed button's name/value, and every chip is a
        // <button name="answer" value="…">.
        if (e.submitter && e.submitter.name && !body.has(e.submitter.name)) {
            body.append(e.submitter.name, e.submitter.value);
        }

        // The answer appears immediately, before the round trip. The server
        // still decides what it means — this is only their own words going up
        // where they expect them.
        var said = saidBy(form, e.submitter);
        if (said) root.appendChild(bubble(said));

        slot.innerHTML = '';
        var wait = typing();
        root.appendChild(wait);
        reveal(wait);

        fetch(form.action, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'X-Chat-Async': '1', 'Accept': 'application/json' }
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
            .then(function (data) {
                wait.remove();

                // The agent handed this conversation to another screen — the
                // employer chose to write the advert themselves. A real
                // navigation, not a swap: it is a different page with a
                // different shell.
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }

                if (data.error) {
                    // The optimistic bubble was wrong, so take it back.
                    var last = root.querySelector('.flex.justify-end:last-child');
                    if (last) last.remove();
                    if (errorLine) { errorLine.textContent = data.error; errorLine.hidden = false; }
                    slot.innerHTML = data.control || '';
                    if (window.Alpine && window.Alpine.initTree) window.Alpine.initTree(slot);
                    busy = false;
                    return;
                }

                // Replace the optimistic bubble with the server's wording —
                // "3–5 years" rather than the 4 it stored.
                root.innerHTML = data.transcript;
                slot.innerHTML = data.control || '';

                if (window.Alpine && window.Alpine.initTree) {
                    window.Alpine.initTree(slot);
                }

                reveal(slot);
                busy = false;
            })
            .catch(function () {
                // Anything unexpected falls back to a real submit, so an answer
                // is never silently lost.
                wait.remove();
                form.removeAttribute('data-chat-form');
                form.submit();
            });
    });
})();
</script>
