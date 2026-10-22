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

    <div id="mobile-nav" class="drawer drawer-left lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer-backdrop" data-drawer-close></div>
        <aside class="drawer-panel w-72 bg-ink">
            <button type="button" class="drawer-close absolute top-3 right-3 z-10 text-white/70 hover:bg-white/10 hover:text-white" data-drawer-close aria-label="Close menu"><x-icon name="x" /></button>
            @include('admin.partials.sidebar', ['nav' => $nav, 'user' => $user])
        </aside>
    </div>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/85 px-4 backdrop-blur-xl sm:px-6">
            <button type="button" class="hamburger -ml-2 shrink-0 lg:hidden" data-drawer-open="#mobile-nav" aria-controls="mobile-nav" aria-expanded="false" aria-label="Menu">
                <span></span><span></span><span></span>
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
                        <h1 class="page-title">{{ $heading }}</h1>
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
