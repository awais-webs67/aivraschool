<x-layouts.admin heading="Settings" subheading="Your company details for invoices, and defaults for new schools.">
    @include('admin.settings.tabs')

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Company details</h2>
                    <p class="mt-0.5 text-sm text-muted">Printed on every invoice you send to schools.</p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="company_name" label="Company name" :value="$s['company_name']" required />
                <x-form.input name="company_email" type="email" label="Billing email" :value="$s['company_email']" />
                <x-form.input name="company_phone" label="Phone" :value="$s['company_phone']" />
                <x-form.input name="company_website" label="Website" :value="$s['company_website']" placeholder="https://" />
                <x-form.input name="company_address" label="Address" :value="$s['company_address']" class="sm:col-span-2" />
                <x-form.input name="tax_id" label="Tax / registration number" :value="$s['tax_id']" hint="Shown on invoices if filled." />
                <x-form.input name="invoice_note" label="Invoice note" :value="$s['invoice_note']" hint="e.g. bank account details or payment terms." />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Defaults for new schools</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="trial_days" type="number" min="1" max="365" label="Free trial length (days)" :value="$s['trial_days']" required />
                <x-form.input name="grace_days" type="number" min="0" max="90" label="Grace period after expiry (days)" :value="$s['grace_days']" required hint="Schools keep access this many days after their subscription ends." />
            </div>
        </section>

        <div class="flex justify-end">
            <button class="btn btn-primary">Save settings</button>
        </div>
    </form>
</x-layouts.admin>
