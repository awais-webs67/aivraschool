<x-layouts.school heading="School profile" subheading="Your school's name, logo and contact details. They appear on the portal, and later on vouchers and report cards.">
    <form method="POST" action="{{ route('school.setup.profile.update') }}" enctype="multipart/form-data" class="grid max-w-5xl gap-6 lg:grid-cols-3">
        @csrf
        @method('PUT')

        <section class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">Details</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="School name" :value="$school->name" required class="sm:col-span-2" />
                <x-form.input name="principal_name" label="Principal name" :value="$school->principal_name" />
                <x-form.input name="phone" label="Phone" :value="$school->phone" />
                <x-form.input name="email" type="email" label="Email" :value="$school->email" />
                <x-form.input name="city" label="City" :value="$school->city" />
                <x-form.input name="country" label="Country" :value="$school->country" />
                <x-form.select name="currency" label="Fee currency" required :value="$school->currencyCode()" :options="collect(config('currencies'))->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()" hint="Used for fees, invoices and receipts." />
                <x-form.input name="address" label="Address" :value="$school->address" class="sm:col-span-2" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Logo</h2></div>
            <div class="card-body space-y-4">
                <div class="grid place-items-center rounded-md border border-dashed border-line-strong bg-canvas p-6">
                    <x-school-badge :school="$school" size="lg" class="size-24! text-5xl!" />
                </div>
                <div>
                    <label for="logo" class="label">Upload logo</label>
                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="input file:mr-3 file:rounded file:border-0 file:bg-chip file:px-3 file:py-1 file:text-sm file:font-medium">
                    @error('logo')<p class="error">{{ $message }}</p>@else<p class="hint">PNG, JPG or WebP, up to 1 MB. A square logo looks best.</p>@enderror
                </div>
                @if ($school->logo_path)
                    <label class="flex items-center gap-2 text-sm text-body">
                        <input type="checkbox" name="remove_logo" value="1" class="checkbox"> Remove current logo
                    </label>
                @endif
            </div>
        </section>

        <div class="flex items-center justify-between gap-3 lg:col-span-3">
            <p class="text-sm text-muted">Portal address: <span class="font-medium text-ink">{{ $school->displayUrl() }}</span></p>
            <button class="btn btn-primary">Save profile</button>
        </div>
    </form>
</x-layouts.school>
