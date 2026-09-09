{{--
    Sign in.

    Rebuilt after sir pointed at tickbig.com and asked for something that
    minimal and that direct. What is taken from it is the restraint, not the
    look: one card, two fields, one button, and nothing on the page competing
    with the thing the visitor came to do.

    What is deliberately NOT taken:

    - The dark ground. Luckyboss is navy and emerald on white, and the live site
      is light. A near-black page would fight both, and it would be a copy
      rather than an influence.
    - The empty stage. TickBig can afford a homepage with almost nothing on it;
      a jobs site cannot, because the vacancies are the product.

    What the old page had and this drops: a panel advertising "5,000+ Active
    Jobs, 2,500+ Companies, 50,000+ Job Seekers", hardcoded, against a database
    holding 14, 10 and 13. A minimal sign-in has nowhere to put a number like
    that, which is most of the argument for it.

    What it keeps: the account-type choice. It is the one thing here that earns
    its space, because a job seeker and an employer land in completely different
    portals and picking wrong is a dead end.
--}}
<x-layouts.app :bare="true" title="{{ $adminLogin ?? false ? 'Administrator Sign In' : 'Sign in — Luckyboss' }}">

    <div class="min-h-screen flex flex-col" style="background:#F7F9FC;">

        {{-- A single line of chrome. Logo out, sign-up in. --}}
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
                        <img src="{{ asset($branding['logo_url'] ?? 'images/lucky-boss-logo-transparent.png') }}"
                             alt="Luckyboss Employment Agency Pte. Ltd"
                             class="h-11 sm:h-14 w-auto object-contain">
                    </a>
                </div>

                @unless($adminLogin ?? false)
                    {{-- On a narrow screen the full sentence collides with the
                         logo, so only the link survives. The same invitation is
                         repeated under the card, where there is room for it. --}}
                    <p class="text-sm text-right flex-shrink-0" style="color:#64748B;">
                        <span class="hidden sm:inline">New to Luckyboss?</span>
                        <a href="{{ route('register.seeker') }}"
                           class="font-semibold hover:underline whitespace-nowrap" style="color:#18A66A;">Create an account</a>
                    </p>
                @endunless
            </div>
        </header>

        @php $selectedRole = old('login_as', 'job-seeker'); @endphp

        {{--
            `role` is held out here, not on the toggle itself, because the
            sign-up line under the card has to follow it: pick "Job seeker" and
            the offer below should be a seeker account, pick "Employer" and it
            should be registering a company. With the state scoped to the toggle
            the card could react and the line beneath it could not, which is why
            only the employer offer was ever shown.
        --}}
        <main class="flex-1 flex items-start justify-center px-6 pb-20 pt-4 sm:pt-10">
            <div class="w-full" style="max-width:26rem;"
                 x-data="{ role: '{{ $selectedRole }}' }">

                {{--
                    Two levels, the way tickbig.com splits them: the page says
                    what it is, the card says hello.

                    This previously used "Welcome back" as the 32px page title,
                    which read as a greeting where the label should be — arriving
                    from "Find a job" you were told welcome back before being
                    told you had landed on sign-in. The heading now names the
                    page and the greeting shrinks into the card.
                --}}
                <div class="text-center mb-7">
                    <h1 class="text-3xl sm:text-3xl font-heading font-bold leading-tight" style="color:#031F49;">
                        {{ $adminLogin ?? false ? 'Administrator sign in' : 'Sign in' }}
                    </h1>
                    <span class="inline-block mt-3 rounded-full" style="width:38px;height:3px;background:#18A66A;"></span>
                </div>

                <div class="rounded-2xl px-6 sm:px-8 py-8"
                     style="background:#FFFFFF;border:1px solid #E4EAF2;box-shadow:0 1px 2px rgba(3,31,73,.05),0 12px 32px -20px rgba(3,31,73,.28);">

                    @unless($adminLogin ?? false)
                        <p class="text-center font-heading font-bold mb-6" style="color:#18A66A;font-size:17px;">
                            Welcome back
                        </p>
                    @endunless

                    @if ($errors->any())
                        <div class="mb-5 rounded-xl px-4 py-3 text-sm"
                             style="background:#FDECEA;border:1px solid #F5C6C0;color:#A3341F;" role="alert">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ $adminLogin ?? false ? route('admin.login.store') : route('login.store') }}"
                          class="space-y-5">
                        @csrf

                        @unless($adminLogin ?? false)
                            {{-- Inline styles: the brand tokens are not in the
                                 prebuilt Tailwind bundle, and this project runs
                                 with no Node build step.

                                 `role` lives on the page wrapper above, so the
                                 sign-up line under the card can read it too. --}}
                            <div>
                                <input type="hidden" name="login_as" :value="role">

                                <span class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:#8494A8;">
                                    I am a
                                </span>

                                <div class="grid grid-cols-2 gap-2.5" role="group" aria-label="Account type">
                                    <button type="button" @click="role = 'job-seeker'"
                                            :aria-pressed="role === 'job-seeker'"
                                            :style="role === 'job-seeker'
                                                ? 'border-color:#031F49;background:#031F49;color:#fff'
                                                : 'border-color:#E4EAF2;background:#fff;color:#64748B'"
                                            class="rounded-xl border px-3 py-3 text-sm font-semibold transition-all">
                                        Job seeker
                                    </button>

                                    <button type="button" @click="role = 'employer'"
                                            :aria-pressed="role === 'employer'"
                                            :style="role === 'employer'
                                                ? 'border-color:#031F49;background:#031F49;color:#fff'
                                                : 'border-color:#E4EAF2;background:#fff;color:#64748B'"
                                            class="rounded-xl border px-3 py-3 text-sm font-semibold transition-all">
                                        Employer
                                    </button>
                                </div>
                            </div>
                        @endunless

                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider mb-2" style="color:#8494A8;">
                                Email
                            </label>
                            <input id="email" name="email" type="email" required autofocus
                                   value="{{ old('email') }}" autocomplete="email"
                                   class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all"
                                   style="border:1px solid #E4EAF2;color:#031F49;background:#fff;"
                                   onfocus="this.style.borderColor='#18A66A';this.style.boxShadow='0 0 0 3px rgba(24,166,106,.14)'"
                                   onblur="this.style.borderColor='#E4EAF2';this.style.boxShadow='none'"
                                   placeholder="you@example.com">
                        </div>

                        <div>
                            <div class="flex items-baseline justify-between mb-2">
                                <label for="password" class="block text-xs font-bold uppercase tracking-wider" style="color:#8494A8;">
                                    Password
                                </label>
                                <a href="{{ Route::has('password.request') ? route('password.request') : '#' }}"
                                   class="text-xs font-semibold hover:underline" style="color:#2563EB;">Forgot?</a>
                            </div>

                            <div x-data="{ show: false }" class="relative">
                                <input id="password" name="password" required autocomplete="current-password"
                                       :type="show ? 'text' : 'password'"
                                       class="w-full rounded-xl px-4 py-3 pr-11 text-sm outline-none transition-all"
                                       style="border:1px solid #E4EAF2;color:#031F49;background:#fff;"
                                       onfocus="this.style.borderColor='#18A66A';this.style.boxShadow='0 0 0 3px rgba(24,166,106,.14)'"
                                       onblur="this.style.borderColor='#E4EAF2';this.style.boxShadow='none'"
                                       placeholder="Your password">

                                <button type="button" @click="show = !show"
                                        class="absolute inset-y-0 right-0 px-3 flex items-center"
                                        style="color:#8494A8;"
                                        :aria-label="show ? 'Hide password' : 'Show password'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
                                        <path x-show="!show" stroke-linecap="round" stroke-linejoin="round"
                                              d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                                        <path x-show="!show" stroke-linecap="round" stroke-linejoin="round"
                                              d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        <path x-show="show" x-cloak stroke-linecap="round" stroke-linejoin="round"
                                              d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.774 3.162 10.066 7.5a10.52 10.52 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" value="1"
                                   class="w-4 h-4 rounded cursor-pointer" style="accent-color:#18A66A;">
                            <span class="text-sm" style="color:#64748B;">Keep me signed in</span>
                        </label>

                        <button type="submit"
                                class="w-full rounded-xl py-3.5 font-bold text-sm transition-all"
                                style="background:#18A66A;color:#fff;box-shadow:0 8px 20px -12px rgba(24,166,106,.9);"
                                onmouseover="this.style.background='#149257'"
                                onmouseout="this.style.background='#18A66A'">
                            Sign in
                        </button>
                    </form>
                </div>

                @unless($adminLogin ?? false)
                    {{--
                        Both accounts are offered, and the one shown follows the
                        toggle above. Previously only the employer line was here,
                        so a job seeker who could not sign in was invited to
                        register a company — the wrong door, and the only one on
                        offer.

                        Deliberately no x-cloak: with scripting off, Alpine never
                        removes it and both offers would vanish. Without it a
                        no-JS visitor simply sees both lines, which is correct —
                        both are real routes — and Alpine narrows to one the
                        moment it boots.
                    --}}
                    <p class="text-center text-sm mt-6" style="color:#64748B;" x-show="role === 'job-seeker'">
                        Looking for a job?
                        <a href="{{ route('register.seeker') }}" class="font-semibold hover:underline" style="color:#031F49;">
                            Create a job seeker account
                        </a>
                    </p>

                    <p class="text-center text-sm mt-6" style="color:#64748B;" x-show="role === 'employer'">
                        Hiring?
                        <a href="{{ route('register.employer') }}" class="font-semibold hover:underline" style="color:#031F49;">
                            Register your company
                        </a>
                    </p>
                @endunless

                <p class="text-center text-xs mt-8" style="color:#9AA8BA;">
                    &copy; {{ date('Y') }} Luckyboss Employment Agency Pte. Ltd
                </p>
            </div>
        </main>
    </div>
</x-layouts.app>
