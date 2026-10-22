<x-layouts.auth>
    <x-slot:aside>
        <p class="eyebrow text-brand">School portal</p>
        <h2 class="font-display mt-3 text-6xl leading-[0.95]">{{ $school->name }}</h2>
        <p class="mt-5 text-white/70">Sign in to manage students, attendance, fees, exams and everything else at your school.</p>
    </x-slot:aside>

    <div class="mb-6 flex items-center gap-3 lg:hidden">
        <span class="font-display grid size-11 place-items-center rounded-md bg-ink text-2xl text-white">{{ strtoupper(substr($school->name, 0, 1)) }}</span>
        <p class="font-semibold text-ink">{{ $school->name }}</p>
    </div>

    <h1 class="font-display text-5xl leading-none">Welcome back</h1>
    <p class="mt-2 text-sm text-body">Sign in to your school account.</p>

    <form method="POST" action="{{ route('school.login.store') }}" class="mt-8 space-y-5">
        @csrf
        <x-form.input name="email" type="email" label="Email address" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="Password" required autocomplete="current-password" />
        <label class="flex items-center gap-2 text-sm text-body">
            <input type="checkbox" name="remember" class="checkbox"> Keep me signed in
        </label>
        <button class="btn btn-primary w-full">Sign in <x-icon name="arrow-right" size="4" /></button>
    </form>

    <p class="mt-8 text-xs text-muted">Forgot your password? Ask your school administrator to reset it.</p>
</x-layouts.auth>
