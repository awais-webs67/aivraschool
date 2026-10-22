<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => $school->name])
</head>
<body class="grid min-h-full place-items-center px-4 py-10">
    <div class="w-full max-w-md text-center">
        <x-logo tagline class="justify-center" />
        <div class="card mt-8 p-8">
            <span class="mx-auto grid size-14 place-items-center rounded-full {{ $suspended ? 'bg-danger-tint text-danger' : 'bg-warning-tint text-warning' }}">
                <x-icon :name="$suspended ? 'ban' : 'clock'" size="7" />
            </span>
            <h1 class="font-display mt-5 text-3xl text-ink">{{ $suspended ? 'Account suspended' : 'Subscription expired' }}</h1>
            <p class="mt-3 text-sm text-body">
                The Skooline portal for <span class="font-semibold text-ink">{{ $school->name }}</span>
                {{ $suspended ? 'has been temporarily suspended.' : 'is unavailable because its subscription has ended.' }}
                Your data is safe and will be available again once the account is restored.
            </p>
            @unless ($suspended)
                <a href="{{ route('school.renew', $school->slug) }}" class="btn btn-primary mt-6 w-full"><x-icon name="credit-card" size="4" /> Renew subscription</a>
            @endunless
            <a href="mailto:{{ config('skooline.support_email') }}" class="btn {{ $suspended ? 'btn-primary mt-6' : 'btn-secondary mt-3' }} w-full"><x-icon name="mail" size="4" /> Contact AivraSol support</a>
        </div>
    </div>
</body>
</html>
