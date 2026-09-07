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
        1 => ['name', 'email', 'phone'],
        2 => ['company_name', 'company_type_id', 'country_code', 'registration_number'],
        3 => ['password', 'password_confirmation', 'terms'],
    ];

    $stepTitles = [
        1 => 'First, who are you?',
        2 => 'About your company',
        3 => 'Secure your account',
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
                <a href="{{ route('home') }}" class="flex items-center flex-shrink-0" aria-label="Luckyboss home">
                    <img src="{{ $branding['logo_url'] }}"
                         alt="Luckyboss Employment Agency Pte. Ltd"
                         class="h-9 sm:h-11 w-auto object-contain">
                </a>

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
                    <p class="text-xs font-bold uppercase tracking-[0.14em] mb-3" style="color:var(--color-text-muted);">
                        Step <span data-wizard-current>{{ $startStep }}</span> of {{ count($stepFields) }}
                    </p>
                    <h1 class="text-[26px] sm:text-[30px] font-heading font-bold leading-tight" style="color:#031F49;">
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

                            <x-ui.input label="Phone Number" name="phone" type="tel" required
                                        placeholder="+65 8000 0000" :value="old('phone')" />

                            <x-ui.button type="button" variant="secondary" class="w-full mt-2" size="lg"
                                         data-wizard-next>
                                Continue
                            </x-ui.button>
                        </div>

                        {{-- Step 2 — the company --}}
                        <div data-wizard-panel="2" class="space-y-5" hidden>
                            <x-ui.input label="Company Name" name="company_name" required
                                        placeholder="Acme Corp" :value="old('company_name')" />

                            <x-ui.select label="Company Type" name="company_type_id">
                                <option value="">Select type</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('company_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.select label="Country" name="country_code" required>
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

                            <label class="flex items-start gap-3 cursor-pointer group pt-1">
                                <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-border text-secondary-500 focus:ring-secondary-500 transition-colors">
                                <span class="text-sm" style="color:var(--color-text-secondary);">
                                    I agree to the <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Terms of Service</a>
                                    and <a href="#" class="font-semibold hover:underline" style="color:#18A66A;">Privacy Policy</a>.
                                </span>
                            </label>

                            <x-ui.button type="submit" variant="secondary" class="w-full mt-2" size="lg">
                                Register Company
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
