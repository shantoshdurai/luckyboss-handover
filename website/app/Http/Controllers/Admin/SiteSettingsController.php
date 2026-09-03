<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    private function admin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function edit(SiteSettingsService $settings): View
    {
        $this->admin();
        $mailRecord = AdminRecord::where('module', 'mail-settings')->where('slug', 'gmail-smtp')->first();
        $mailSettings = $mailRecord?->payload ?? [
            'mail_mailer' => config('mail.default', 'smtp'),
            'mail_host' => config('mail.mailers.smtp.host', 'smtp.gmail.com'),
            'mail_port' => config('mail.mailers.smtp.port', 587),
            'mail_username' => config('mail.mailers.smtp.username', 'luckybossea@gmail.com'),
            'mail_password' => config('mail.mailers.smtp.password', 'onzswvfwffzyqptj'),
            'mail_encryption' => config('mail.mailers.smtp.encryption', 'tls'),
            'mail_from_address' => config('mail.from.address', 'luckybossea@gmail.com'),
            'mail_from_name' => config('mail.from.name', 'Luckyboss'),
        ];
        return view('admin.site-settings.edit', [
            'branding' => $settings->branding(), 
            'contact' => $settings->contact(),
            'mailSettings' => $mailSettings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->admin();
        $data = $request->validate([
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 
            'secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'], 
            'logo' => ['nullable', 'image', 'max:2048'], 
            'favicon' => ['nullable', 'image', 'max:1024'], 
            'site_name' => ['required', 'string', 'max:120'], 
            'seo_title' => ['required', 'string', 'max:180'], 
            'seo_description' => ['required', 'string', 'max:320'], 
            'office_address' => ['required', 'string', 'max:1000'], 
            'official_email' => ['required', 'email'], 
            'official_phone' => ['nullable', 'string', 'max:50'], 
            'facebook_url' => ['nullable', 'url'], 
            'instagram_url' => ['nullable', 'url'], 
            'tiktok_url' => ['nullable', 'url'],
            'linkedin_url' => ['nullable', 'url'], 
            'youtube_url' => ['nullable', 'url'], 
            'whatsapp_url' => ['nullable', 'url'],
            'viber_url' => ['nullable', 'url'],
            'telegram_url' => ['nullable', 'url'],
            'mail_mailer' => ['nullable', 'string'],
            'mail_host' => ['nullable', 'string'],
            'mail_port' => ['nullable', 'numeric'],
            'mail_username' => ['nullable', 'string'],
            'mail_password' => ['nullable', 'string'],
            'mail_encryption' => ['nullable', 'string'],
            'mail_from_address' => ['nullable', 'string'],
            'mail_from_name' => ['nullable', 'string'],
        ]);
        $current = app(SiteSettingsService::class)->branding();
        $branding = ['logo_url' => $current['logo_url'], 'favicon_url' => $current['favicon_url'] ?? $current['logo_url'], 'site_name' => $data['site_name'], 'seo_title' => $data['seo_title'], 'seo_description' => $data['seo_description'], 'primary_color' => $data['primary_color'], 'secondary_color' => $data['secondary_color']];
        if ($request->hasFile('logo')) { $file = $request->file('logo'); $directory = public_path('uploads/branding'); if (! is_dir($directory)) mkdir($directory, 0755, true); $name = 'lucky-boss-'.now()->format('YmdHis').'.'.$file->extension(); $file->move($directory, $name); $branding['logo_url'] = 'uploads/branding/'.$name; }
        if ($request->hasFile('favicon')) { $file = $request->file('favicon'); $directory = public_path('uploads/branding'); if (! is_dir($directory)) mkdir($directory, 0755, true); $name = 'favicon-'.now()->format('YmdHis').'.'.$file->extension(); $file->move($directory, $name); $branding['favicon_url'] = 'uploads/branding/'.$name; }
        AdminRecord::updateOrCreate(['module' => 'branding', 'slug' => 'website-branding'], ['name' => 'Website Branding', 'description' => 'Public website and portal brand settings', 'payload' => $branding, 'is_active' => true]);
        AdminRecord::updateOrCreate(['module' => 'contact-information', 'slug' => 'official-contact'], ['name' => 'Official Contact', 'description' => 'Official public office and contact details', 'payload' => collect($data)->only(['office_address','official_email','official_phone','facebook_url','instagram_url','tiktok_url','linkedin_url','whatsapp_url','viber_url','telegram_url'])->all(), 'is_active' => true]);
        
        if ($request->filled('mail_username')) {
            $mailPayload = [
                'mail_mailer' => $data['mail_mailer'] ?? 'smtp',
                'mail_host' => $data['mail_host'] ?? 'smtp.gmail.com',
                'mail_port' => $data['mail_port'] ?? 587,
                'mail_username' => $data['mail_username'] ?? 'luckybossea@gmail.com',
                'mail_password' => $data['mail_password'] ?? 'onzswvfwffzyqptj',
                'mail_encryption' => $data['mail_encryption'] ?? 'tls',
                'mail_from_address' => $data['mail_from_address'] ?? 'luckybossea@gmail.com',
                'mail_from_name' => $data['mail_from_name'] ?? 'Luckyboss',
            ];
            AdminRecord::updateOrCreate(['module' => 'mail-settings', 'slug' => 'gmail-smtp'], ['name' => 'Gmail SMTP Configuration', 'description' => 'Official Gmail SMTP settings for automated platform mailers', 'payload' => $mailPayload, 'is_active' => true]);
        }

        return back()->with('success', 'Branding, contact and Gmail SMTP configuration updated successfully.');
    }
}