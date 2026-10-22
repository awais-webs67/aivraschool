<x-layouts.admin heading="Edit school" :subheading="$school->name">
    <x-slot:actions>
        <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ route('admin.schools.update', $school) }}" class="card max-w-3xl">
        @csrf
        @method('PUT')
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="name" label="School name" :value="$school->name" required class="sm:col-span-2" />
            <div class="sm:col-span-2">
                <span class="label">Portal address</span>
                <p class="input bg-canvas text-muted">{{ $school->host() }}</p>
                <p class="hint">The portal address can't be changed after creation.</p>
            </div>
            <x-form.input name="principal_name" label="Principal / owner name" :value="$school->principal_name" />
            <x-form.input name="email" type="email" label="School email" :value="$school->email" />
            <x-form.input name="phone" label="Phone" :value="$school->phone" />
            <x-form.input name="city" label="City" :value="$school->city" />
            <x-form.input name="address" label="Address" :value="$school->address" class="sm:col-span-2" />
            <x-form.textarea name="notes" label="Internal notes" :value="$school->notes" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">Save changes</button>
        </div>
    </form>
</x-layouts.admin>
