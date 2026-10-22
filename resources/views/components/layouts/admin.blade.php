@props(['title' => null, 'heading' => null, 'subheading' => null])
@php
    $nav = [
        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
        ['route' => 'admin.schools.index', 'match' => 'admin.schools.*', 'icon' => 'building', 'label' => 'Schools'],
        ['route' => 'admin.subscriptions.index', 'match' => 'admin.subscriptions.*', 'icon' => 'credit-card', 'label' => 'Subscriptions'],
        ['route' => 'admin.plans.index', 'match' => 'admin.plans.*', 'icon' => 'layers', 'label' => 'Plans & Pricing'],
        ['route' => 'admin.demo-requests.index', 'match' => 'admin.demo-requests.*', 'icon' => 'inbox', 'label' => 'Demo Requests'],
    ];
    $user = auth('web')->user();
@endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => $title ?? $heading])
</head>
<body class="min-h-full">
    {{-- Sidebar: fixed on desktop, drawer on mobile --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 bg-ink lg:block">
        @include('admin.partials.sidebar', ['nav' => $nav, 'user' => $user])
    </aside>

    <div id="mobile-nav" data-drawer hidden class="fixed inset-0 z-40 lg:hidden">
        <button type="button" class="absolute inset-0 bg-ink/60" data-toggle="#mobile-nav" aria-label="Close menu"></button>
        <aside class="relative h-full w-72 max-w-[85%] bg-ink shadow-pop">
            @include('admin.partials.sidebar', ['nav' => $nav, 'user' => $user])
        </aside>
    </div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/95 px-4 backdrop-blur sm:px-6">
            <button type="button" class="btn-ghost -ml-2 rounded p-2 lg:hidden" data-toggle="#mobile-nav" data-lock-scroll aria-label="Open menu" aria-expanded="false">
                <x-icon name="menu" />
            </button>
            <form action="{{ route('admin.schools.index') }}" method="GET" class="relative hidden max-w-md flex-1 sm:block" role="search">
                <x-icon name="search" size="4" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search schools by name, address or city" class="input py-2 pl-9" aria-label="Search schools">
            </form>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('admin.schools.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="4" /> <span>Add school</span></a>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            @if ($heading)
                <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="font-display text-4xl leading-none text-ink sm:text-[2.75rem]">{{ $heading }}</h1>
                        @if ($subheading)
                            <p class="mt-1.5 text-sm text-body">{{ $subheading }}</p>
                        @endif
                    </div>
                    @isset($actions)
                        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
                    @endisset
                </div>
            @endif

            <div class="mb-6 space-y-3 empty:hidden">
                <x-flash />
            </div>

            {{ $slot }}
        </main>
    </div>
</body>
</html>
