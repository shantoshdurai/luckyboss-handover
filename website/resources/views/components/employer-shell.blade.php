@props(['title' => 'Employer Portal', 'footer' => true])
{{--
    The employer portal's shell, on the site's own header.

    `x-employer-sidebar` was a second layout entirely: its own <html>, its own
    <head>, its own logo bar, and a twelve-item rail pinned down the left of
    every screen — Dashboard, My Jobs, Post New Job, Candidates, Interviews,
    Offers, Subscription, Billing, Team, Company Profile, AI Tools, Analytics.
    It has been deleted, and this is the whole employer portal now.

    Keeping it "for the deep ATS pages" is what made the portal feel broken.
    Five of the six employer screens lived in that other document, so the pill
    nav in the header linked to four destinations that did not have the header:
    clicking Jobs or Candidates dropped the top bar, dropped the logo, and — the
    part nobody could name — dropped the soft navigation with it, because
    `layouts/app.blade.php` cannot swap <main> between two different documents.
    Every one of those clicks was a full browser load, which is the flash and the
    "chunky" reflow sir kept reporting, surviving in exactly the places the fix
    had never reached.

    Twelve doors is also a filing cabinet rather than a product. The five that
    matter are pills in the header; the rest are in the account menu, which is
    where the candidate portal already puts them.

    This is the same wrapper the candidate portal got, for the same reason —
    signing in should change what the page offers, not what the product looks
    like. The header, the logo, the pill nav and the account menu all stay put.
--}}
<x-layouts.app :title="$title.' | Luckyboss Employers'" :footer="$footer">
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
