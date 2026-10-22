@php $r = $registration; @endphp
<x-layouts.marketing title="Registration status" :index="false">
    <section class="px-4 py-14 sm:px-6 lg:px-8">
        <div class="card mx-auto max-w-xl p-6 text-center sm:p-10">
            @if ($r->status === 'approved' && $r->school)
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="8" /></span>
                <h1 class="font-display mt-5 text-3xl text-ink">Your school is ready</h1>
                <p class="mt-2 text-body">{{ $r->school_name }} has its own portal. Sign in with <span class="font-semibold text-ink">{{ $r->principal_email }}</span> and the password you chose.</p>
                <a href="{{ $r->school->url('login') }}" class="btn btn-primary btn-lg mt-6">Open {{ $r->school->displayUrl() }} <x-icon name="arrow-right" size="4" /></a>
            @elseif ($r->status === 'rejected')
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-danger-tint text-danger"><x-icon name="info" size="8" /></span>
                <h1 class="font-display mt-5 text-3xl text-ink">Registration not approved</h1>
                <p class="mt-2 text-body">{{ $r->rejection_reason ?: 'Please contact us for details.' }}</p>
            @elseif ($r->awaitingPayment())
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-warning-tint text-warning"><x-icon name="credit-card" size="8" /></span>
                <h1 class="font-display mt-5 text-3xl text-ink">{{ $cancelled ? 'Payment not completed' : 'Waiting for payment' }}</h1>
                <p class="mt-2 text-body">Your registration is saved. Complete the payment of <span class="font-semibold text-ink">{{ $r->amount_currency }} {{ number_format((float) $r->amount, 2) }}</span> to continue.</p>
                <a href="{{ $retryUrl }}" class="btn btn-primary btn-lg mt-6">Pay now <x-icon name="arrow-right" size="4" /></a>
            @else
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="8" /></span>
                <h1 class="font-display mt-5 text-3xl text-ink">Registration received</h1>
                <p class="mt-2 text-body">
                    @if ($r->isPaid()) Thank you, your payment was received. @endif
                    We will review {{ $r->school_name }} and email <span class="font-semibold text-ink">{{ $r->principal_email }}</span> as soon as your portal is ready.
                </p>
            @endif
            <dl class="mt-8 grid gap-2 rounded-lg bg-canvas p-4 text-left text-sm sm:grid-cols-2">
                <div><dt class="text-muted">Reference</dt><dd class="font-mono font-semibold text-ink">{{ $r->number() }}</dd></div>
                <div><dt class="text-muted">Plan</dt><dd class="font-semibold text-ink">{{ $r->plan?->name ?? '—' }}</dd></div>
            </dl>
            <a href="{{ route('home') }}" class="mt-6 inline-block text-sm font-semibold text-brand-text underline">Back to the website</a>
        </div>
    </section>
</x-layouts.marketing>
