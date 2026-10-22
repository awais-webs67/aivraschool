@php
    $groups = [
        'Overview' => [
            ['admin.dashboard', 'admin.dashboard', 'home', 'Dashboard', null],
            ['admin.reports', 'admin.reports', 'chart', 'Reports', 'reports'],
        ],
        'Schools' => [
            ['admin.schools.index', 'admin.schools.*', 'building', 'Schools', 'schools.view'],
            ['admin.registrations.index', 'admin.registrations.*', 'clipboard', 'Registrations', 'schools.manage'],
            ['admin.subscriptions.index', 'admin.subscriptions.*', 'credit-card', 'Billing & invoices', 'billing'],
            ['admin.plans.index', 'admin.plans.*', 'layers', 'Plans & pricing', 'plans'],
        ],
        'Customers' => [
            ['admin.demo-requests.index', 'admin.demo-requests.*', 'inbox', 'Leads', 'leads'],
            ['admin.tickets.index', 'admin.tickets.*', 'chat', 'Support tickets', 'tickets'],
            ['admin.announcements.index', 'admin.announcements.*', 'bolt', 'Announcements', 'announcements'],
        ],
        'Administration' => [
            ['admin.team.index', 'admin.team.*', 'users', 'Team', 'team'],
            ['admin.activity', 'admin.activity', 'clock', 'Activity log', 'activity'],
            ['admin.ai.index', 'admin.ai.*', 'sparkles', 'AI setup', 'settings'],
            ['admin.settings', 'admin.settings*', 'sliders', 'Settings', 'settings'],
            ['admin.system', 'admin.system', 'cube', 'System', 'system'],
        ],
    ];
    $badgeFor = ['admin.demo-requests.index' => 'leads', 'admin.tickets.index' => 'tickets', 'admin.registrations.index' => 'registrations'];
@endphp
<div class="flex h-full flex-col">
    <div class="flex h-16 shrink-0 items-center px-5">
        <a href="{{ route('admin.dashboard') }}"><x-logo dark /></a>
    </div>
    <div class="mx-4 mb-1 flex items-center gap-2 rounded-md border border-white/10 bg-white/5 px-3 py-2">
        <span class="size-2 shrink-0 rounded-full bg-brand"></span>
        <span class="truncate text-[11px] font-semibold tracking-wider text-white/80 uppercase">{{ $user->roleLabel() }}</span>
    </div>
    <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 pb-4" aria-label="Main">
        @foreach ($groups as $group => $items)
            @php $visible = collect($items)->filter(fn ($i) => $i[4] === null || $user->can($i[4])); @endphp
            @if ($visible->isNotEmpty())
                <p class="nav-section">{{ $group }}</p>
                @foreach ($visible as [$route, $match, $icon, $label])
                    @php $badge = isset($badgeFor[$route]) ? ($navBadges[$badgeFor[$route]] ?? 0) : 0; @endphp
                    <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($match)) aria-current="page" @endif>
                        <x-icon :name="$icon" class="shrink-0" /> <span class="truncate">{{ $label }}</span>
                        @if ($badge > 0)<span class="ml-auto rounded-full bg-brand px-1.5 text-[11px] leading-5 font-bold text-white">{{ $badge > 99 ? '99+' : $badge }}</span>@endif
                    </a>
                @endforeach
            @endif
        @endforeach
        <p class="nav-section">You</p>
        <a href="{{ route('admin.account') }}" class="nav-link" @if (request()->routeIs('admin.account')) aria-current="page" @endif>
            <x-icon name="user-circle" class="shrink-0" /> <span class="truncate">My account</span>
        </a>
        <a href="{{ route('home') }}" class="nav-link" target="_blank" rel="noopener">
            <x-icon name="globe" class="shrink-0" /> <span class="truncate">View website</span> <x-icon name="external" size="4" class="ml-auto shrink-0 opacity-50" />
        </a>
    </nav>
    <div class="shrink-0 border-t border-white/10 p-4">
        <div class="flex items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-light to-brand font-semibold text-white">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-white/50">{{ $user->email }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="rounded-md p-2 text-white/60 transition hover:bg-white/10 hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="logout" /></button>
            </form>
        </div>
    </div>
</div>
