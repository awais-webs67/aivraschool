<x-layouts.auth :home-url="route('home')">
    <x-slot:aside>
        <p class="eyebrow text-brand">Account security</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">Keep the <span class="text-brand">platform</span> safe.</h2>
        <p class="mt-5 text-white/70">Every school's data sits behind this panel. Use a strong password and turn on two-factor sign-in.</p>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Forgot password</h1>
    <p class="mt-2 text-sm text-body">Enter your email and we'll send a link to choose a new password.</p>

    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <button class="btn btn-primary w-full">Send reset link</button>
    </form>
    <a href="{{ route('admin.login') }}" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink"><x-icon name="arrow-left" size="4" /> Back to sign in</a>
</x-layouts.auth>
