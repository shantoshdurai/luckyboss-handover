<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportController extends Controller
{
    private function ensureAdmin(): void { abort_unless(auth()->user()?->hasRole('super-admin'), 403); }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $view = $request->string('view')->toString() ?: 'all-queries';
        $query = SupportTicket::with(['user', 'assignee'])->latest();
        match ($view) {
            'new-queries' => $query->where('status', 'new'),
            'open' => $query->where('status', 'open'),
            'in-progress' => $query->where('status', 'in-progress'),
            'resolved' => $query->where('status', 'resolved'),
            'closed' => $query->where('status', 'closed'),
            'assigned-agent' => $query->whereNotNull('assigned_to'),
            default => null,
        };
        return view('admin.support.index', ['view' => $view, 'tickets' => $query->paginate(20)->withQueryString(), 'agents' => User::whereHas('roles', fn ($builder) => $builder->whereIn('slug', ['super-admin', 'support-agent']))->orderBy('name')->get()]);
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->ensureAdmin();
        $ticket->update($request->validate(['status' => ['required', 'in:new,open,in-progress,resolved,closed'], 'priority' => ['required', 'in:low,normal,high'], 'assigned_to' => ['nullable', 'exists:users,id']]));
        return back()->with('success', 'Support query updated.');
    }

    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        $this->ensureAdmin(); $ticket->delete(); return back()->with('success', 'Support query deleted.');
    }
}
