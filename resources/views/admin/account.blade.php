<x-layouts.admin heading="Account settings" subheading="Manage your Super Admin sign-in.">
    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <section class="card">
            <div class="card-header"><h2 class="card-title">Profile</h2></div>
            <dl class="card-body space-y-3 text-sm">
                <div><dt class="text-xs text-muted">Name</dt><dd class="text-ink">{{ auth()->user()->name }}</dd></div>
                <div><dt class="text-xs text-muted">Email</dt><dd class="text-ink">{{ auth()->user()->email }}</dd></div>
                <div><dt class="text-xs text-muted">Role</dt><dd><span class="badge badge-brand">{{ \App\Models\User::ROLES[auth()->user()->role] ?? auth()->user()->role }}</span></dd></div>
                <div><dt class="text-xs text-muted">Last sign-in</dt><dd class="text-ink">{{ auth()->user()->last_login_at?->format('d M Y, h:i A') ?? '—' }}</dd></div>
            </dl>
        </section>
        <form method="POST" action="{{ route('admin.account.password') }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-header"><h2 class="card-title">Change password</h2></div>
            <div class="card-body space-y-4">
                <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 10 characters with letters and numbers." />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Update password</button></div>
        </form>
    </div>
</x-layouts.admin>
