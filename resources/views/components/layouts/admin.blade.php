@props(['title' => null, 'heading' => null, 'subheading' => null])
@php $user = auth('web')->user(); @endphp
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => $title ?? $heading])
</head>
<body class="min-h-full">
    {{-- Sidebar: fixed on desktop, drawer on mobile --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 bg-ink lg:block">
        @include('admin.partials.sidebar', ['user' => $user, 'navBadges' => $navBadges ?? []])
    </aside>

    <div id="mobile-nav" class="drawer drawer-left lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer-backdrop" data-drawer-close></div>
        <aside class="drawer-panel w-72 bg-ink">
            <button type="button" class="drawer-close absolute top-3 right-3 z-10 text-white/70 hover:bg-white/10 hover:text-white" data-drawer-close aria-label="Close menu"><x-icon name="x" /></button>
            @include('admin.partials.sidebar', ['user' => $user, 'navBadges' => $navBadges ?? []])
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
                <div class="relative">
                    <button type="button" class="relative grid size-10 place-items-center rounded-md text-body transition hover:bg-chip hover:text-ink" data-menu="#notifications" aria-haspopup="true" aria-expanded="false" aria-label="Notifications{{ ($notificationTotal ?? 0) ? ' ('.$notificationTotal.')' : '' }}">
                        <x-icon name="bell" />
                        @if (($notificationTotal ?? 0) > 0)
                            <span class="absolute top-1.5 right-1.5 grid min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] leading-4 font-bold text-white ring-2 ring-white">{{ $notificationTotal > 99 ? '99+' : $notificationTotal }}</span>
                        @endif
                    </button>
                    <div id="notifications" hidden class="menu right-0 w-[min(20rem,calc(100vw-2rem))] p-0">
                        <p class="border-b border-line px-4 py-3 text-sm font-semibold text-ink">Needs your attention</p>
                        <ul class="p-1.5">
                            @forelse (collect($notifications ?? [])->where('count', '>', 0) as $n)
                                <li><a href="{{ $n['route'] }}" class="menu-item justify-start"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon :name="$n['icon']" size="4" /></span><span class="min-w-0"><span class="font-semibold">{{ $n['count'] }}</span> {{ $n['label'] }}</span></a></li>
                            @empty
                                <li class="flex items-center gap-3 px-3 py-4 text-sm text-muted"><x-icon name="check-circle" class="text-success" /> You're all caught up.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                @can('schools.manage')
                    <a href="{{ route('admin.schools.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" size="4" /> <span class="hidden sm:inline">Add school</span></a>
                @endcan
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
                <x-credentials />
            </div>

            {{ $slot }}
        </main>
    </div>
</body>
</html>
