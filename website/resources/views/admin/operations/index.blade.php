<x-admin-layout title="{{ $data['title'] }} | Luckyboss Admin" heading="{{ $data['title'] }}">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:22px">
        <div><h2 style="margin:0">{{ $data['title'] }}</h2><p style="margin:5px 0;color:#667085">Live configuration and operational records.</p></div>
        @if (in_array($area, ['packages', 'sliders', 'integrations']))
            <a class="admin-button" href="{{ route('admin.operations.create', $area) }}">Add record</a>
        @endif
    </div>
    @if (session('success'))<p style="color:#027a48;font-weight:700">{{ session('success') }}</p>@endif
    @if ($area === 'reports')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px">
            <article class="admin-card" style="padding:20px"><span style="color:#667085">Employer Revenue</span><strong style="display:block;font-size:28px">SGD {{ number_format(\App\Models\Payment::where('status', 'paid')->sum('amount'), 2) }}</strong></article>
            <article class="admin-card" style="padding:20px"><span style="color:#667085">Applications</span><strong style="display:block;font-size:28px">{{ \App\Models\JobApplication::count() }}</strong></article>
            <article class="admin-card" style="padding:20px"><span style="color:#667085">Active Subscriptions</span><strong style="display:block;font-size:28px">{{ \App\Models\Subscription::where('status', 'active')->count() }}</strong></article>
        </div>
    @else
        <section class="admin-card" style="padding:8px 18px;overflow:auto">
            <table class="admin-table">
                <thead><tr><th>Record</th><th>Details</th><th>Status</th><th style="text-align:right">Action</th></tr></thead>
                <tbody>
                    @forelse ($data['records'] as $record)
                        <tr>
                            @if ($area === 'packages')
                                <td><strong>{{ $record->name }}</strong></td><td>{{ $record->validity_days }} days · {{ implode(', ', array_keys($record->entitlements ?? [])) }}</td><td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td>
                            @elseif ($area === 'payments')
                                <td><strong>{{ $record->reference }}</strong></td><td>{{ $record->company?->name ?? $record->user?->name }} · {{ $record->currency_code }} {{ number_format($record->amount, 2) }}</td><td>{{ str($record->status)->headline() }}</td>
                            @elseif ($area === 'sliders')
                                <td>@if($record->image_path)<img src="{{ asset($record->image_path) }}" alt="{{ $record->title }}" style="width:70px;height:40px;object-fit:cover;vertical-align:middle;margin-right:8px">@endif<strong>{{ $record->title }}</strong></td><td>{{ $record->cta_text }} · Position {{ $record->sort_order }}</td><td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td>
                            @elseif ($area === 'integrations')
                                <td><strong>{{ $record->name }}</strong></td><td>{{ $record->provider }} · {{ $record->environment }} · {{ number_format($record->usage_count) }} requests</td><td>{{ $record->is_enabled ? 'Enabled' : 'Disabled' }}</td>
                            @else
                                <td><strong>{{ $record->title }}</strong></td><td>{{ $record->user?->name }} · {{ $record->type }}</td><td>{{ $record->read_at ? 'Read' : 'Unread' }}</td>
                            @endif
                            <td style="text-align:right">
                                @if (in_array($area, ['packages', 'sliders', 'integrations']))
                                    <a href="{{ route('admin.operations.edit', [$area, $record]) }}" style="color:#465fff;font-weight:700">Edit</a>
                                    <form method="POST" action="{{ route('admin.operations.destroy', [$area, $record]) }}" style="display:inline">@csrf @method('DELETE')<button style="border:0;background:none;color:#b42318;cursor:pointer;margin-left:10px">Delete</button></form>
                                @else
                                    <span style="color:#667085">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="padding:20px;color:#667085">No records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif
</x-admin-layout>