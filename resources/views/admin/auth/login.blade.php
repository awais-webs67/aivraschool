<x-layouts.auth :home-url="route('home')">
    <x-slot:aside>
        <p class="eyebrow text-brand">Platform control</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">Every school.<br>One <span class="text-brand">line</span> of control.</h2>
        <p class="mt-5 text-white/70">Add schools, manage subscriptions and keep every campus running from the Skooline Super Admin panel.</p>
        <ul class="mt-8 space-y-3 text-sm text-white/80">
            @foreach (['Each school\'s data kept fully separate', 'Monthly, yearly, lifetime & per-student plans', 'Suspend, renew or upgrade any school instantly'] as $point)
                <li class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-brand/20 text-brand"><x-icon name="check" size="4" /></span>{{ $point }}</li>
            @endforeach
        </ul>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Super Admin</h1>
    <p class="mt-2 text-sm text-body">Sign in to the Skooline platform panel.</p>

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-body">
            <input type="checkbox" name="remember" class="checkbox"> Keep me signed in
        </label>
        <button class="btn btn-primary w-full">Sign in <x-icon name="arrow-right" size="4" /></button>
    </form>

    <p class="mt-8 flex items-center gap-2 text-xs text-muted"><x-icon name="lock" size="4" /> Restricted area. All access is logged.</p>
</x-layouts.auth>
