<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    private const TYPES = ['email-templates' => 'email', 'whatsapp-templates' => 'whatsapp', 'interview-templates' => 'interview', 'offer-templates' => 'offer', 'rejection-templates' => 'rejection', 'joining-templates' => 'joining'];

    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'email-templates';
        $type = self::TYPES[$view] ?? null;
        $templates = CommunicationTemplate::when($type, fn ($query) => $query->where('type', $type))->latest()->paginate(20)->withQueryString();
        $logs = CommunicationLog::with(['company', 'sender', 'recipient'])->latest()->paginate(20)->withQueryString();
        return view('admin.communication.index', compact('view', 'templates', 'logs'));
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['type' => ['required', 'in:email,whatsapp,interview,offer,rejection,joining'], 'name' => ['required', 'string', 'max:180'], 'subject' => ['nullable', 'string', 'max:255'], 'body' => ['required', 'string']]);
        CommunicationTemplate::create($data + ['is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Communication template created.');
    }

    public function updateTemplate(Request $request, CommunicationTemplate $template): RedirectResponse
    {
        $this->ensureAdmin();
        $template->update($request->validate(['type' => ['required', 'in:email,whatsapp,interview,offer,rejection,joining'], 'name' => ['required', 'string', 'max:180'], 'subject' => ['nullable', 'string', 'max:255'], 'body' => ['required', 'string'], 'is_active' => ['nullable', 'boolean']]));
        return back()->with('success', 'Communication template updated.');
    }

    public function destroyTemplate(CommunicationTemplate $template): RedirectResponse
    {
        $this->ensureAdmin(); $template->delete(); return back()->with('success', 'Communication template deleted.');
    }

    public function destroyLog(CommunicationLog $log): RedirectResponse
    {
        $this->ensureAdmin(); $log->delete(); return back()->with('success', 'Communication log deleted.');
    }
}
