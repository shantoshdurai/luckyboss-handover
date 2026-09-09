@props(['title' => 'Job Seeker Portal', 'footer' => true])
{{--
    The candidate portal's shell.

    This used to be a whole second layout: its own <html>, its own top bar with a
    page title in it, its own notification bell, and a fixed left rail. Signing in
    therefore dropped you out of the site's own header — the one with the logo,
    the pill nav and the motion — and into something flatter, which is exactly
    what sir objected to.

    It now wraps `x-layouts.app`, so a signed-in candidate keeps the same header
    as every other page. That header already swaps Sign In / Register for the
    icon rail and the avatar when someone is signed in, and the whole account
    menu hangs off the avatar on hover. Nothing is pinned down the side any more,
    and there is no page-title strip.

    Only `info` is rendered here: the layout shows success and error as toasts,
    and rendering them a second time is how the duplicate "Saved." happened.
--}}
<x-layouts.app :title="$title.' | Luckyboss Candidate Portal'" :footer="$footer">
    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 py-8 space-y-6">

        @if(session('info'))
            <div class="p-4 rounded-2xl bg-accent/5 border border-accent/30 text-navy text-xs font-semibold flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        {{ $slot }}
    </div>
</x-layouts.app>
