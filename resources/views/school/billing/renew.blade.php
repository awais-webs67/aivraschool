<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => 'Renew · '.$school->name])
    <meta name="robots" content="noindex">
</head>
<body class="min-h-full bg-canvas px-4 py-10">
    <main class="mx-auto w-full max-w-xl">
        <div class="flex items-center justify-between gap-3">
            <x-logo />
            @auth('school')<a href="{{ route('school.billing') }}" class="text-sm font-medium text-muted hover:text-ink">← Billing</a>@endauth
        </div>

        <div class="mt-6"><x-flash /></div>
        <div class="card mt-4">
            <div class="card-header">
                <div class="flex min-w-0 items-center gap-3">
                    <x-school-badge :school="$school" />
                    <div class="min-w-0">
                        <h1 class="card-title break-words">Renew {{ $school->name }}</h1>
                        <p class="text-xs text-muted">New period starts {{ $starts->format('d M Y') }}</p>
                    </div>
                </div>
            </div>

            @if (! $plan)
                <p class="card-body text-sm text-muted">No plans are available to renew online. Contact {{ $company['company_email'] ?? 'us' }}.</p>
            @elseif (! $canPay)
                <p class="card-body text-sm text-body">Online payment is not set up. To renew, contact {{ $company['company_name'] }}{{ $company['company_email'] ? ' at '.$company['company_email'] : '' }}{{ $company['company_phone'] ? ' or '.$company['company_phone'] : '' }}.</p>
            @else
                <form method="POST" action="{{ route('school.renew.checkout') }}" class="card-body space-y-5">
                    @csrf
                    <fieldset class="space-y-2">
                        <legend class="label">Plan</legend>
                        @foreach ($plans as $p)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-line p-3 has-[:checked]:border-brand has-[:checked]:bg-brand-tint/40">
                                <input type="radio" name="plan_id" value="{{ $p->id }}" class="size-4 shrink-0 accent-brand" @checked((int) old('plan_id', $plan->id) === $p->id)>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold text-ink">{{ $p->name }}{{ $p->id === $school->plan_id ? ' (current)' : '' }}</span>
                                    <span class="block text-xs text-muted">{{ $p->billingLabel() }}</span>
                                </span>
                                <span class="tabular shrink-0 font-semibold text-ink">{{ money($p->priceFor((int) $school->student_count), true) }}</span>
                            </label>
                        @endforeach
                        @error('plan_id')<p class="error">{{ $message }}</p>@enderror
                    </fieldset>
                    <x-form.input name="coupon_code" label="Discount code (optional)" maxlength="40" autocomplete="off" />
                    @if ($bill && $bill['tax_rate'] > 0)
                        <p class="text-xs text-muted">{{ $bill['tax_name'] }} {{ rtrim(rtrim(number_format($bill['tax_rate'], 2), '0'), '.') }}% is added at checkout.</p>
                    @endif
                    <button class="btn btn-primary btn-lg w-full"><x-icon name="credit-card" size="4" /> Continue to secure payment</button>
                    <p class="text-center text-xs text-muted">Card payment by Stripe. Your plan updates as soon as the payment goes through.</p>
                </form>
            @endif
        </div>
    </main>
</body>
</html>
