<x-layouts.school heading="Billing" subheading="Your Skooline plan and invoices from AivraSol.">
    @php $days = $school->daysLeft(); @endphp
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Your plan</h2></div>
            <dl class="card-body space-y-3 text-sm">
                <div><dt class="text-xs text-muted">Plan</dt><dd class="font-semibold text-ink">{{ $school->plan?->name ?? '—' }}</dd></div>
                <div>
                    <dt class="text-xs text-muted">{{ $school->status->value === 'trial' ? 'Free trial ends' : 'Paid until' }}</dt>
                    <dd class="font-semibold text-ink">{{ $school->accessEndsAt()?->format('d M Y') ?? 'Lifetime' }}</dd>
                    @if ($days !== null)<dd class="text-xs {{ $days <= 7 ? 'text-warning' : 'text-muted' }}">{{ $days }} {{ \Illuminate\Support\Str::plural('day', $days) }} left</dd>@endif
                </div>
            </dl>
            <div class="border-t border-line px-5 py-4">
                @if ($canPay)
                    <a href="{{ route('school.renew') }}" class="btn btn-primary w-full"><x-icon name="credit-card" size="4" /> {{ $school->status->value === 'trial' ? 'Subscribe now' : 'Renew now' }}</a>
                @else
                    <p class="text-sm text-muted">To renew, contact {{ $company['company_name'] }}{{ $company['company_email'] ? ' at '.$company['company_email'] : '' }}.</p>
                @endif
            </div>
        </section>

        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Invoices</h2></div>
            @if ($invoices->isEmpty())
                <div class="flex flex-col items-center px-4 py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="credit-card" size="7" /></span>
                    <p class="mt-4 font-semibold text-ink">No invoices yet</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Invoices appear here once you subscribe to a plan.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($invoices as $invoice)
                        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4">
                            <div class="min-w-[11rem] flex-1">
                                <p class="font-mono text-xs font-semibold text-ink">{{ $invoice->invoice_number }}</p>
                                <p class="truncate text-sm text-body">{{ $invoice->plan_name }} · {{ $invoice->starts_at->format('d M Y') }} – {{ $invoice->ends_at?->format('d M Y') ?? 'Lifetime' }}</p>
                            </div>
                            <p class="tabular font-semibold text-ink">{{ money($invoice->amount, true) }}</p>
                            <span class="badge {{ $invoice->paymentBadge() }}">{{ ucfirst($invoice->payment_status) }}</span>
                            <div class="flex gap-1">
                                <a href="{{ $invoice->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="View / print"><x-icon name="printer" size="4" /><span class="sr-only">View invoice</span></a>
                                @if ($invoice->isOpen() && $canPay)
                                    <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('invoice.pay', $invoice) }}" class="btn btn-primary btn-sm">Pay now</a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
                @if ($invoices->hasPages())<div class="border-t border-line px-4 py-3">{{ $invoices->links() }}</div>@endif
            @endif
        </section>
    </div>
</x-layouts.school>
