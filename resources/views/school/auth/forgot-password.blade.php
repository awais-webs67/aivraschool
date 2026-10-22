<x-layouts.auth>
    <x-slot:aside>
        @if ($school->logo_path)
            <x-school-badge :school="$school" size="lg" class="mb-6" />
        @endif
        <p class="eyebrow text-brand">School portal</p>
        <h2 class="font-display mt-3 text-4xl leading-tight xl:text-5xl">{{ $school->name }}</h2>
        <p class="mt-5 text-white/70">Sign in to manage students, attendance, fees, exams and everything else at your school.</p>
    </x-slot:aside>

    <h1 class="font-display text-3xl">Forgot password</h1>
    <p class="mt-2 text-sm text-body">Enter your email and we'll send a link to choose a new password.</p>

    <form method="POST" action="{{ route('school.password.email', $school->slug) }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <button class="btn btn-primary w-full">Send reset link</button>
    </form>
    <p class="mt-4 text-xs text-muted">No email arriving? Your school administrator can also reset your password.</p>
    <a href="{{ route('school.login', $school->slug) }}" class="mt-6 inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-ink"><x-icon name="arrow-left" size="4" /> Back to sign in</a>
</x-layouts.auth>
