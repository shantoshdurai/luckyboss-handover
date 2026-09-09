{{--
    What this conversation will spend, in one quiet line.

    Sir asked for the remaining allowance to be visible "smally" while the agent
    is running, and small is the whole design: an employer needs to know a
    shortlist costs them nothing until they unlock somebody, and roughly how many
    of those they have left. A panel would turn a conversation into a checkout.

    Two rules, both from `EntitlementCatalogue`:

      - **Unlimited prints nothing at all.** A row saying "Unlimited" is billing
        furniture around something free, and the catalogue is explicit that a
        zero-priced SKU stays invisible. That is why the candidate side of this
        renders nothing today — every seeker action is unlimited — and why it
        will start appearing on its own the day one of them is priced.
      - **The numbers are real balances**, plan allowance plus this month's free
        tier, read from the ledger. Nothing here is a constant.
--}}
@php
    $usageRows = collect($rows ?? [])
        ->filter(fn ($row) => ! $row['unlimited'] && $row['remaining'] !== null)
        ->take(3);
@endphp

@if ($usageRows->isNotEmpty())
    <p class="text-xs" style="color:var(--color-text-muted);">
        @foreach ($usageRows as $row)
            <span class="whitespace-nowrap">
                <span class="font-bold" style="color:#031F49;">{{ $row['remaining'] }}</span>
                {{ Str::plural($row['unit'], $row['remaining']) }} left
            </span>@if(! $loop->last)<span style="opacity:.5;"> &middot; </span>@endif
        @endforeach
        <span style="opacity:.5;"> &middot; </span>
        <a href="{{ $manageUrl }}" class="font-bold hover:underline" style="color:#2563EB;">Subscription</a>
    </p>
@endif
