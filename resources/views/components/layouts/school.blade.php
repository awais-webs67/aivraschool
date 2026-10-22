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
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 bg-ink lg:block">
        @include('school.partials.sidebar', ['school' => $school, 'user' => $user])
    </aside>

    <div id="mobile-nav" class="drawer drawer-left lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer-backdrop" data-drawer-close></div>
        <aside class="drawer-panel w-72 bg-ink">
            <button type="button" class="drawer-close absolute top-3 right-3 z-10 text-white/70 hover:bg-white/10 hover:text-white" data-drawer-close aria-label="Close menu"><x-icon name="x" /></button>
            @include('school.partials.sidebar', ['school' => $school, 'user' => $user])
        </aside>
    </div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur-xl sm:px-6">
            <button type="button" class="hamburger -ml-2 shrink-0 lg:hidden" data-drawer-open="#mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <x-school-badge :school="$school" size="sm" class="hidden sm:grid lg:hidden" />
            <p class="truncate font-semibold text-ink">{{ $school->name }}</p>
            @if ($session = \App\Models\AcademicSession::current())
                <span class="badge badge-neutral hidden sm:inline-flex">Session {{ $session->name }}</span>
            @endif
            <div class="ml-auto flex items-center gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-medium text-ink">{{ $user->name }}</span>
                    <span class="block text-xs text-muted">{{ $user->roleLabel() }}</span>
                </span>
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-light to-brand font-semibold text-white shadow-brand">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
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
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="page-title">{{ $heading }}</h1>
                        @if ($subheading)<p class="mt-1.5 text-sm text-body">{{ $subheading }}</p>@endif
                    </div>
                    @isset($actions)
                        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                    @endisset
                </div>
            @endif
            <div class="mb-6 space-y-3 empty:hidden"><x-flash /></div>
            {{ $slot }}
        </main>
    </div>
</body>
</html>
