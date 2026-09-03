@php
    $normalizePayload = function ($payload) {
        if (is_array($payload)) {
            return $payload;
        }

        if (is_object($payload)) {
            return (array) $payload;
        }

        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    };

    $contact = \App\Models\AdminRecord::where('slug', 'official-contact')->first();
    $contactData = $normalizePayload($contact?->payload ?? []);

    $branding = \App\Models\AdminRecord::where('slug', 'website-branding')->first();
    $brandData = $normalizePayload($branding?->payload ?? []);
@endphp

<footer class="bg-navy text-white border-t border-white/10">
    {{-- Main Footer --}}
    <div class="container-app py-16">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 lg:gap-12">
            {{-- Brand Column --}}
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="mb-4">
                    <a href="{{ route('home') }}" class="text-2xl font-heading font-bold">
                        <span class="text-secondary-400">Lucky</span><span class="text-white">Boss</span>
                    </a>
                </div>
                <p class="text-sm text-slate-400 leading-relaxed max-w-xs">
                    Growth Partner in Your Hiring Journey. AI-powered recruitment for Singapore, Malaysia, India and beyond — Luckyboss Employment Agency Pte. Ltd.
                </p>

                {{-- Social Links --}}
                <div class="flex items-center gap-3 mt-6">
                    @foreach(['facebook' => $contactData['facebook_url'] ?? 'https://www.facebook.com/', 'linkedin' => $contactData['linkedin_url'] ?? 'https://www.linkedin.com/', 'instagram' => $contactData['instagram_url'] ?? 'https://www.instagram.com/'] as $platform => $url)
                        @if($url && $url !== '#')
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-secondary-500 flex items-center justify-center transition-colors text-white" aria-label="{{ ucfirst($platform) }}">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                @if($platform === 'facebook')
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                @elseif($platform === 'linkedin')
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                @elseif($platform === 'instagram')
                                    <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/>
                                @elseif($platform === 'youtube')
                                    <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                @endif
                            </svg>
                        </a>
                        @endif
                    @endforeach
                    @foreach([
                        'tiktok' => $contactData['tiktok_url'] ?? 'https://www.tiktok.com/',
                        'whatsapp' => $contactData['whatsapp_url'] ?? 'https://wa.me/',
                        'viber' => $contactData['viber_url'] ?? 'https://www.viber.com/',
                        'telegram' => $contactData['telegram_url'] ?? 'https://t.me/',
                    ] as $platform => $url)
                        @if($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-transform hover:-translate-y-0.5" aria-label="{{ ucfirst($platform) }}">
                                <img src="https://cdn.simpleicons.org/{{ $platform }}/white" alt="" class="w-4 h-4" loading="lazy">
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">For Job Seekers</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('jobs.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Browse Jobs</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Job Categories</a></li>
                    <li><a href="{{ route('register.seeker') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Create Account</a></li>
                    <li><a href="{{ route('blogs.index') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Career Blog</a></li>
                </ul>
            </div>

            {{-- Employer Links --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">For Employers</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('register.employer') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Post a Job</a></li>
                    <li><a href="{{ route('register.employer') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Subscription Plans</a></li>
                    <li><a href="{{ route('seekers.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Browse Candidates</a></li>
                    <li><a href="{{ route('contact.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Enterprise Plans</a></li>
                </ul>
            </div>

            {{-- Company --}}
            <div>
                <h4 class="text-sm font-bold text-white uppercase tracking-wider mb-4">Company</h4>
                <ul class="space-y-2.5">
                    <li><a href="{{ route('page.show', 'about-us') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">About Us</a></li>
                    <li><a href="{{ route('contact.public') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Contact</a></li>
                    <li><a href="{{ route('page.show', 'privacy-policy') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('page.show', 'terms-and-conditions') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Terms of Service</a></li>
                    <li><a href="{{ route('page.show', 'refund-policy') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">Refund Policy</a></li>
                    <li><a href="{{ route('page.show', 'faq') }}" class="text-sm text-slate-400 hover:text-secondary-400 transition-colors">FAQ</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom Bar --}}
    <div class="border-t border-white/10">
        <div class="container-app py-5 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-slate-500">&copy; {{ date('Y') }} Luckyboss Employment Agency Pte. Ltd. All rights reserved.</p>
            <p class="text-xs text-slate-500">Powered By: <a href="https://www.maactechnologies.com/" target="_blank" rel="noopener" class="text-secondary-400 hover:text-white">Maac Technologies</a> | All Rights Reserved.</p>
        </div>
    </div>
</footer>