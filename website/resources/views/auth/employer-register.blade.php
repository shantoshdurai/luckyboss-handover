<x-layouts.app title="Create Employer Account — Luckyboss Portal">
    <div class="min-h-[calc(100vh-72px)] flex">
        {{-- Left: Brand Side --}}
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-navy via-primary-800 to-green-900 relative overflow-hidden">
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20 text-white">
                <span class="eyebrow text-secondary-300 mb-4 tracking-wider uppercase text-sm font-bold">For Employers</span>
                <h1 class="text-4xl xl:text-5xl font-serif !text-white font-normal leading-tight mb-6">
                    <span class="italic text-white">Hire the best</span><br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">talent, faster.</span>
                </h1>
                <p class="text-lg text-slate-300 max-w-md mb-10 leading-relaxed">
                    Join thousands of companies using our platform to find, interview, and hire top candidates. Build your team with confidence.
                </p>

                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg !text-white text-white">Massive Talent Pool</h3>
                            <p class="text-slate-200 text-sm">Access over 50,000 active job seekers.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-secondary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg !text-white text-white">Powerful Tools</h3>
                            <p class="text-slate-200 text-sm">Applicant tracking and interview management.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Decorative elements --}}
            <div class="absolute top-0 right-0 w-96 h-96 bg-secondary-500/20 rounded-full blur-3xl -translate-y-1/2 translate-x-1/3"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-green-500/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3"></div>
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
                    <h2 class="text-3xl font-heading font-bold text-navy mb-2">Register your company</h2>
                    <p class="text-text-muted text-lg">
                        Already have an account?
                        <a href="{{ route('login') }}" class="text-accent font-semibold hover:underline">Sign in instead</a>
                    </p>
                </div>

                <form method="POST" action="{{ route('register.employer.store') }}" class="space-y-5">
                    @csrf

                    <div class="border-b border-border pb-4 mb-4">
                        <h3 class="text-sm font-bold tracking-wider text-text-muted uppercase mb-4">Contact Person</h3>
                        
                        <x-ui.input
                            label="Full Name"
                            name="name"
                            required
                            placeholder="John Doe"
                            :value="old('name')"
                        />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
                            <x-ui.input
                                label="Email Address"
                                name="email"
                                type="email"
                                required
                                placeholder="work@company.com"
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
                    </div>

                    <div class="border-b border-border pb-4 mb-4">
                        <h3 class="text-sm font-bold tracking-wider text-text-muted uppercase mb-4">Company Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                            <x-ui.input
                                label="Company Name"
                                name="company_name"
                                required
                                placeholder="Acme Corp"
                                :value="old('company_name')"
                            />

                            <x-ui.select 
                                label="Company Type" 
                                name="company_type_id" 
                            >
                                <option value="">Select type...</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}" {{ old('company_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <x-ui.select 
                                label="Country" 
                                name="country_code" 
                                required
                            >
                                <option value="">Select country...</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->code }}" {{ old('country_code') == $country->code ? 'selected' : '' }}>
                                        {{ $country->name }}
                                    </option>
                                @endforeach
                            </x-ui.select>

                            <x-ui.input
                                label="Registration Number (Optional)"
                                name="registration_number"
                                placeholder="Business Reg No."
                                :value="old('registration_number')"
                            />
                        </div>
                    </div>

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
                            <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-border text-secondary-500 focus:ring-secondary-500 transition-colors">
                            <span class="text-sm text-text-secondary group-hover:text-text-primary transition-colors">
                                I agree to the <a href="#" class="text-secondary-600 hover:underline">Terms of Service</a> and <a href="#" class="text-secondary-600 hover:underline">Privacy Policy</a>.
                            </span>
                        </label>
                    </div>

                    <x-ui.button type="submit" variant="secondary" class="w-full mt-4" size="lg">
                        Register Company
                    </x-ui.button>
                </form>

                <div class="mt-8 text-center pt-8 border-t border-border">
                    <p class="text-sm text-text-muted">
                        Looking for a job?
                        <a href="{{ route('register.seeker') }}" class="text-accent font-semibold hover:underline">Register as a candidate</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
