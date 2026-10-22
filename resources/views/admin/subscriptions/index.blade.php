@php
    $methods = collect(\App\Models\Subscription::PAYMENT_METHODS)->mapWithKeys(fn ($m) => [$m => \Illuminate\Support\Str::headline($m)]);
@endphp
<x-layouts.admin heading="Billing & invoices" subheading="Every invoice across all schools. Record payments, print invoices and export.">
    <x-slot:actions>
        <a href="{{ route('admin.subscriptions.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" size="4" /> Export CSV</a>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat label="Invoiced" :value="money($totals['invoiced'])" icon="credit-card" tone="neutral" :hint="$totals['count'].' '.\Illuminate\Support\Str::plural('invoice', $totals['count'])" />
        <x-stat label="Paid" :value="money($totals['paid'])" icon="check-circle" tone="success" />
        <x-stat label="Unpaid" :value="money($totals['unpaid'])" icon="clock" tone="danger" />
    </div>

    <form method="GET" class="card mt-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_auto] lg:items-end">
        <div>
            <label for="q" class="label">Search</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="School, invoice no. or reference">
        </div>
        <x-form.select name="payment_status" label="Payment" :value="$status" placeholder="All" :options="['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partial', 'waived' => 'Waived']" />
        <x-form.input name="from" type="date" label="Period from" :value="$filters['from'] ?? null" />
        <x-form.input name="to" type="date" label="Period to" :value="$filters['to'] ?? null" />
        <div class="flex gap-2">
            <button class="btn btn-dark flex-1"><x-icon name="filter" size="4" /> Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.subscriptions.index') }}" class="btn btn-ghost">Clear</a>@endif
        </div>
    </form>

    <div class="card mt-6">
        @if ($subscriptions->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No invoices found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Invoice</th><th>School</th><th>Period</th><th>Amount</th><th>Payment</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td>
                                    <p class="font-mono text-xs font-semibold text-ink">{{ $subscription->invoice_number }}</p>
                                    <p class="text-xs text-muted">{{ $subscription->plan_name }}</p>
                                </td>
                                <td>
                                    @if ($subscription->school)
                                        <a href="{{ route('admin.schools.show', $subscription->school) }}" class="font-medium text-ink hover:text-brand-text">{{ $subscription->school->name }}</a>
                                    @else
                                        <span class="text-muted">Deleted school</span>
                                    @endif
                                </td>
                                <td class="tabular text-xs">{{ $subscription->starts_at->format('d M Y') }} – {{ $subscription->ends_at?->format('d M Y') ?? 'Lifetime' }}</td>
                                <td class="tabular font-medium text-ink">{{ money($subscription->amount) }}</td>
                                <td>
                                    <span class="badge {{ $subscription->paymentBadge() }}">{{ ucfirst($subscription->payment_status) }}</span>
                                    @if ($subscription->paid_at)<p class="mt-0.5 text-xs text-muted">{{ $subscription->paid_at->format('d M Y') }}{{ $subscription->payment_method ? ' · '.\Illuminate\Support\Str::headline($subscription->payment_method) : '' }}</p>@endif
                                </td>
                                <td>
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.subscriptions.invoice', $subscription) }}" target="_blank" class="btn btn-ghost btn-sm" title="Print invoice"><x-icon name="printer" size="4" /><span class="sr-only">Print invoice</span></a>
                                        <button type="button" class="btn btn-secondary btn-sm" data-toggle="#pay-{{ $subscription->id }}">Update</button>
                                    </div>
                                </td>
                            </tr>
                            <tr id="pay-{{ $subscription->id }}" hidden>
                                <td colspan="6" class="bg-canvas">
                                    <form method="POST" action="{{ route('admin.subscriptions.update', $subscription) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                                        @csrf
                                        @method('PATCH')
                                        <x-form.select name="payment_status" label="Status" :id="'ps-'.$subscription->id" :value="$subscription->payment_status" :options="['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partial', 'waived' => 'Waived']" />
                                        <x-form.select name="payment_method" label="Method" :id="'pm-'.$subscription->id" :value="$subscription->payment_method" placeholder="—" :options="$methods" />
                                        <x-form.input name="payment_reference" label="Reference" :id="'pr-'.$subscription->id" :value="$subscription->payment_reference" />
                                        <x-form.input name="paid_at" type="date" label="Paid on" :id="'pd-'.$subscription->id" :value="$subscription->paid_at?->toDateString()" />
                                        <button class="btn btn-primary">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($subscriptions->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $subscriptions->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
