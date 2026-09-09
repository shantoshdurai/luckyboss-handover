{{--
    The clear control on a saved-conversation row.

    Plain CSS for the usual reason — the Tailwind bundle is prebuilt with no Node
    step, so `group-hover:` on a class nobody used before 2026-08-27 would resolve
    to nothing and the button would either never appear or never hide.

    Three things this has to get right:

      - **It reveals on hover *and* on keyboard focus.** Hidden behind hover
        alone, the only way to clear a conversation would be with a mouse.
      - **It is always visible where there is no hover.** On a phone `:hover`
        either never fires or sticks after a tap, and our candidates are mostly on
        phones — so on a touch screen the control simply sits there.
      - **The date moves out of its way.** The row's right edge already carries
        "Unfinished · 7 minutes ago"; without the padding the × lands on top of it.
--}}
<style>
    .lb-histrow { position: relative; }

    .lb-histrow .lb-hist-x {
        position: absolute;
        top: 50%;
        right: 8px;
        transform: translateY(-50%);
        opacity: 0;
        transition: opacity .15s ease;
    }

    .lb-histrow:hover .lb-hist-x,
    .lb-histrow:focus-within .lb-hist-x { opacity: 1; }

    /* Make room for it only while it is showing, so a row at rest is not
       permanently indented around an invisible button. */
    .lb-histrow:hover .lb-hist-meta,
    .lb-histrow:focus-within .lb-hist-meta { padding-right: 22px; }

    .lb-histrow .lb-hist-x button {
        width: 24px;
        height: 24px;
        border-radius: 9999px;
        border: 1px solid var(--color-border);
        background: #fff;
        color: #6E829C;
        font-size: 15px;
        line-height: 1;
        font-weight: 700;
        cursor: pointer;
        display: grid;
        place-items: center;
    }

    .lb-histrow .lb-hist-x button:hover {
        background: #FDECEA;
        border-color: #F5C6C0;
        color: #A3341F;
    }

    .lb-histrow .lb-hist-x button:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .35);
    }

    @media (hover: none) {
        .lb-histrow .lb-hist-x { opacity: 1; }
        .lb-histrow .lb-hist-meta { padding-right: 22px; }
    }
</style>
