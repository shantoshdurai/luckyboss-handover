<x-admin-layout title="Credits & Entitlements" heading="Credits & Entitlements">
    <div class="max-w-5xl space-y-6">

        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 space-y-1">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        {{-- The switch that turns metering into billing. --}}
        <form method="POST" action="{{ route('admin.entitlements.enforcement') }}"
              class="rounded-2xl p-6 sm:p-8 border shadow-sm {{ $enforcing ? 'border-amber-200 bg-amber-50' : 'border-border bg-white' }}">
            @csrf
            @method('PUT')

            <div class="flex items-start justify-between gap-4 pb-4 mb-6 border-b {{ $enforcing ? 'border-amber-200' : 'border-border' }}">
                <div>
                    <h2 class="text-lg font-heading font-bold text-navy">Charging</h2>
                    <p class="text-xs text-text-muted">Whether running out of credits actually stops an employer.</p>
                </div>
                <span class="shrink-0 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                             {{ $enforcing ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-700 border border-slate-300' }}">
                    {{ $enforcing ? 'Enforcing' : 'Free for everyone' }}
                </span>
            </div>

            <label class="flex items-start gap-3 cursor-pointer group">
                <input type="hidden" name="enforcement_enabled" value="0">
                <input type="checkbox" name="enforcement_enabled" value="1" @checked($enforcing)
                       class="mt-0.5 w-4 h-4 rounded border-slate-300 text-accent focus:ring-accent cursor-pointer">
                <span>
                    <span class="block text-sm font-bold text-navy group-hover:text-accent">Refuse actions when credits run out</span>
                    <span class="block text-[11px] text-text-muted mt-0.5 leading-relaxed">
                        While this is off, every billable action is still <strong>counted</strong> — the ledger below fills up
                        exactly as it would if we were charging — but nobody is ever blocked. That is what lets us
                        run at zero today and switch to paid later without changing any code.
                        <strong>Leave it off until prices are agreed per market.</strong>
                    </span>
                </span>
            </label>

            <div class="mt-6 pt-5 border-t {{ $enforcing ? 'border-amber-200' : 'border-border' }} flex justify-end">
                <button type="submit" class="btn btn-primary cursor-pointer">Save</button>
            </div>
        </form>

        {{-- Spec §67 + §79: what AI actually costs us. Without this an AI tier is
             priced blind — §12 lets admin sell "100 AI candidate searches" and
             nothing here could say whether that was profitable. --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4 pb-4 mb-6 border-b border-border">
                <div>
                    <h2 class="text-lg font-heading font-bold text-navy">What AI costs us</h2>
                    <p class="text-xs text-text-muted">Lucky Boss spend only. Employers on their own API key are counted separately.</p>
                </div>
                <span class="shrink-0 text-right">
                    <span class="block text-2xl font-heading font-extrabold text-navy">USD {{ number_format($aiCost['this_month'], 2) }}</span>
                    <span class="block text-[11px] text-text-muted">this month &middot; last month USD {{ number_format($aiCost['last_month'], 2) }}</span>
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @foreach ([
                    'AI calls' => $aiCost['calls_this_month'],
                    'Failed' => $aiCost['failed_this_month'],
                    'On employer keys' => $aiCost['byoai_calls_this_month'],
                    'Features used' => $aiCost['by_feature']->count(),
                ] as $label => $value)
                    <div class="rounded-xl border border-border p-3.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-text-secondary">{{ $label }}</p>
                        <p class="text-lg font-extrabold text-navy mt-1">{{ number_format($value) }}</p>
                    </div>
                @endforeach
            </div>

            @if ($aiCost['by_feature']->isNotEmpty())
                <div class="mt-5 pt-5 border-t border-border space-y-2">
                    @foreach ($aiCost['by_feature'] as $row)
                        <div class="flex items-center justify-between gap-4 text-xs">
                            <span class="font-semibold text-navy">{{ Str::headline($row->feature) }}</span>
                            <span class="text-text-muted">{{ number_format($row->calls) }} calls &middot; <span class="font-bold text-navy">USD {{ number_format((float) $row->cost, 2) }}</span></span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-5 pt-5 border-t border-border text-xs text-text-muted italic">
                    No AI calls recorded this month yet. Every call records its model, tokens and estimated
                    cost &mdash; including the ones that fail, because those tokens were spent too.
                </p>
            @endif
        </div>

        {{-- Grant credits. The only way they arrive until a gateway exists. --}}
        <form method="POST" action="{{ route('admin.entitlements.grant') }}" class="bg-white rounded-2xl p-6 sm:p-8 border border-border shadow-sm">
            @csrf

            <div class="pb-4 mb-6 border-b border-border">
                <h2 class="text-lg font-heading font-bold text-navy">Give an employer credits</h2>
                <p class="text-xs text-text-muted">There is no online checkout yet, so this is how credits are added.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="company_id" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Company</label>
                    <select id="company_id" name="company_id" class="form-input" required>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="key" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Credit</label>
                    <select id="key" name="key" class="form-input" required>
                        @foreach ($employerSkus as $key => $sku)
                            <option value="{{ $key }}">{{ $sku['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="quantity" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">How many</label>
                    <input id="quantity" name="quantity" type="number" min="1" max="10000" value="10" class="form-input font-mono" required>
                </div>
                <div>
                    <label for="note" class="block text-xs font-bold uppercase tracking-wider text-text-secondary mb-2">Note <span class="font-normal normal-case">(optional)</span></label>
                    <input id="note" name="note" type="text" maxlength="255" class="form-input" placeholder="Invoice 1042, paid by transfer">
                </div>
            </div>

            <div class="mt-6 pt-5 border-t border-border flex justify-end">
                <button type="submit" class="btn btn-primary cursor-pointer">Add credits</button>
            </div>
        </form>

        {{-- Who has what. --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm overflow-hidden">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-bold text-navy">Employer balances</h2>
                <p class="text-xs text-text-muted mt-0.5">Including this month&rsquo;s free allowance.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-border bg-slate-50">
                            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">Company</th>
                            @foreach ($employerSkus as $sku)
                                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary text-right">{{ $sku['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse ($companies as $company)
                            <tr>
                                <td class="px-5 py-3.5 text-sm font-semibold text-navy">{{ $company->name }}</td>
                                @foreach ($employerSkus as $key => $sku)
                                    @php($remaining = $balances[$company->id][$key]['remaining'] ?? 0)
                                    <td class="px-5 py-3.5 text-right text-sm font-bold {{ $remaining > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                                        {{ $remaining }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($employerSkus) + 1 }}" class="px-5 py-5 text-sm text-text-muted italic">No companies yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Seeker SKUs, listed so the zero-priced ones are visible rather than
             hidden. This is the half sir asked to be able to flip later. --}}
        <div class="bg-white rounded-2xl border border-border shadow-sm p-6 sm:p-8">
            <div class="pb-4 mb-5 border-b border-border">
                <h2 class="text-base font-bold text-navy">Job seeker actions</h2>
                <p class="text-xs text-text-muted mt-0.5">
                    Free and unlimited today, and priced at zero rather than exempted &mdash; so they can be
                    switched to paid from here later without any code change. Usage is already being recorded.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($seekerSkus as $key => $sku)
                    <div class="rounded-xl border border-border p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-bold text-navy">{{ $sku['label'] }}</span>
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200">Free</span>
                        </div>
                        <p class="text-[11px] text-text-muted mt-1.5 leading-relaxed">{{ $sku['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-admin-layout>
