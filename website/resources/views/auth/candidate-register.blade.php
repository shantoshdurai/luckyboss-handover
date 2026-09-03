<x-layouts.app title="Create Job Seeker Account — Luckyboss Portal">
    <div class="min-h-[calc(100vh-72px)] flex">
        {{-- Left: Brand Side --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-navy via-primary-800 to-accent relative overflow-hidden">
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <span class="eyebrow text-blue-200 mb-4 tracking-wider uppercase text-sm font-bold">For Job Seekers</span>
                <h1 class="text-4xl xl:text-5xl font-serif !text-white font-normal leading-tight mb-6">
                    <span class="italic text-white">Find your dream</span><br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">career today.</span>
                </h1>
                <p class="text-lg text-blue-100 max-w-md mb-10 leading-relaxed">
                    Join thousands of professionals finding opportunities that match their skills. Build your profile, apply with one click, and get hired faster.
                </p>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg !text-white text-white">Top Employers</h3>
                            <p class="text-blue-100 text-sm">Access jobs from verified, high-quality companies.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg !text-white text-white">Fast Applications</h3>
                            <p class="text-blue-100 text-sm">Apply to jobs in seconds with your saved profile.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Decorative elements --}}
            <div class="absolute top-0 right-0 w-96 h-96 bg-secondary-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-accent/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>
        </div>

        {{-- Right: Form Side --}}
        <div class="flex-1 flex items-center justify-center px-6 py-12 bg-white">
            <div class="w-full max-w-xl">
                {{-- Mobile Logo --}}
                <div class="lg:hidden text-center mb-8">
                    <a href="{{ route('home') }}" class="text-2xl font-heading font-bold">
                        <span class="text-secondary-500">Lucky</span><span class="text-navy">Boss</span>
                    </a>
                </div>

                <div class="mb-8">
                    <h2 class="text-3xl font-heading font-bold text-navy mb-2">Create your account</h2>
                    <p class="text-text-muted text-lg">
                        Already have an account?
                        <a href="{{ route('login') }}" class="text-accent font-semibold hover:underline">Sign in instead</a>
                    </p>
                </div>

                <form method="POST" action="{{ route('register.seeker.store') }}" class="space-y-5">
                    @csrf

                    <x-ui.input
                        label="Full Name"
                        name="name"
                        required
                        placeholder="John Doe"
                        :value="old('name')"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <x-ui.input
                            label="Email Address"
                            name="email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            :value="old('email')"
                        />

                        <x-ui.input
                            label="Phone Number"
                            name="phone"
                            type="tel"
                            required
                            placeholder="+1 (555) 000-0000"
                            :value="old('phone')"
                        />
                    </div>

                    <x-ui.select 
                        label="Country" 
                        name="country_code" 
                        required
                    >
                        <option value="">Select a country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->code }}" {{ old('country_code') == $country->code ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </x-ui.select>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <x-ui.input
                            label="Password"
                            name="password"
                            type="password"
                            required
                            placeholder="Min. 8 characters"
                        />

                        <x-ui.input
                            label="Confirm Password"
                            name="password_confirmation"
                            type="password"
                            required
                            placeholder="Confirm password"
                        />
                    </div>

                    <div class="pt-2">
                        <label class="flex items-start gap-3 cursor-pointer group">
                            <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-border text-accent focus:ring-accent transition-colors">
                            <span class="text-sm text-text-secondary group-hover:text-text-primary transition-colors">
                                I agree to the <a href="#" class="text-accent hover:underline">Terms of Service</a> and <a href="#" class="text-accent hover:underline">Privacy Policy</a>.
                            </span>
                        </label>
                    </div>

                    <x-ui.button type="submit" variant="primary" class="w-full mt-4" size="lg">
                        Create Account
                    </x-ui.button>
                </form>

                <div class="mt-8 text-center pt-8 border-t border-border">
                    <p class="text-sm text-text-muted">
                        Looking to hire talent?
                        <a href="{{ route('register.employer') }}" class="text-secondary-500 font-semibold hover:underline">Register as an employer</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
