<x-layouts.auth :home-url="route('home')">
    <x-slot:aside>
        <p class="eyebrow text-brand">Account security</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">Keep the <span class="text-brand">platform</span> safe.</h2>
        <p class="mt-5 text-white/70">Every school's data sits behind this panel. Use a strong password and turn on two-factor sign-in.</p>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Choose a new password</h1>
    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" :value="$email" required autocomplete="username" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 10 characters with letters and numbers." />
        <x-form.input name="password_confirmation" type="password" label="Repeat new password" required autocomplete="new-password" />
        <button class="btn btn-primary w-full">Save new password</button>
    </form>
</x-layouts.auth>
