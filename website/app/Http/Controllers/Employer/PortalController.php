<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\EmployerPortalRecord;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class PortalController extends Controller
{
    private function company(): ?Company
    {
        $user = auth()->user();
        if (! $user) return null;

        $company = $user->companies()->first();
        if ($company) return $company;

        if ($user->hasRole('super-admin')) {
            return Company::first() ?: Company::create([
                'name' => 'Luckyboss Global Recruitment',
                'country_code' => 'SGP',
                'status' => 'verified',
            ]);
        }

        if ($user->hasRole('employer')) {
            $company = Company::create([
                'name' => ($user->name ?: 'Corporate') . ' Enterprise',
                'country_code' => $user->country_code ?? 'SGP',
                'status' => 'verified',
            ]);
            $company->users()->attach($user->id, ['company_role' => 'company-admin', 'is_active' => true]);
            return $company;
        }

        return Company::first();
    }

    public function index(Request $request, string $section)
    {
        $user = auth()->user();
        if (! $user) {
            return redirect()->route('login')->with('info', 'Please sign in to access the Employer Portal.');
        }

        if ($user->hasRole('job-seeker') && ! $user->hasRole('employer') && ! $user->hasRole('super-admin')) {
            return redirect()->route('seeker.dashboard')->with('info', 'Logged in as Job Seeker. Redirected to your Candidate Workspace.');
        }

        $company = $this->company();
        if (! $company) {
            return redirect()->route('login')->with('info', 'Company record not found.');
        }
        
        $aliasMap = [
            'company-profile' => 'profile',
            'talent-pool' => 'candidate-search',
            'team' => 'team-users',
            'ai-configuration' => 'ai-tools',
            'subscriptions' => 'subscription',
            'applications' => 'candidates',
            'analytics' => 'reports',
            'settings' => 'profile',
        ];
        if (isset($aliasMap[$section])) {
            $section = $aliasMap[$section];
        }
        if ($section === 'jobs') {
            return redirect()->route('employer.jobs.index');
        }

        $allowed = [
            'candidates', 'recruitment', 'interviews', 'offers', 'candidate-search', 
            'messages', 'reports', 'team-users', 'subscription', 'billing', 'ai-tools', 
            'notifications', 'profile', 'settings', 'support', 'analytics'
        ];
        if (! in_array($section, $allowed, true)) {
            $section = 'candidates';
        }
        
        $records = EmployerPortalRecord::where('company_id', $company->id)->where('section', $section)->latest()->get();
        $aiConfiguration = $section === 'ai-tools'
            ? EmployerPortalRecord::where('company_id', $company->id)->where('section', 'ai-configuration')->where('name', 'Company AI Configuration')->first()
            : null;
        $applications = JobApplication::with(['candidate.candidateProfile', 'job'])
            ->whereHas('job', fn ($query) => $query->where('company_id', $company->id))
            ->latest('last_activity_at')
            ->get();
        $subscription = $company->subscriptions()->with('package')->latest('expires_at')->first();
        $entitlements = $subscription?->entitlements ?? $subscription?->package?->entitlements ?? [];
        
        $data = [
            'company' => $company,
            'section' => $section,
            'records' => $records,
            'users' => $company->users()->with('roles')->get(),
            'applications' => $applications,
            'interviews' => Interview::with('application.candidate', 'application.job')->where('company_id', $company->id)->latest('scheduled_at')->get(),
            'offers' => Offer::with('application.candidate', 'application.job')->where('company_id', $company->id)->latest()->get(),
            'subscription' => $subscription,
            'payments' => Payment::where('company_id', $company->id)->latest()->get(),
            'countries' => Country::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'aiConfiguration' => $aiConfiguration,
            'byoAiAllowed' => app(\App\Services\FeatureFlagService::class)->enabled('employer_byoai_enabled') && (bool) data_get($entitlements, 'byoai', false),
        ];

        if ($section === 'candidate-search') {
            $data['candidates'] = User::with('candidateProfile')
                ->whereHas('roles', fn ($query) => $query->where('slug', 'job-seeker'))
                ->when($request->filled('keyword'), fn ($query) => $query->where(function ($search) use ($request): void {
                    $search->where('name', 'like', '%'.$request->string('keyword').'%')->orWhere('email', 'like', '%'.$request->string('keyword').'%');
                }))
                ->when($request->filled('location'), fn ($query) => $query->whereHas('candidateProfile', fn ($profile) => $profile->where('current_location', 'like', '%'.$request->string('location').'%')))
                ->latest()
                ->take(50)
                ->get();
        }

        return view('employer.portal.index', $data);
    }

    public function updateAiConfiguration(Request $request): RedirectResponse
    {
        $company = $this->company();
        abort_unless(auth()->user()->companies()->where('companies.id', $company->id)->wherePivot('company_role', 'company-admin')->exists() || auth()->user()->hasRole('super-admin'), 403);
        $subscription = $company->subscriptions()->where('status', 'active')->whereDate('expires_at', '>=', today())->with('package')->latest('expires_at')->first();
        $byoAiAllowed = app(\App\Services\FeatureFlagService::class)->enabled('employer_byoai_enabled') && (bool) data_get($subscription?->entitlements ?? $subscription?->package?->entitlements ?? [], 'byoai', false);
        
        $data = $request->validate(['mode' => ['required', 'in:automatic,lucky_boss_first,company_first'], 'provider' => ['required', 'in:OpenAI'], 'model' => ['required', 'string', 'max:100'], 'api_key' => ['nullable', 'string', 'max:500'], 'company_ai_enabled' => ['nullable', 'boolean']]);
        $record = EmployerPortalRecord::firstOrNew(['company_id' => $company->id, 'section' => 'ai-configuration', 'name' => 'Company AI Configuration']);
        $payload = $record->payload ?? [];
        $payload = array_merge($payload, ['mode' => $data['mode'], 'provider' => $data['provider'], 'model' => $data['model'], 'company_ai_enabled' => $request->boolean('company_ai_enabled'), 'api_key_status' => filled($data['api_key'] ?? null) ? 'connected' : ($payload['api_key_status'] ?? 'not_connected'), 'last_tested_at' => $payload['last_tested_at'] ?? null]);
        if (filled($data['api_key'] ?? null)) $payload['encrypted_api_key'] = Crypt::encryptString($data['api_key']);
        $record->fill(['created_by' => auth()->id(), 'description' => 'Company-owned AI configuration', 'payload' => $payload, 'is_active' => true])->save();
        $this->auditAi('AI configuration updated', $company->id, ['mode' => $data['mode'], 'provider' => $data['provider'], 'api_key_changed' => filled($data['api_key'] ?? null)]);
        return back()->with('success', 'AI configuration saved securely.');
    }

    public function testAiConfiguration(): RedirectResponse
    {
        $company = $this->company();
        $record = EmployerPortalRecord::where('company_id', $company->id)->where('section', 'ai-configuration')->where('name', 'Company AI Configuration')->firstOrFail();
        $payload = $record->payload ?? [];
        $status = 'failed';
        if (! empty($payload['encrypted_api_key'])) {
            try { $response = Http::withToken(Crypt::decryptString($payload['encrypted_api_key']))->timeout(8)->get('https://api.openai.com/v1/models'); $status = $response->successful() ? 'connected' : 'invalid'; } catch (\Throwable) { $status = 'provider_error'; }
        }
        $payload['api_key_status'] = $status;
        $payload['last_tested_at'] = now()->toDateTimeString();
        $record->update(['payload' => $payload]);
        $this->auditAi('AI connection tested', $company->id, ['status' => $status]);
        return back()->with($status === 'connected' ? 'success' : 'error', $status === 'connected' ? 'Connection successful.' : 'Connection failed. Check the API key or provider status.');
    }

    public function removeAiConfiguration(): RedirectResponse
    {
        $company = $this->company();
        abort_unless(auth()->user()->companies()->where('companies.id', $company->id)->wherePivot('company_role', 'company-admin')->exists() || auth()->user()->hasRole('super-admin'), 403);
        $record = EmployerPortalRecord::where('company_id', $company->id)->where('section', 'ai-configuration')->where('name', 'Company AI Configuration')->first();
        if ($record) { $payload = $record->payload ?? []; unset($payload['encrypted_api_key']); $payload['api_key_status'] = 'not_connected'; $record->update(['payload' => $payload]); }
        $this->auditAi('AI API key removed', $company->id);
        return back()->with('success', 'Company AI API key removed.');
    }

    private function auditAi(string $action, int $companyId, array $details = []): void
    {
        if (class_exists(\App\Models\AuditLog::class)) \App\Models\AuditLog::create(['user_id' => auth()->id(), 'company_id' => $companyId, 'action' => $action, 'entity_type' => EmployerPortalRecord::class, 'new_values' => $details, 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    public function store(Request $request, string $section): RedirectResponse
    {
        $company = $this->company();
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string']]);
        EmployerPortalRecord::create(['company_id' => $company->id, 'created_by' => auth()->id(), 'section' => $section, 'name' => $data['name'], 'description' => $data['description'] ?? null, 'payload' => filled($data['payload'] ?? null) ? json_decode($data['payload'], true) : [], 'is_active' => true]);
        return back()->with('success', 'Employer record created.');
    }

    public function update(Request $request, EmployerPortalRecord $record): RedirectResponse
    {
        abort_unless($record->company_id === $this->company()->id || auth()->user()->hasRole('super-admin'), 403);
        $record->update($request->validate(['name' => ['required', 'string', 'max:180'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']]));
        return back()->with('success', 'Employer record updated.');
    }

    public function destroy(EmployerPortalRecord $record): RedirectResponse
    {
        abort_unless($record->company_id === $this->company()->id || auth()->user()->hasRole('super-admin'), 403);
        $record->delete();
        return back()->with('success', 'Employer record deleted.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $company = $this->company();
        $data = $request->validate(['name' => ['required', 'string', 'max:180'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'string', 'max:32'], 'website' => ['nullable', 'url'], 'industry' => ['nullable', 'string', 'max:120'], 'country_code' => ['nullable', 'exists:countries,code'], 'logo' => ['nullable', 'image', 'max:4096']]);
        if ($request->hasFile('logo')) { $file = $request->file('logo'); $directory = public_path('uploads/companies'); if (! is_dir($directory)) mkdir($directory, 0755, true); $name = 'company-'.now()->format('YmdHis').'-'.Str::random(6).'.'.$file->extension(); $file->move($directory, $name); $data['logo_path'] = 'uploads/companies/'.$name; }
        unset($data['logo']);
        $company->update($data);
        return back()->with('success', 'Company profile updated.');
    }
}
