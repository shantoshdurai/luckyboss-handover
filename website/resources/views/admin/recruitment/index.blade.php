<x-admin-layout title="{{ Str::headline($view) }} | Recruitment Pipeline" heading="ATS Recruitment Pipeline">
    <div class="space-y-6">
        {{-- Header Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-border shadow-sm">
            <div>
                <h2 class="text-xl font-heading font-bold text-navy">Application Tracking System (ATS)</h2>
                <p class="text-xs text-text-secondary mt-0.5">Track candidate workflows, match scores, interview milestones, and job offers.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.interviews.index') }}" class="btn btn-primary btn-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Interview Calendar</span>
                </a>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="bg-white p-4 rounded-2xl border border-border shadow-sm">
            <form method="GET" action="{{ route('admin.recruitment.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <input type="hidden" name="view" value="{{ $view }}">
                <div class="flex-1 relative w-full">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search candidate name, email, or applied position..." class="form-input text-xs pl-9 w-full">
                    <svg class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-6 w-full sm:w-auto">Search</button>
                @if(!empty($filters['search']))
                    <a href="{{ route('admin.recruitment.index', ['view' => $view]) }}" class="btn btn-outline btn-sm">Clear</a>
                @endif
            </form>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-surface-sunken border-b border-border text-xs uppercase font-bold text-text-muted tracking-wider">
                            <th class="py-3.5 px-6">Candidate Details</th>
                            <th class="py-3.5 px-6">Target Role & Company</th>
                            <th class="py-3.5 px-6">Applied Date</th>
                            <th class="py-3.5 px-6 text-center">AI Match Score</th>
                            <th class="py-3.5 px-6">Current Stage</th>
                            <th class="py-3.5 px-6">Update Pipeline Stage</th>
                            <th class="py-3.5 px-6 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($applications as $application)
                            <tr class="hover:bg-surface/60 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-accent to-secondary-400 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                            {{ substr($application->candidate->name ?? 'C', 0, 2) }}
                                        </div>
                                        <div>
                                            <span class="font-bold text-navy block">{{ $application->candidate->name ?? 'Unknown' }}</span>
                                            <span class="text-xs text-text-muted">{{ $application->candidate->email ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="font-bold text-navy block text-sm">{{ $application->job->title ?? 'Position' }}</span>
                                    <span class="text-xs text-text-secondary">{{ $application->job->company->name ?? 'Luckyboss Partner' }}</span>
                                </td>
                                <td class="py-4 px-6 text-xs text-text-muted font-medium">
                                    {{ $application->applied_at ? $application->applied_at->format('M d, Y') : '-' }}
                                </td>
                                <td class="py-4 px-6 text-center">
                                    @php $score = $application->match_score ?? 0; @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold {{ $score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($score >= 60 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                                        {{ $score }}%
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-surface-sunken text-navy border border-border">
                                        {{ $application->status }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <form method="POST" action="{{ route('admin.recruitment.status', $application) }}" class="flex items-center gap-2">
                                        @csrf
                                        <select name="status" class="form-input text-xs py-1.5 px-2.5 w-36">
                                            @foreach($statuses as $status)
                                                <option value="{{ $status }}" @selected($application->status === $status)>{{ $status }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="remark" placeholder="Remark..." class="form-input text-xs py-1.5 px-2 w-28">
                                        <button type="submit" class="btn btn-primary btn-sm py-1.5 px-3 text-xs cursor-pointer">Save</button>
                                    </form>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <form method="POST" action="{{ route('admin.recruitment.destroy', $application) }}" onsubmit="return confirm('Delete this application record?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-danger hover:underline cursor-pointer">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-text-muted">
                                    No applications found matching the current pipeline filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>
