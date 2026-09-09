{{--
    Employer registration.

    Same chrome and same card as `auth/login.blade.php` and the seeker sign-up.
    Sir's point was that sign-in looked like one product and registration like
    another, and the half-screen blue panel was the difference. It is gone
    rather than shrunk; on a page of three fields there is nothing for it to
    balance.

    `:bare` also removes the header, footer and the Lucky AI drawer — a copilot
    bubble hovering over a sign-up form is a second thing to click on a page
    built for one.

    Ten fields on one screen became three steps of three or four, which is the
    shape tickbig.com uses. Country stays here, unlike on the seeker form: a
    company record is filed under a jurisdiction and a vacancy has to be posted
    somewhere.
--}}
<x-layouts.app :bare="true" title="Register your company — Luckyboss">
@include('auth.partials.wizard')

@push('head')
    {{--
        Twelve lines of CSS instead of two Tailwind variants, and the reason is
        worth writing down.

        The obvious markup here is `peer` + `peer-checked:border-secondary-500`.
        It renders nothing: this project has no Node step, the CSS bundle was
        compiled on 2026-08-27, and **no view was using a `peer-checked:` variant
        on that date, so the variant does not exist**. Probed in the browser
        rather than guessed — a checked radio left its card at the default border
        and a white background, which is to say the chosen plan looked exactly
        like the two the employer did not choose. Nothing errors; it is simply
        invisible.

        `:checked + div` needs no build step and no JavaScript, so the selection
        also survives on a phone with scripting off — which the rest of this
        wizard is careful about.
    --}}
    <style>
        .lb-plan input:checked + .lb-plan-card {
            border-color: #18A66A !important;
            background: #F3FBF7 !important;
            box-shadow: 0 0 0 3px rgba(24, 166, 106, .14);
        }
        .lb-plan input:focus-visible + .lb-plan-card {
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .35);
        }
        .lb-plan:hover .lb-plan-card { border-color: #C9D6E6; }
    </style>

    {{--
        The plan prices follow the country picked on step 2.

        All three stored prices are rendered into every card and all but one are
        `hidden`; this only changes which. That is on purpose — §64 forbids live
        conversion, so there is no arithmetic anywhere near this, just a choice
        between rows an admin typed.

        With scripting off nothing runs and the SGD price stays showing, which is
        exactly what the server would have chosen: `AuthController::currencyFor()`
        defaults to SGD too, so the page and the subscription it creates never
        disagree.
    --}}
    <script>
        (function () {
            var CURRENCY = { IN: 'INR', IND: 'INR', MY: 'MYR', MYS: 'MYR' };

            function sync() {
                var country = document.getElementById('country_code');
                if (! country) { return; }

                var wanted = CURRENCY[(country.value || '').toUpperCase()] || 'SGD';

                document.querySelectorAll('[data-plan-price]').forEach(function (el) {
                    el.hidden = el.getAttribute('data-plan-price') !== wanted;
                });
            }

            function start() {
                var country = document.getElementById('country_code');
                if (! country) { return; }
                country.addEventListener('change', sync);
                sync();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', start);
            } else {
                start();
            }
        })();
    </script>
@endpush

@php
    /*
        Which fields live on which step — used to render the panels, and to
        decide where to reopen when the server rejects the submission. Without
        the second use an error on `password` is reported on a panel the user
        cannot see, and submitting looks like it did nothing.

        The split follows the three questions this form actually asks: who is
        signing up, what company they are signing up, and how they get back in.
    */
    $stepFields = [
        1 => ['name', 'email', 'phone', 'phone_national', 'dial_code'],
        2 => ['company_name', 'company_type_id', 'country_code', 'registration_number'],
        3 => ['password', 'password_confirmation'],
        4 => ['package_id', 'terms'],
    ];

    $stepTitles = [
        1 => 'First, who are you?',
        2 => 'About your company',
        3 => 'Secure your account',
        4 => 'Choose your plan',
    ];

    /*
        Which of the three stored prices to show. Spec §64 is explicit that
        prices are set per market by hand and never converted live, so this picks
        a row — it does not do arithmetic on one.

        It follows the country chosen on step 2 where there is one, and otherwise
        falls back to SGD, the currency a company is registered against.
    */
    $registerCurrency = match (strtoupper((string) old('country_code'))) {
        'IN', 'IND' => 'INR',
        'MY', 'MYS' => 'MYR',
        default     => 'SGD',
    };

    $currencySymbols = ['SGD' => 'S$', 'INR' => 'INR ', 'MYR' => 'RM'];

    /*
        The middle tier carries sir's own anchor price (§64) and is the one the
        card recommends. Picked by position rather than by name, so renaming a
        package in admin cannot silently un-recommend it.
    */
    $recommendedPackageId = $packages->count() >= 3 ? $packages[1]->id : $packages->first()?->id;

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

                    <form method="POST" action="{{ route('register.employer.store') }}">
                        @csrf

                        {{-- Step 1 — the person --}}
                        <div data-wizard-panel="1" class="space-y-5">
                            <x-ui.input label="Full Name" name="name" required
                                        placeholder="Your name" :value="old('name')" />

                            <x-ui.input label="Work Email" name="email" type="email" required
                                        placeholder="work@company.com" :value="old('email')" />

                            <x-ui.phone-field label="Phone Number" required />

                            <x-ui.button type="button" variant="secondary" class="w-full mt-2" size="lg"
                                         data-wizard-next>
                                Continue
                            </x-ui.button>
                        </div>

                        {{-- Step 2 — the company --}}
                        <div data-wizard-panel="2" class="space-y-5" hidden>
                            <x-ui.input label="Company Name" name="company_name" required
                                        placeholder="Acme Corp" :value="old('company_name')" />

                            {{-- placeholder="" because the empty option is written into the slot
                                 below; without this the component adds a second one and the
                                 dropdown opens on two indistinguishable blank choices. --}}
                            <x-ui.select label="Company Type" name="company_type_id" placeholder="">
                                <option value="">Select type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('company_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select label="Country" name="country_code" required placeholder="">
                                <option value="">Select country</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->code }}" {{ old('country_code') == $country->code ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input label="Registration Number (Optional)" name="registration_number"
                                        placeholder="Business Reg No." :value="old('registration_number')" />

                            <x-ui.button type="button" variant="secondary" class="w-full mt-2" size="lg"
                                         data-wizard-next>
                                Continue
                            </x-ui.button>

                            <button type="button" data-wizard-back
                                    class="w-full text-sm font-semibold transition-colors py-1"
                                    style="color:var(--color-text-muted);">
                                &larr; Back
                            </button>
                        </div>

                        {{-- Step 3 — credentials, with the consent beside the
                             button that actually creates the account. --}}
                        <div data-wizard-panel="3" class="space-y-5" hidden>
                            <x-ui.input label="Password" name="password" type="password" required
                                        minlength="8" placeholder="Min. 8 characters" />

                            <x-ui.input label="Confirm Password" name="password_confirmation" type="password"
                                        required minlength="8" placeholder="Confirm password" />

                            <x-ui.button type="button" variant="secondary" class="w-full mt-2" size="lg"
                                         data-wizard-next>
                                Continue
                            </x-ui.button>

                            <button type="button" data-wizard-back
                                    class="w-full text-sm font-semibold transition-colors py-1"
                                    style="color:var(--color-text-muted);">
                                &larr; Back
                            </button>
                        </div>

                        {{--
                            Step 4 — the plan, with the consent beside the button
                            that actually creates the account.

                            Radios rather than a select or three links: the choice
                            has to be comparable at a glance, and it has to survive
                            a validation bounce, which a link-per-plan cannot. Each
                            card is a <label> wrapping its own radio, so the whole
                            card is the hit area and it works with no JavaScript at
                            all — the chosen card is styled by `peer-checked`, not
                            by a click handler.

                            Prices come from `package_prices` for the country
                            chosen on step 2. Nothing is charged here, there is no
                            gateway, and the line under the cards says so rather
                            than leaving anyone to assume.
                        --}}
                        <div data-wizard-panel="4" class="space-y-4" hidden>
                            @foreach($packages as $package)
                                @php
                                    $price = $package->prices->firstWhere('currency_code', $registerCurrency)
                                        ?? $package->prices->firstWhere('currency_code', 'SGD');

                                    /*
                                        Built here rather than inline in the markup.
                                        An `@if` that directly follows a word character
                                        is not compiled by Blade at all — `\B@` fails on
                                        the boundary — so `...a month@if(...)` rendered
                                        the directive to the page as literal text. Found
                                        on screen, which is the only place it shows.
                                    */
                                    $limits = $package->entitlements ?? [];
                                    $posts  = (int) data_get($limits, 'job_posts');
                                    $views  = (int) data_get($limits, 'candidate_views');

                                    $summary = [
                                        $posts === -1 ? 'Unlimited vacancies' : $posts.' '.Str::plural('vacancy', $posts),
                                        $views === -1 ? 'unlimited candidate unlocks' : $views.' candidate '.Str::plural('unlock', $views),
                                    ];

                                    if (data_get($limits, 'ai_matching')) {
                                        $aiUsage = (int) data_get($limits, 'ai_usage');
                                        $summary[] = $aiUsage === -1 ? 'unlimited AI match reports' : $aiUsage.' AI match reports';
                                    }
                                @endphp

                                <label class="block cursor-pointer lb-plan">
                                    <input type="radio" name="package_id" value="{{ $package->id }}"
                                           class="sr-only"
                                           @checked((int) old('package_id', $recommendedPackageId) === $package->id)>

                                    <div class="rounded-2xl border p-4 transition-all lb-plan-card"
                                         style="border-color:var(--color-border);background:#fff;">
                                        <div class="flex items-baseline justify-between gap-3">
                                            <span class="font-heading font-bold text-navy text-base">{{ $package->name }}</span>
                                            @if($package->id === $recommendedPackageId)
                                                <span class="shrink-0 px-2 py-0.5 rounded-full text-xs font-bold"
                                                      style="background:#E7F7EF;color:#127A4E;">Recommended</span>
                                            @endif
                                        </div>

                                        <p class="mt-1 font-heading font-bold text-navy text-xl">
                                            @forelse($package->prices as $planPrice)
                                                <span data-plan-price="{{ $planPrice->currency_code }}"
                                                      @if($planPrice->currency_code !== $registerCurrency) hidden @endif>{{ $currencySymbols[$planPrice->currency_code] ?? $planPrice->currency_code.' ' }}{{ number_format((float) $planPrice->amount) }}</span>
                                            @empty
                                                <span class="text-sm font-semibold" style="color:var(--color-text-muted);">Price on request</span>
                                            @endforelse
                                            <span class="text-sm font-semibold" style="color:var(--color-text-muted);"> / month</span>
                                        </p>

                                        <p class="text-sm mt-2" style="color:var(--color-text-secondary);">
                                            {{ implode(', ', $summary) }} a month.
                                        </p>
                                    </div>
                                </label>
                            @endforeach

                            <p class="text-sm text-center" style="color:var(--color-text-muted);">
                                Nothing is charged today. Your plan starts straight away, and we will
                                talk to you about payment before anything is billed.
                            </p>

                            <label class="flex items-start gap-3 cursor-pointer group pt-1">
                                <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-border text-secondary-500 focus:ring-secondary-500 transition-colors">
                                <span class="text-sm" style="color:var(--color-text-secondary);">
                                    I agree to the <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Terms of Service</a>
                                    and <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Privacy Policy</a>.
                                </span>
                            </label>

                            <x-ui.button type="submit" variant="secondary" class="w-full mt-2" size="lg">
                                Create account
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
                    Looking for a job?
                    <a href="{{ route('register.seeker') }}" class="font-semibold hover:underline" style="color:#031F49;">Create a job seeker account</a>
                </p>
            </div>
        </main>
    </div>
</x-layouts.app>
