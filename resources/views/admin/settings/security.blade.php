<x-layouts.admin heading="Settings" subheading="Protect saved API keys and passwords.">
    @include('admin.settings.tabs')

    <form method="POST" action="{{ route('admin.settings.pin') }}" class="card max-w-2xl">
        @csrf
        @method('PUT')
        <div class="card-header">
            <div>
                <h2 class="card-title">Secret-viewing PIN</h2>
                <p class="mt-0.5 text-sm text-muted">Saved keys are always shown masked (sk-a••••••3f9c). The "Show" button asks for this PIN.</p>
            </div>
            <span class="badge {{ $hasPin ? 'badge-success' : 'badge-warning' }}">{{ $hasPin ? 'PIN set' : 'No PIN yet' }}</span>
        </div>
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="pin" type="password" inputmode="numeric" label="{{ $hasPin ? 'New PIN' : 'PIN' }} (4–8 digits)" required autocomplete="new-password" />
            <x-form.input name="pin_confirmation" type="password" inputmode="numeric" label="Repeat PIN" required autocomplete="new-password" />
            <x-form.input name="current_password" type="password" label="Your account password" required autocomplete="current-password" class="sm:col-span-2" />
        </div>
        <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save PIN</button></div>
    </form>
</x-layouts.admin>
