<x-layouts.app title="Employer Solutions — Luckyboss Portal">
    {{-- Hero Section --}}
    <section class="bg-gradient-to-br from-navy via-primary-800 to-green-900 text-white py-16 lg:py-24 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-secondary-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent/20 rounded-full blur-3xl"></div>

        <div class="container mx-auto px-6 relative z-10 text-center max-w-3xl">
            <span class="inline-block py-1 px-3.5 rounded-full bg-white/10 border border-white/20 text-xs font-bold tracking-wider uppercase mb-4 text-secondary-300">
                Enterprise Recruitment Platform
            </span>
            <h1 class="text-4xl md:text-6xl font-serif !text-white font-normal mb-6 tracking-tight leading-tight">
                <span class="italic text-white">Hire verified talent,</span> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">faster & smarter.</span>
            </h1>
            <p class="text-blue-100/90 text-lg md:text-xl leading-relaxed mb-8">
                Post jobs, screen candidates with AI-powered matching, manage interviews, and issue offers across Singapore, Malaysia, and India in one unified workspace.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('register.employer') }}" class="btn btn-secondary btn-xl shadow-xl hover:shadow-2xl">
                    Register Your Company
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline btn-xl border-white text-white hover:bg-white hover:text-navy">
                    Employer Sign In
                </a>
            </div>
        </div>
    </section>

    {{-- Value Props Grid --}}
    <section class="py-16 bg-white border-b border-border">
        <div class="container mx-auto px-6">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="p-8 rounded-2xl bg-surface-sunken border border-border">
                    <div class="w-12 h-12 rounded-xl bg-secondary-50 text-secondary-600 flex items-center justify-center mb-6 font-bold text-xl">
                        01
                    </div>
                    <h3 class="text-xl font-heading font-bold text-navy mb-3">AI Candidate Scoring</h3>
                    <p class="text-text-secondary leading-relaxed">Automatically analyze applicants against job requirements with instant match percentages and skill gap highlights.</p>
                </div>
                <div class="p-8 rounded-2xl bg-surface-sunken border border-border">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-accent flex items-center justify-center mb-6 font-bold text-xl">
                        02
                    </div>
                    <h3 class="text-xl font-heading font-bold text-navy mb-3">Seamless Interviews</h3>
                    <p class="text-text-secondary leading-relaxed">Schedule video and in-person interviews directly from your portal with automated email and calendar notifications.</p>
                </div>
                <div class="p-8 rounded-2xl bg-surface-sunken border border-border">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 font-bold text-xl">
                        03
                    </div>
                    <h3 class="text-xl font-heading font-bold text-navy mb-3">Compliance & Offers</h3>
                    <p class="text-text-secondary leading-relaxed">Issue structured job offers, track document submissions, and ensure cross-border recruitment compliance.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Subscription Packages Section --}}
    @if(isset($packages) && $packages->count() > 0)
    <section class="py-20 bg-surface">
        <div class="container mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="text-xs font-bold uppercase tracking-wider text-secondary-600 mb-2 block">Flexible Pricing</span>
                <h2 class="text-3xl md:text-4xl font-heading font-bold text-navy mb-4">Recruitment Packages</h2>
                <p class="text-text-secondary text-lg">Choose a plan that fits your hiring velocity and organization size.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @foreach($packages as $package)
                    <div class="bg-white rounded-3xl p-8 border {{ $loop->iteration == 2 ? 'border-accent shadow-xl ring-2 ring-accent/20 relative' : 'border-border shadow-sm' }} flex flex-col justify-between">
                        @if($loop->iteration == 2)
                            <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-accent text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                                Most Popular
                            </div>
                        @endif

                        <div>
                            <h3 class="text-2xl font-heading font-bold text-navy mb-2">{{ $package->name }}</h3>
                            <p class="text-sm text-text-secondary mb-6">{{ $package->description }}</p>

                            <div class="py-4 mb-6 border-y border-border">
                                <span class="text-xs text-text-muted uppercase font-bold tracking-wider block">Validity</span>
                                <span class="text-2xl font-bold text-text-primary">{{ $package->validity_days }} Days</span>
                            </div>

                            @if(is_array($package->entitlements) || is_object($package->entitlements))
                                <ul class="space-y-3 mb-8 text-sm text-text-secondary">
                                    @foreach($package->entitlements as $key => $val)
                                        <li class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-secondary-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span><strong>{{ is_numeric($val) && $val < 0 ? 'Unlimited' : (is_bool($val) ? ($val ? 'Included' : 'Not included') : $val) }}</strong> {{ Str::headline($key) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <a href="{{ route('register.employer') }}" class="btn {{ $loop->iteration == 2 ? 'btn-primary' : 'btn-outline' }} w-full justify-center">
                            Get Started
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
</x-layouts.app>