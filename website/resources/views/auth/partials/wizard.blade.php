{{--
    Shared behaviour for the multi-step registration forms.

    The pattern is taken from tickbig.com, which sir asked us to match after
    comparing it to our own sign-up. Walking their flow directly, what makes it
    feel calm is not the dark theme — it is that a step never shows more than
    about four fields, and the control for the next answer does not exist until
    the previous one has been given. Ours put ten inputs on one screen.

    Deliberately one form and one POST, not a session-backed wizard:

      - server validation is unchanged, so RegisterSeekerRequest and
        RegisterEmployerRequest stay the single source of truth;
      - there is no half-created account to clean up if someone leaves midway;
      - no new routes, and the existing registration tests keep passing.

    Stepping is presentation only. If the server rejects the submission the view
    computes which step holds the first failing field and opens there, so an
    error can never be reported on a panel the user cannot see.

    ## Why this is plain JavaScript and not Alpine

    It was written with Alpine first. The documented way to register a component
    — `Alpine.data()` inside an `alpine:init` listener — throws here, because
    this project's prebuilt bundle keeps Alpine module-scoped and only assigns
    `window.Alpine` after `alpine:init` has fired. That exception aborts Alpine's
    initialisation for the whole page, so every directive on it evaluated once
    and then froze.

    Alpine itself is fine — inline `x-data` toggles work elsewhere on the site,
    and a `window.authWizard` factory would have worked too. Plain JS was kept
    for a better reason: it degrades. Every panel is rendered visible by the
    server, so with no JavaScript at all this is an ordinary long registration
    form that submits and works. The script's first act is to hide the steps it
    is taking responsibility for, so it can only ever hide fields it can also
    show again.

--}}
@once
    @push('head')
        <script>
            (function () {
                function setUp(root) {
                    var panels = root.querySelectorAll('[data-wizard-panel]');
                    if (panels.length < 2) { return; }

                    var current = parseInt(root.getAttribute('data-wizard-start'), 10) || 1;
                    var total = panels.length;

                    function render() {
                        for (var i = 0; i < panels.length; i++) {
                            var n = parseInt(panels[i].getAttribute('data-wizard-panel'), 10);
                            panels[i].hidden = (n !== current);
                        }

                        var titles = root.querySelectorAll('[data-wizard-title]');
                        for (var t = 0; t < titles.length; t++) {
                            titles[t].hidden =
                                parseInt(titles[t].getAttribute('data-wizard-title'), 10) !== current;
                        }

                        var labels = root.querySelectorAll('[data-wizard-current]');
                        for (var l = 0; l < labels.length; l++) {
                            labels[l].textContent = current;
                        }

                        var bars = root.querySelectorAll('[data-wizard-bar]');
                        for (var b = 0; b < bars.length; b++) {
                            var reached = parseInt(bars[b].getAttribute('data-wizard-bar'), 10) <= current;
                            bars[b].classList.toggle('bg-secondary-500', reached);
                            bars[b].classList.toggle('bg-border', !reached);
                        }
                    }

                    function panelFor(n) {
                        return root.querySelector('[data-wizard-panel="' + n + '"]');
                    }

                    /*
                        The browser's own constraint validation, not a second
                        hand-written copy of it: `required`, `type="email"` and
                        `minlength` are already declared on the inputs.

                        Only this step's fields are checked — that is the whole
                        point — and because every field stays in the DOM, the
                        final submit still validates all of them.
                    */
                    function stepIsValid(n) {
                        var fields = panelFor(n).querySelectorAll('input, select, textarea');
                        for (var i = 0; i < fields.length; i++) {
                            if (!fields[i].checkValidity()) {
                                fields[i].reportValidity();
                                return false;
                            }
                        }
                        return true;
                    }

                    function move(to) {
                        current = to;
                        render();

                        // Focus the new step's first field. Without this someone
                        // tabbing through lands back at the top of the document
                        // on every step change, and a screen reader is never
                        // told that anything happened.
                        var first = panelFor(current).querySelector('input, select, textarea');
                        if (first) { first.focus(); }
                    }

                    root.addEventListener('click', function (event) {
                        var next = event.target.closest('[data-wizard-next]');
                        if (next) {
                            event.preventDefault();
                            if (current < total && stepIsValid(current)) { move(current + 1); }
                            return;
                        }

                        var back = event.target.closest('[data-wizard-back]');
                        if (back) {
                            event.preventDefault();
                            if (current > 1) { move(current - 1); }
                        }
                    });

                    // Enter inside a field should advance the step rather than
                    // submit a form the user has not finished filling in.
                    root.addEventListener('keydown', function (event) {
                        if (event.key !== 'Enter' || current >= total) { return; }
                        if (!event.target.matches('input')) { return; }

                        event.preventDefault();
                        if (stepIsValid(current)) { move(current + 1); }
                    });

                    render();
                }

                function init() {
                    var roots = document.querySelectorAll('[data-wizard]');
                    for (var i = 0; i < roots.length; i++) { setUp(roots[i]); }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
        </script>
    @endpush
@endonce
