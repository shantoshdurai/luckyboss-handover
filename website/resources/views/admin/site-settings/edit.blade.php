<x-admin-layout title="Portal Branding & Official Settings" heading="Branding & Contact Configuration">
    <div class="max-w-6xl">
        <form method="POST" action="{{ route('admin.site-settings.update') }}" enctype="multipart/form-data" class="space-y-8">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                {{-- Left Column: Branding, Logo, Favicon & Colors --}}
                <div class="lg:col-span-6 space-y-6">
                    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-accent flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-heading font-bold text-navy">Brand Identity</h2>
                                <p class="text-xs text-text-muted">Logo, favicon, and brand color palette.</p>
                            </div>
                        </div>

                        {{-- Website Logo --}}
                        <div class="mb-6">
                            <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Website & Portal Logo</label>
                            <div class="p-4 rounded-xl bg-surface-sunken border border-border flex items-center gap-5 mb-3">
                                <div class="bg-white p-2.5 rounded-lg border border-border shadow-2xs shrink-0">
                                    <img src="{{ $branding['logo_url'] ?? asset('images/lucky-boss-logo.png') }}" alt="Current Logo" class="h-10 w-auto object-contain max-w-[140px]">
                                </div>
                                <div class="text-xs text-text-muted">
                                    <span class="font-bold text-text-primary block mb-0.5">Current Logo</span>
                                    Recommended: Transparent PNG (400x100px)
                                </div>
                            </div>
                            <input type="file" name="logo" accept="image/*" class="w-full text-xs text-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-navy file:text-white hover:file:bg-navy/80 cursor-pointer">
                        </div>

                        {{-- Favicon --}}
                        <div class="mb-6">
                            <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Browser Favicon</label>
                            <div class="p-4 rounded-xl bg-surface-sunken border border-border flex items-center gap-5 mb-3">
                                <div class="w-12 h-12 bg-white rounded-lg border border-border flex items-center justify-center shadow-2xs shrink-0">
                                    <img src="{{ $branding['favicon_url'] ?? asset('uploads/branding/favicon-20260821075904.png') }}" alt="Favicon" class="w-7 h-7 object-contain">
                                </div>
                                <div class="text-xs text-text-muted">
                                    <span class="font-bold text-text-primary block mb-0.5">Current Icon</span>
                                    PNG or ICO (32x32px or 64x64px)
                                </div>
                            </div>
                            <input type="file" name="favicon" accept="image/png,image/jpeg,image/x-icon" class="w-full text-xs text-text-secondary file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-navy file:text-white hover:file:bg-navy/80 cursor-pointer">
                        </div>

                        {{-- Site Name --}}
                        <div class="mb-6">
                            <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Portal Name</label>
                            <input type="text" name="site_name" value="{{ $branding['site_name'] ?? 'Luckyboss Employment Agency Pte. Ltd' }}" class="form-input" required>
                        </div>

                        {{-- Brand Colors --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Primary Color</label>
                                <div class="flex items-center gap-3 p-2 rounded-xl border border-border bg-surface-sunken">
                                    <input type="color" name="primary_color" value="{{ $branding['primary_color'] ?? '#031f49' }}" class="w-8 h-8 rounded-lg border-0 cursor-pointer">
                                    <span class="text-xs font-mono font-bold text-text-primary">{{ $branding['primary_color'] ?? '#031f49' }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Secondary Color</label>
                                <div class="flex items-center gap-3 p-2 rounded-xl border border-border bg-surface-sunken">
                                    <input type="color" name="secondary_color" value="{{ $branding['secondary_color'] ?? '#18a66a' }}" class="w-8 h-8 rounded-lg border-0 cursor-pointer">
                                    <span class="text-xs font-mono font-bold text-text-primary">{{ $branding['secondary_color'] ?? '#18a66a' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SEO Settings Box --}}
                    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-heading font-bold text-navy">Search Engine Optimization (SEO)</h2>
                                <p class="text-xs text-text-muted">Global meta titles, descriptions, and OpenGraph tags.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Default Meta Title</label>
                                <input type="text" name="seo_title" value="{{ $branding['seo_title'] ?? 'Luckyboss Portal | AI-Powered Recruitment' }}" class="form-input" required maxlength="180">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Default Meta Description</label>
                                <textarea name="seo_description" rows="3" class="form-input" required maxlength="320">{{ $branding['seo_description'] ?? 'Find jobs, build your career, and manage recruitment with Luckyboss Portal.' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Official Contact & Socials --}}
                <div class="lg:col-span-6 space-y-6">
                    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                        <div class="flex items-center gap-3 pb-4 mb-6 border-b border-border">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-heading font-bold text-navy">Official Contact & Socials</h2>
                                <p class="text-xs text-text-muted">Public contact address, support email, and channels.</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Office Address</label>
                                <textarea name="office_address" rows="3" class="form-input" required>{{ $contact['office_address'] ?? 'Singapore Regional Hub' }}</textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Official Email</label>
                                    <input type="email" name="official_email" value="{{ $contact['official_email'] ?? 'support@luckyboss.org' }}" class="form-input" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Official Phone</label>
                                    <input type="text" name="official_phone" value="{{ $contact['official_phone'] ?? '+65 6123 4567' }}" class="form-input">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-border space-y-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">WhatsApp Direct URL</label>
                                    <input type="text" name="whatsapp_url" value="{{ $contact['whatsapp_url'] ?? 'https://wa.me/' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">LinkedIn Profile</label>
                                    <input type="text" name="linkedin_url" value="{{ $contact['linkedin_url'] ?? 'https://www.linkedin.com/' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">Facebook Page</label>
                                    <input type="text" name="facebook_url" value="{{ $contact['facebook_url'] ?? 'https://www.facebook.com/' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">Instagram Account</label>
                                    <input type="text" name="instagram_url" value="{{ $contact['instagram_url'] ?? 'https://www.instagram.com/' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">TikTok Profile</label>
                                    <input type="text" name="tiktok_url" value="{{ $contact['tiktok_url'] ?? 'https://www.tiktok.com/' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">Viber Contact URL</label>
                                    <input type="text" name="viber_url" value="{{ $contact['viber_url'] ?? '' }}" class="form-input">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-1">Telegram Profile</label>
                                    <input type="text" name="telegram_url" value="{{ $contact['telegram_url'] ?? 'https://t.me/' }}" class="form-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Gmail SMTP & Automated Mailers Configuration Box --}}
                    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
                        <div class="flex items-center justify-between pb-4 mb-6 border-b border-border">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <div>
                                    <h2 class="text-lg font-heading font-bold text-navy">Gmail SMTP Settings</h2>
                                    <p class="text-xs text-text-muted">Automated candidate notifications, password resets & employer emails.</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Active SMTP</span>
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Mail Mailer</label>
                                    <input type="text" name="mail_mailer" value="{{ $mailSettings['mail_mailer'] ?? 'smtp' }}" class="form-input font-mono text-xs" readonly>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Mail Host</label>
                                    <input type="text" name="mail_host" value="{{ $mailSettings['mail_host'] ?? 'smtp.gmail.com' }}" class="form-input font-mono text-xs" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Mail Port</label>
                                    <input type="number" name="mail_port" value="{{ $mailSettings['mail_port'] ?? 587 }}" class="form-input font-mono text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Encryption</label>
                                    <input type="text" name="mail_encryption" value="{{ $mailSettings['mail_encryption'] ?? 'tls' }}" class="form-input font-mono text-xs" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Gmail Username</label>
                                    <input type="email" name="mail_username" value="{{ $mailSettings['mail_username'] ?? 'luckybossea@gmail.com' }}" class="form-input font-mono text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Gmail App Password</label>
                                    <input type="password" name="mail_password" value="{{ $mailSettings['mail_password'] ?? 'onzswvfwffzyqptj' }}" class="form-input font-mono text-xs" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Mail From Address</label>
                                    <input type="email" name="mail_from_address" value="{{ $mailSettings['mail_from_address'] ?? 'luckybossea@gmail.com' }}" class="form-input font-mono text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Mail From Sender Name</label>
                                    <input type="text" name="mail_from_name" value="{{ $mailSettings['mail_from_name'] ?? 'Luckyboss' }}" class="form-input font-mono text-xs" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Sticky Bar --}}
            <div class="bg-white p-5 rounded-2xl border border-border shadow-md flex items-center justify-between">
                <p class="text-xs text-text-muted">Changes will be reflected across public web, mobile apps, and email footers immediately.</p>
                <button type="submit" class="btn btn-primary btn-lg shadow-md cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Save Branding & Settings</span>
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
