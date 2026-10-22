@props(['title' => null, 'heading' => null, 'subheading' => null])
@php
    $school = current_school();
    $user = auth('school')->user();
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => ($title ?? $heading).' · '.$school->name])
</head>
<body class="min-h-full">
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 bg-steel lg:block">
        @include('school.partials.sidebar', ['school' => $school, 'user' => $user])
    </aside>

    <div id="mobile-nav" data-drawer hidden class="fixed inset-0 z-40 lg:hidden">
        <button type="button" class="absolute inset-0 bg-ink/60" data-toggle="#mobile-nav" aria-label="Close menu"></button>
        <aside class="relative h-full w-72 max-w-[85%] bg-steel shadow-pop">
            @include('school.partials.sidebar', ['school' => $school, 'user' => $user])
        </aside>
    </div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/95 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn-ghost -ml-2 rounded p-2 lg:hidden" data-toggle="#mobile-nav" data-lock-scroll aria-label="Open menu" aria-expanded="false">
                <x-icon name="menu" />
            </button>
            <p class="truncate font-semibold text-ink">{{ $school->name }}</p>
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-medium text-ink">{{ $user->name }}</span>
                    <span class="block text-xs text-muted">{{ $user->roleLabel() }}</span>
                </span>
                <span class="grid size-9 place-items-center rounded-full bg-brand font-semibold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            </div>
        </header>

        @if ($school->inGracePeriod() || ($school->status->value === 'trial' && ($days = $school->daysLeft()) !== null && $days <= 5))
            <div class="border-b border-warning/20 bg-warning-tint px-4 py-2.5 text-sm text-warning sm:px-6">
                <div class="flex items-center gap-2"><x-icon name="clock" size="4" />
                    @if ($school->inGracePeriod())
                        Your subscription has ended. Please renew within {{ config('skooline.grace_days') }} days of the end date to avoid interruption.
                    @else
                        Your free trial ends {{ $days === 0 ? 'today' : 'in '.$days.' '.\Illuminate\Support\Str::plural('day', $days) }}. Contact AivraSol to subscribe.
                    @endif
                </div>
            </div>
        @endif

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if ($heading)
                <div class="mb-6">
                    <h1 class="font-display text-4xl leading-none text-ink sm:text-[2.75rem]">{{ $heading }}</h1>
                    @if ($subheading)<p class="mt-1.5 text-sm text-body">{{ $subheading }}</p>@endif
                </div>
            @endif
            <div class="mb-6 space-y-3 empty:hidden"><x-flash /></div>
            {{ $slot }}
        </main>
    </div>
</body>
</html>
