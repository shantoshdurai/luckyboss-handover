<x-layouts.app title="Contact Us — Luckyboss Portal">
    {{-- Header Banner --}}
    <section class="bg-gradient-to-br from-navy via-primary-800 to-accent text-white py-14 lg:py-20 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-80 h-80 bg-secondary-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-accent/20 rounded-full blur-3xl"></div>

        <div class="container mx-auto px-6 relative z-10 text-center max-w-3xl">
            <span class="inline-block py-1 px-3 rounded-full bg-white/10 border border-white/20 text-xs font-semibold tracking-wider uppercase mb-4">
                Get In Touch
            </span>
            <h1 class="text-3xl md:text-5xl font-serif !text-white font-normal mb-4 tracking-tight leading-tight">
                <span class="italic text-white">We're here to</span> 
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-teal-200 to-sky-200 font-sans font-extrabold not-italic">help you succeed.</span>
            </h1>
            <p class="text-blue-100/90 text-base md:text-lg leading-relaxed">
                Have questions about hiring talent or finding your next role? Reach out to our dedicated support team.
            </p>
        </div>
    </section>

    {{-- Contact Cards Section --}}
    <section class="py-16 bg-surface">
        <div class="container mx-auto px-6 max-w-5xl">
            <div class="grid md:grid-cols-2 gap-8">
                {{-- Office Info --}}
                <div class="bg-white rounded-3xl p-8 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-accent flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        <h3 class="text-2xl font-heading font-bold text-navy mb-6">Corporate Office</h3>
                        
                        <div class="space-y-4 text-text-secondary text-sm">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-text-muted mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <div>
                                    <strong class="text-text-primary block mb-0.5">Address</strong>
                                    {{ $contact['office_address'] ?? 'Singapore / Malaysia / India Regional Hubs' }}
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-text-muted mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <div>
                                    <strong class="text-text-primary block mb-0.5">Official Email</strong>
                                    <a href="mailto:{{ $contact['official_email'] ?? 'support@luckyboss.org' }}" class="text-accent hover:underline">
                                        {{ $contact['official_email'] ?? 'support@luckyboss.org' }}
                                    </a>
                                </div>
                            </div>

                            @if(!empty($contact['official_phone']))
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-text-muted mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                <div>
                                    <strong class="text-text-primary block mb-0.5">Phone Contact</strong>
                                    <span>{{ $contact['official_phone'] }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Dashboard Support Card --}}
                <div class="bg-white rounded-3xl p-8 border border-border shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="w-12 h-12 rounded-xl bg-secondary-50 text-secondary-600 flex items-center justify-center mb-6">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-heading font-bold text-navy mb-4">Dedicated Help Center</h3>
                        <p class="text-text-secondary text-sm leading-relaxed mb-6">
                            Registered job seekers and corporate employers have direct access to our priority ticketing system with guaranteed 24-hour response times.
                        </p>

                        <div class="p-4 rounded-xl bg-surface-sunken border border-border text-xs text-text-muted mb-6">
                            💡 Sign in to your account to create support tickets, view billing history, or request recruitment assistance.
                        </div>
                    </div>

                    <a href="{{ route('login') }}" class="btn btn-primary w-full justify-center" size="lg">
                        Sign In for Priority Support
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>