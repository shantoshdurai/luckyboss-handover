{{--
    Job seeker registration.

    Rebuilt to sit beside `auth/login.blade.php` rather than fight it. Sir's
    complaint was exact: sign-in was a centred card on a calm page and this was a
    half-screen blue panel with the form pushed to the right — two different
    products wearing the same logo. Same chrome, same card, same rule under the
    heading, same width. The brand panel is gone rather than shrunk; on a page
    with three fields there is nothing for it to balance.

    `:bare` also removes the header, the footer and the Lucky AI drawer. A
    copilot bubble hovering over a sign-up form is a second thing to click on a
    page built for one.

    Country was dropped from this form on sir's instruction. It is collected in
    the profile wizard, where it is actually used — JobMatchService scores
    location, and treats a missing one as a dimension it cannot assess rather
    than inventing a country for someone.
--}}
<x-layouts.app :bare="true" title="Create your account — Luckyboss">
@include('auth.partials.wizard')

@php
    /*
        Which fields live on which step — used to render the panels, and to
        decide where to reopen when the server rejects the submission. Without
        the second use an error on `email` is reported on a panel the user
        cannot see, and the button looks dead.
    */
    $stepFields = [
        1 => ['name', 'phone', 'phone_national', 'dial_code'],
        2 => ['email', 'password', 'password_confirmation', 'terms'],
    ];

    $stepTitles = [
        1 => 'First, who are you?',
        2 => 'Now your sign-in details',
    ];

    $startStep = 1;
    foreach ($stepFields as $number => $fields) {
        if ($errors->hasAny($fields)) {
            $startStep = $number;
            break;
        }
    }

    $branding = app(\App\Services\SiteSettingsService::class)->branding();
@endphp

    <div class="min-h-screen flex flex-col" style="background:var(--color-surface);">

        <header class="w-full">
            <div class="max-w-6xl mx-auto px-6 h-20 flex items-center justify-between gap-4">
                {{-- Back and the mark travel together on the left. Left as three
                     items in a justify-between row, the logo floated to the
                     centre of the page, which reads as decoration rather than as
                     the way home. The mark is also the larger of the two: it is
                     the thing people recognise. --}}
                <div class="flex items-center gap-4 min-w-0">
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-navy transition-colors shrink-0"
                       aria-label="Back to Luckyboss">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <span>Back</span>
                    </a>

                    <a href="{{ route('home') }}" class="flex items-center flex-shrink-0" aria-label="Luckyboss home">
                        <img src="{{ $branding['logo_url'] }}"
                             alt="Luckyboss Employment Agency Pte. Ltd"
                             class="h-11 sm:h-14 w-auto object-contain">
                    </a>
                </div>

                <p class="text-sm text-right flex-shrink-0" style="color:var(--color-text-secondary);">
                    <span class="hidden sm:inline">Already have an account?</span>
                    <a href="{{ route('login') }}"
                       class="font-semibold hover:underline whitespace-nowrap" style="color:#18A66A;">Sign in</a>
                </p>
            </div>
        </header>

        <main class="flex-1 flex items-start justify-center px-6 pb-20 pt-4 sm:pt-10">
            <div class="w-full" style="max-width:26rem;" data-wizard data-wizard-start="{{ $startStep }}">

                <div class="text-center mb-7">
                    <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color:var(--color-text-muted);">
                        Step <span data-wizard-current>{{ $startStep }}</span> of {{ count($stepFields) }}
                    </p>
                    <h1 class="text-2xl sm:text-3xl font-heading font-bold leading-tight" style="color:#031F49;">
                        @foreach($stepTitles as $number => $title)
                            <span data-wizard-title="{{ $number }}" @if($number !== $startStep) hidden @endif>{{ $title }}</span>
                        @endforeach
                    </h1>
                    <span class="inline-block mt-3 rounded-full" style="width:38px;height:3px;background:#18A66A;"></span>
                </div>

                {{-- Bars, not a percentage: what matters is how many questions
                     are left, which a number cannot show. --}}
                <div class="flex gap-2 mb-5" aria-hidden="true">
                    @for($i = 1; $i <= count($stepFields); $i++)
                        <div data-wizard-bar="{{ $i }}"
                             class="h-1.5 flex-1 rounded-full transition-colors duration-300 {{ $i <= $startStep ? 'bg-secondary-500' : 'bg-border' }}"></div>
                    @endfor
                </div>

                <div class="rounded-2xl px-6 sm:px-8 py-8"
                     style="background:#FFFFFF;border:1px solid var(--color-border);box-shadow:0 1px 2px rgba(3,31,73,.05),0 12px 32px -20px rgba(3,31,73,.28);">

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl px-4 py-3 text-sm"
                             style="background:#FDECEA;border:1px solid #F5C6C0;color:#A3341F;" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register.seeker.store') }}">
                        @csrf

                        <div data-wizard-panel="1" class="space-y-5">
                            <x-ui.input
                                label="Full Name"
                                name="name"
                                required
                                placeholder="Your name"
                                :value="old('name')"
                            />

                            <x-ui.phone-field label="Phone Number" required />

                            <x-ui.button type="button" variant="primary" class="w-full mt-2" size="lg"
                                         data-wizard-next>
                                Continue
                            </x-ui.button>
                        </div>

                        <div data-wizard-panel="2" class="space-y-5" hidden>
                            <x-ui.input
                                label="Email Address"
                                name="email"
                                type="email"
                                required
                                placeholder="you@example.com"
                                :value="old('email')"
                            />

                            <x-ui.input
                                label="Password"
                                name="password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="Min. 8 characters"
                            />

                            <x-ui.input
                                label="Confirm Password"
                                name="password_confirmation"
                                type="password"
                                required
                                minlength="8"
                                placeholder="Confirm password"
                            />

                            {{-- Consent sits next to the button that actually
                                 creates the account, not three steps earlier. --}}
                            <label class="flex items-start gap-3 cursor-pointer group pt-1">
                                <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-border text-accent focus:ring-accent transition-colors">
                                <span class="text-sm" style="color:var(--color-text-secondary);">
                                    I agree to the <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Terms of Service</a>
                                    and <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Privacy Policy</a>.
                                </span>
                            </label>

                            <x-ui.button type="submit" variant="primary" class="w-full mt-2" size="lg">
                                Create Account
                            </x-ui.button>

                            <button type="button" data-wizard-back
                                    class="w-full text-sm font-semibold transition-colors py-1"
                                    style="color:var(--color-text-muted);">
                                &larr; Back
                            </button>
                        </div>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm" style="color:var(--color-text-muted);">
                    Hiring?
                    <a href="{{ route('register.employer') }}" class="font-semibold hover:underline" style="color:#031F49;">Register your company</a>
                </p>
            </div>
        </main>
    </div>
</x-layouts.app>
