<x-layouts.auth>
    <x-slot:aside>
        @if ($school->logo_path)
            <x-school-badge :school="$school" size="lg" class="mb-6" />
        @endif
        <p class="eyebrow text-brand">School portal</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">{{ $school->name }}</h2>
        <p class="mt-5 text-white/70">Sign in to manage students, attendance, fees, exams and everything else at your school.</p>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Choose a new password</h1>
    <form method="POST" action="{{ route('school.password.update', $school->slug) }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Email address" :value="$email" required autocomplete="username" />
        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" hint="At least 8 characters with letters and numbers." />
        <x-form.input name="password_confirmation" type="password" label="Repeat new password" required autocomplete="new-password" />
        <button class="btn btn-primary w-full">Save new password</button>
    </form>
</x-layouts.auth>
