<x-layouts.auth :home-url="route('home')">
    <x-slot:aside>
        <p class="eyebrow text-brand">First-time setup</p>
        <h2 class="font-display mt-3 text-6xl leading-[0.95]">Welcome to <span class="text-brand">Skooline</span>.</h2>
        <p class="mt-5 text-white/70">The database is ready. Create the Super Admin account that will manage schools, plans and subscriptions.</p>
    </x-slot:aside>

    <h1 class="font-display text-5xl leading-none">Create Super Admin</h1>
    <p class="mt-2 text-sm text-body">This page works only once, while no Super Admin exists.</p>

    <form method="POST" action="{{ route('admin.setup.store') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="name" label="Full name" required autofocus autocomplete="name" />
        <x-form.input name="email" type="email" label="Email address" required autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 10 characters with letters and numbers." />
        <x-form.input name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" />
        <button class="btn btn-primary w-full">Create account and sign in <x-icon name="arrow-right" size="4" /></button>
    </form>
</x-layouts.auth>
