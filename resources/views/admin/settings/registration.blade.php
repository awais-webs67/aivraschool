<x-layouts.admin heading="Settings" subheading="Let schools sign themselves up from the website.">
    @include('admin.settings.tabs')

    <form method="POST" action="{{ route('admin.settings.registration.update') }}" class="max-w-3xl space-y-6">
        @csrf
        @method('PUT')
        <section class="card">
            <div class="card-header"><h2 class="card-title">"Register your school"</h2></div>
            <div class="card-body space-y-5">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="registration_enabled" value="0">
                    <input type="checkbox" name="registration_enabled" value="1" class="checkbox mt-0.5" @checked(old('registration_enabled', $enabled))>
                    <span><span class="block text-sm font-semibold text-ink">Open registration on the website</span><span class="block text-sm text-muted">Shows "Register your school" in the header, hero and plan buttons. Turn off to accept demo requests only.</span></span>
                </label>
                <label class="flex items-start gap-3">
                    <input type="hidden" name="registration_auto_approve" value="0">
                    <input type="checkbox" name="registration_auto_approve" value="1" class="checkbox mt-0.5" @checked(old('registration_auto_approve', $autoApprove))>
                    <span><span class="block text-sm font-semibold text-ink">Approve automatically</span><span class="block text-sm text-muted">Free trials and online payments go live straight away. Off: every registration waits for you under Registrations.</span></span>
                </label>
                <x-form.input name="registration_notify_email" type="email" label="Email new registrations to" :value="$notifyEmail" placeholder="Leave blank to use the company email" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Prices on the website</h2></div>
            <div class="card-body">
                <x-form.select name="website_default_currency" label="Show prices first in" :value="$defaultCurrency"
                    :options="['auto' => 'Visitor\'s own currency (detected)'] + collect($currencies)->mapWithKeys(fn ($c) => [$c['code'] => $c['code'].' — '.$c['name']])->all()"
                    hint="Visitors can still switch with the currency button. Add currencies (e.g. USD) under the Currencies tab, and set exact prices on each plan." />
            </div>
        </section>

        <div class="flex justify-end"><button class="btn btn-primary">Save settings</button></div>
    </form>
</x-layouts.admin>
