<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRecord;
use App\Models\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'notification-dashboard';
        $query = PlatformNotification::with('user')->latest();
        match ($view) {
            'admin-alerts' => $query->whereHas('user.roles', fn ($builder) => $builder->where('slug', 'super-admin')),
            'employer-alerts' => $query->whereHas('user.roles', fn ($builder) => $builder->where('slug', 'employer')),
            'job-seeker-alerts' => $query->whereHas('user.roles', fn ($builder) => $builder->where('slug', 'job-seeker')),
            'push-notifications' => $query->where('type', 'push'),
            'email-notifications' => $query->where('type', 'email'),
            'whatsapp-notifications' => $query->where('type', 'whatsapp'),
            default => null,
        };
        return view('admin.notifications.index', ['view' => $view, 'notifications' => $query->paginate(20)->withQueryString(), 'sounds' => AdminRecord::where('module', 'notification-sounds')->latest()->get(), 'types' => PlatformNotification::select('type')->distinct()->orderBy('type')->pluck('type')]);
    }

    public function storeSound(Request $request): RedirectResponse
    {
        $this->ensureAdmin();
        $data = $request->validate(['name' => ['required', 'string', 'max:150'], 'description' => ['nullable', 'string'], 'payload' => ['nullable', 'string']]);
        AdminRecord::create(['module' => 'notification-sounds', 'name' => $data['name'], 'slug' => str($data['name'])->slug(), 'description' => $data['description'] ?? null, 'payload' => filled($data['payload'] ?? null) ? json_decode($data['payload'], true) : [], 'is_active' => true]);
        return back()->with('success', 'Notification sound created.');
    }

    public function destroySound(AdminRecord $sound): RedirectResponse
    {
        $this->ensureAdmin(); abort_unless($sound->module === 'notification-sounds', 404); $sound->delete(); return back()->with('success', 'Notification sound deleted.');
    }

    public function destroy(PlatformNotification $notification): RedirectResponse
    {
        $this->ensureAdmin(); $notification->delete(); return back()->with('success', 'Notification deleted.');
    }
}
