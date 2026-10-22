<x-layouts.auth :home-url="route('home')">
    <x-slot:aside>
        <p class="eyebrow text-brand">Account security</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">Keep the <span class="text-brand">platform</span> safe.</h2>
        <p class="mt-5 text-white/70">Every school's data sits behind this panel. Use a strong password and turn on two-factor sign-in.</p>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Two-factor sign-in</h1>
    <p class="mt-2 text-sm text-body">Open your authenticator app and enter the 6-digit code for Skooline. Lost your phone? Enter one of your recovery codes.</p>
    <form method="POST" action="{{ route('admin.two-factor.store') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="code" label="Code" required autofocus autocomplete="one-time-code" inputmode="numeric" placeholder="123456" />
        <button class="btn btn-primary w-full">Verify and sign in</button>
    </form>
    <a href="{{ route('admin.login') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink"><x-icon name="arrow-left" size="4" /> Start again</a>
</x-layouts.auth>
