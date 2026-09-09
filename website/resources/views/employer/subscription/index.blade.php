<x-employer-shell title="Subscription">
    <div class="space-y-6" x-data="{ tab: 'plan' }">

        @if (session('info'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold text-navy">{{ session('info') }}</div>
        @endif
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-heading font-extrabold text-navy">Subscription</h1>
                <p class="text-sm text-text-secondary mt-1">{{ $company->name }}</p>
            </div>

            <div class="flex gap-1 p-1 rounded-2xl bg-slate-100">
                <button type="button" @click="tab = 'plan'"
                        :class="tab === 'plan' ? 'bg-white text-navy shadow-xs' : 'text-text-secondary hover:text-navy'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer">Your current plan</button>
                <button type="button" @click="tab = 'catalogue'"
                        :class="tab === 'catalogue' ? 'bg-white text-navy shadow-xs' : 'text-text-secondary hover:text-navy'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer">What you can buy</button>
                <button type="button" @click="tab = 'history'"
                        :class="tab === 'history' ? 'bg-white text-navy shadow-xs' : 'text-text-secondary hover:text-navy'"
                        class="px-4 py-2 rounded-xl text-xs font-bold transition-colors cursor-pointer">History</button>
            </div>
        </div>

        @unless ($enforcing)
            {{-- Said out loud rather than implied. A page that shows limits it
                 does not apply is the kind of thing that gets discovered in a
                 demo. --}}
            <div class="rounded-2xl border border-accent/30 bg-accent/5 px-5 py-4">
                <p class="text-sm font-bold text-navy">Everything is free while we get started.</p>
                <p class="text-xs text-text-secondary mt-1 leading-relaxed">
                    Your usage is being counted below, but nothing is being charged and nothing will be
                    refused. We&rsquo;ll tell you well before that changes.
                </p>
            </div>
        @endunless

        {{-- ── Your current plan: the Name / Remaining table ───────────── --}}
        <div x-show="tab === 'plan'" x-cloak class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-bold text-navy">What you have left</h2>
                <p class="text-xs text-text-muted mt-0.5">Free allowances reset on the 1st of each month.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-border bg-slate-50">
                            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">Name</th>
                            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">Free each month</th>
                            <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary text-right">Remaining</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($balances as $key => $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-semibold text-navy">{{ $row['label'] }}</span>
                                </td>
                                <td class="px-5 py-4 text-xs text-text-secondary">
                                    {{ $row['free_tier_monthly'] ? $row['free_tier_monthly'].' '.Str::plural($row['unit'], $row['free_tier_monthly']) : '—' }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    @if ($row['unlimited'])
                                        <span class="text-sm font-bold text-emerald-600">Unlimited</span>
                                    @elseif ($row['remaining'] > 0)
                                        <span class="text-sm font-bold text-emerald-600">{{ $row['remaining'] }} left</span>
                                    @else
                                        <span class="text-sm font-bold text-amber-600">None left</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── What you can buy ────────────────────────────────────────── --}}
        <div x-show="tab === 'catalogue'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($skus as $key => $sku)
                <div class="bg-white rounded-2xl border border-border p-6 shadow-xs flex flex-col">
                    <h3 class="text-sm font-bold text-navy">{{ $sku['label'] }}</h3>
                    <p class="text-xs text-text-secondary mt-1.5 leading-relaxed flex-1">{{ $sku['description'] }}</p>

                    <div class="mt-5 pt-4 border-t border-border">
                        @if ($sku['price'] > 0)
                            <p class="text-sm font-extrabold text-navy">
                                {{ $sku['currency'] }} {{ number_format($sku['price']) }}
                                <span class="text-xs font-semibold text-text-muted">for {{ $sku['pack_size'] }}</span>
                            </p>
                        @else
                            {{-- No fabricated price. Sir has not signed these off
                                 per market yet, and a placeholder number on a
                                 billing page is the kind of thing that gets
                                 quoted back to us. --}}
                            <p class="text-sm font-bold text-text-muted">Price not set yet</p>
                        @endif

                        <p class="text-xs text-text-muted mt-1">
                            @if ($sku['free_tier_monthly'])
                                {{ $sku['free_tier_monthly'] }} free every month
                            @else
                                No free allowance
                            @endif
                        </p>
                    </div>
                </div>
            @endforeach

            <div class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-border bg-slate-50 p-5">
                <p class="text-xs text-text-secondary leading-relaxed">
                    Buying online isn&rsquo;t switched on yet. To add credits now, contact your Lucky Boss
                    account manager and we&rsquo;ll apply them to {{ $company->name }} straight away.
                </p>
            </div>
        </div>

        {{-- ── History ─────────────────────────────────────────────────── --}}
        <div x-show="tab === 'history'" x-cloak class="bg-white rounded-2xl border border-border shadow-xs overflow-hidden">
            <div class="p-5 border-b border-border">
                <h2 class="text-base font-bold text-navy">Everything that changed your balance</h2>
                <p class="text-xs text-text-muted mt-0.5">Newest first. Every credit added and every one used.</p>
            </div>

            @if ($history->isEmpty())
                <p class="p-5 text-sm text-text-muted italic">Nothing yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border bg-slate-50">
                                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">When</th>
                                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">What</th>
                                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary">Reason</th>
                                <th class="px-5 py-3 text-xs font-bold uppercase tracking-wider text-text-secondary text-right">Change</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @foreach ($history as $row)
                                <tr>
                                    <td class="px-5 py-3.5 text-xs text-text-secondary whitespace-nowrap">{{ $row->created_at?->format('d M Y') }}</td>
                                    <td class="px-5 py-3.5 text-xs font-semibold text-navy">{{ $skus[$row->key]['label'] ?? $row->key }}</td>
                                    <td class="px-5 py-3.5 text-xs text-text-muted">{{ $row->note ?: Str::headline($row->source) }}</td>
                                    <td class="px-5 py-3.5 text-right text-xs font-bold {{ $row->delta >= 0 ? 'text-emerald-600' : 'text-slate-600' }}">
                                        {{ $row->delta >= 0 ? '+' : '' }}{{ $row->delta }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-employer-shell>
