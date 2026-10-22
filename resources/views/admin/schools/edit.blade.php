<x-layouts.admin heading="Edit school" :subheading="$school->name">
    <x-slot:actions>
        <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('admin.schools.update', $school) }}" enctype="multipart/form-data" class="card max-w-3xl">
        @csrf
        @method('PUT')
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="School name" :value="$school->name" required class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <span class="label">Portal address</span>
                <p class="input bg-canvas text-muted">{{ $school->displayUrl() }}</p>
                <p class="hint">The portal address can't be changed after creation.</p>
            </div>
            <x-form.input name="email" type="email" label="School email" :value="$school->email" />
            <x-form.input name="phone" label="Phone" :value="$school->phone" />
            <x-form.input name="city" label="City" :value="$school->city" />
            <x-form.input name="country" label="Country" :value="$school->country" />
            <x-form.select name="currency" label="Fee currency" :value="$school->currencyCode()" :options="collect(config('currencies'))->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()" />
            <x-form.input name="address" label="Address" :value="$school->address" class="sm:col-span-2" />
            <x-form.input name="student_count" type="number" min="0" label="Students" :value="$school->student_count" />
            <x-photo-input name="school_photo" label="School photo" hint="JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2"
                :current="$school->photo_path ? route('admin.schools.photo', [$school, 'school']) : null" />
            <h2 class="border-t border-line pt-5 text-base font-semibold text-ink sm:col-span-2">Principal</h2>
            @include('admin.schools.partials.principal-fields', ['school' => $school])
            <x-form.textarea name="notes" label="Internal notes" :value="$school->notes" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-layouts.admin>
