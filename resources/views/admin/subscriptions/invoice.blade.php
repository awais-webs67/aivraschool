@php
    $school = $subscription->school;
    $public ??= false;
    $payUrl ??= null;
    $subtotal = $subscription->subtotal ?? $subscription->amount;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Invoice '.$subscription->invoice_number])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .sheet { box-shadow: none !important; border: 0 !important; margin: 0 !important; } @page { margin: 14mm; } }</style>
</head>
<body class="bg-canvas py-6 sm:py-10">
    <div class="no-print mx-auto mb-4 flex max-w-3xl flex-wrap items-center justify-between gap-2 px-4">
        @if ($public)
            <x-logo />
        @else
            <a href="{{ url()->previous() }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
        @endif
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="btn {{ $payUrl ? 'btn-secondary' : 'btn-primary' }}"><x-icon name="printer" size="4" /> Print / Save as PDF</button>
            @if ($payUrl)<a href="{{ $payUrl }}" class="btn btn-primary"><x-icon name="credit-card" size="4" /> Pay {{ money($subscription->amount, true) }} by card</a>@endif
        </div>
    </div>
    <div class="no-print mx-auto max-w-3xl px-4"><x-flash /></div>

    <main class="sheet relative mx-auto max-w-3xl overflow-hidden rounded-lg border border-line bg-white p-6 shadow-card sm:p-10">
        <header class="flex flex-wrap items-start justify-between gap-6 border-b border-line pb-6">
            <div>
                <x-logo tagline />
                <div class="mt-4 text-sm text-body">
                    <p class="font-semibold text-ink">{{ $company['company_name'] }}</p>
                    @if ($company['company_address'])<p>{{ $company['company_address'] }}</p>@endif
                    <p>{{ collect([$company['company_email'], $company['company_phone']])->filter()->join(' · ') }}</p>
                    @if ($company['tax_id'])<p>Tax ID: {{ $company['tax_id'] }}</p>@endif
                </div>
            </div>
            <div class="text-left sm:text-right">
                <h1 class="font-display text-3xl text-ink">Invoice</h1>
                <p class="mt-1 font-mono text-sm font-semibold text-ink">{{ $subscription->invoice_number }}</p>
                <p class="mt-2 text-sm text-muted">Issued {{ $subscription->created_at->format('d M Y') }}</p>
                <p class="mt-1"><span class="badge {{ $subscription->paymentBadge() }}">{{ ucfirst($subscription->payment_status) }}</span></p>
            </div>
        </header>

        <section class="grid gap-6 py-6 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold tracking-wide text-muted uppercase">Billed to</p>
                <p class="mt-2 font-semibold text-ink">{{ $school?->name ?? 'Deleted school' }}</p>
                @if ($school)
                    <div class="text-sm text-body">
                        @if ($school->principal_name)<p>{{ $school->principal_name }}</p>@endif
                        @if ($school->address)<p>{{ $school->address }}</p>@endif
                        <p>{{ collect([$school->city, $school->country])->filter()->join(', ') }}</p>
                        <p>{{ collect([$school->email, $school->phone])->filter()->join(' · ') }}</p>
                    </div>
                @endif
            </div>
            <div class="sm:text-right">
                <p class="text-xs font-semibold tracking-wide text-muted uppercase">Service period</p>
                <p class="mt-2 text-sm text-ink">{{ $subscription->starts_at->format('d M Y') }} – {{ $subscription->ends_at?->format('d M Y') ?? 'Lifetime' }}</p>
                @if ($subscription->paid_at)
                    <p class="mt-3 text-xs font-semibold tracking-wide text-muted uppercase">Paid on</p>
                    <p class="mt-1 text-sm text-ink">{{ $subscription->paid_at->format('d M Y') }}{{ $subscription->payment_method ? ' · '.\Illuminate\Support\Str::headline($subscription->payment_method) : '' }}</p>
                    @if ($subscription->payment_reference)<p class="text-xs text-muted">Ref: {{ $subscription->payment_reference }}</p>@endif
                @endif
            </div>
        </section>

        <div class="relative overflow-x-auto">
            @if (in_array($subscription->payment_status, ['paid', 'waived'], true))
                <span aria-hidden="true" class="font-display pointer-events-none absolute bottom-2 left-3 -rotate-6 rounded-md border-[3px] border-success/70 px-4 py-1 text-2xl tracking-widest text-success/80 uppercase">{{ $subscription->payment_status }}</span>
            @endif
            <table class="w-full min-w-[480px] text-sm">
                <thead>
                    <tr class="border-y border-line bg-canvas text-left text-xs tracking-wide text-muted uppercase">
                        <th class="px-3 py-2.5 font-semibold">Description</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Qty</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-line">
                        <td class="px-3 py-4">
                            <p class="font-medium text-ink">Skooline {{ $subscription->plan_name }} plan</p>
                            <p class="text-xs text-muted">{{ config('skooline.billing_models.'.$subscription->billing_model.'.label', $subscription->billing_model) }} subscription{{ $subscription->student_count ? ' · '.$subscription->student_count.' students' : '' }}</p>
                        </td>
                        <td class="tabular px-3 py-4 text-right">1</td>
                        <td class="tabular px-3 py-4 text-right font-medium text-ink">{{ money($subtotal, true) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    @if ((float) $subscription->discount > 0 || (float) $subscription->tax_amount > 0)
                        <tr><td colspan="2" class="px-3 pt-4 text-right text-muted">Subtotal</td><td class="tabular px-3 pt-4 text-right text-ink">{{ money($subtotal, true) }}</td></tr>
                    @endif
                    @if ((float) $subscription->discount > 0)
                        <tr><td colspan="2" class="px-3 pt-1 text-right text-muted">Discount{{ $subscription->coupon_code ? ' ('.$subscription->coupon_code.')' : '' }}</td><td class="tabular px-3 pt-1 text-right text-success">−{{ money($subscription->discount, true) }}</td></tr>
                    @endif
                    @if ((float) $subscription->tax_amount > 0)
                        <tr><td colspan="2" class="px-3 pt-1 text-right text-muted">{{ $subscription->tax_name ?: 'Tax' }} ({{ rtrim(rtrim(number_format((float) $subscription->tax_rate, 2), '0'), '.') }}%)</td><td class="tabular px-3 pt-1 text-right text-ink">{{ money($subscription->tax_amount, true) }}</td></tr>
                    @endif
                    <tr><td colspan="2" class="px-3 pt-4 text-right font-semibold text-ink">Total</td><td class="tabular font-display px-3 pt-4 text-right text-xl text-ink">{{ money($subscription->amount, true) }}</td></tr>
                    <tr><td colspan="2" class="px-3 pt-1 text-right text-muted">Amount due</td><td class="tabular px-3 pt-1 text-right font-semibold {{ $subscription->payment_status === 'unpaid' ? 'text-danger' : 'text-success' }}">{{ money(in_array($subscription->payment_status, ['paid', 'waived'], true) ? 0 : $subscription->amount, true) }}</td></tr>
                </tfoot>
            </table>
        </div>

        @if ($company['invoice_note'] || $subscription->notes)
            <div class="mt-8 rounded-md bg-canvas p-4 text-sm text-body">
                @if ($company['invoice_note'])<p class="whitespace-pre-line">{{ $company['invoice_note'] }}</p>@endif
                @if ($subscription->notes)<p class="mt-2 whitespace-pre-line">{{ $subscription->notes }}</p>@endif
            </div>
        @endif

        <p class="mt-10 border-t border-line pt-4 text-center text-xs text-muted">Thank you for choosing Skooline. {{ $company['company_website'] ? preg_replace('#^https?://#', '', $company['company_website']) : '' }}</p>
    </main>
</body>
</html>
