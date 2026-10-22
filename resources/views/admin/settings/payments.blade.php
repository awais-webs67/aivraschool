<x-layouts.admin heading="Settings" subheading="Let schools pay for plans online with Stripe.">
    @include('admin.settings.tabs')

    <div class="grid max-w-5xl gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.settings.payments.update') }}" class="card xl:col-span-2">
            @csrf
            @method('PUT')
            <div class="card-header">
                <div>
                    <h2 class="card-title">Stripe</h2>
                    <p class="mt-0.5 text-sm text-muted">Keys are in Stripe → Developers → API keys. Use test keys (sk_test_) until you are ready.</p>
                </div>
                @if ($secret)<span class="badge {{ str_starts_with($secret, 'sk_live_') ? 'badge-danger' : 'badge-warning' }}">{{ str_starts_with($secret, 'sk_live_') ? 'Live mode' : 'Test mode' }}</span>@endif
            </div>
            <div class="card-body grid gap-5">
                <x-secret-input name="STRIPE_KEY" label="Publishable key" :value="$publishable" placeholder="pk_test_..." />
                <x-secret-input name="STRIPE_SECRET" label="Secret key" :value="$secret" placeholder="sk_test_..." />
                <x-secret-input name="STRIPE_WEBHOOK_SECRET" label="Webhook signing secret (optional)" :value="$webhookSecret" placeholder="whsec_..."
                    hint="Stripe → Developers → Webhooks → add endpoint {{ route('stripe.webhook') }} with event checkout.session.completed. Without it, payments are still confirmed when the school returns from Stripe." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-form.select name="stripe_currency" label="Charge in" :value="$currency" :options="collect(config('currencies'))->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()" hint="Plans use their price in this currency (set it on each plan), or are converted with your website rates." />
                    <label class="flex items-center gap-2 self-center text-sm text-ink">
                        <input type="hidden" name="stripe_enabled" value="0">
                        <input type="checkbox" name="stripe_enabled" value="1" class="checkbox" @checked(old('stripe_enabled', $enabled))> Accept online payments
                    </label>
                </div>
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save payment settings</button></div>
        </form>

        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Check connection</h2></div>
            <form method="POST" action="{{ route('admin.settings.payments.test') }}" class="card-body space-y-3">
                @csrf
                <p class="text-sm text-muted">Asks Stripe to confirm the secret key works. No money moves.</p>
                <button class="btn btn-secondary w-full" @disabled(! $secret)><x-icon name="refresh" size="4" /> Test Stripe</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
