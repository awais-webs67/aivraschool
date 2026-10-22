<div class="flex h-full flex-col">
    <div class="flex h-16 items-center px-5">
        <a href="{{ route('school.dashboard') }}"><x-logo dark /></a>
    </div>
    <div class="mx-4 mb-2 flex items-center gap-2.5 rounded border border-white/10 bg-white/5 p-2" title="{{ $school->name }}">
        <x-school-badge :school="$school" size="sm" />
        <span class="truncate text-xs font-medium text-white/85">{{ $school->name }}</span>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Main">
        <a href="{{ route('school.dashboard') }}" class="nav-link" @if (request()->routeIs('school.dashboard')) aria-current="page" @endif>
            <x-icon name="home" /> Dashboard
        </a>
        @if (in_array($user->role, ['admin', 'principal'], true) && $school->aiMonthlyLimit() > 0)
            <a href="{{ route('school.ai.index') }}" class="nav-link" @if (request()->routeIs('school.ai.*')) aria-current="page" @endif>
                <x-icon name="sparkles" /> AI assistant <span class="ml-auto rounded-full bg-brand/90 px-1.5 text-[10px] leading-5 font-bold text-white">AI</span>
            </a>
        @endif
        @if (in_array($user->role, ['admin', 'principal'], true))
            <p class="nav-section">People</p>
            @foreach ([
                ['school.staff.index', 'school.staff.*', 'briefcase', 'Staff'],
            ] as [$route, $active, $icon, $label])
                <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs($active)) aria-current="page" @endif>
                    <x-icon :name="$icon" /> {{ $label }}
                </a>
            @endforeach
            <p class="nav-section">School setup</p>
            @foreach ([
                ['school.setup.profile', 'building', 'School profile'],
                ['school.setup.sessions', 'calendar', 'Academic sessions'],
                ['school.setup.classes', 'layers', 'Classes & sections'],
                ['school.setup.subjects', 'book-open', 'Subjects'],
                ['school.billing', 'credit-card', 'Billing'],
                ['school.support.index', 'chat', 'Help & support'],
            ] as [$route, $icon, $label])
                <a href="{{ route($route) }}" class="nav-link" @if (request()->routeIs(str_replace('.index', '.*', $route))) aria-current="page" @endif>
                    <x-icon :name="$icon" /> {{ $label }}
                </a>
            @endforeach
        @endif
        <p class="nav-section">Coming next</p>
        @foreach (config('skooline.modules') as $key => $module)
            @if ($school->hasModule($key) && $key !== 'ai')
                <span class="nav-link cursor-default opacity-60" title="Coming soon">
                    <x-icon :name="$module['icon']" /> <span class="truncate">{{ $module['label'] }}</span>
                    <span class="ml-auto rounded-sm bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold">Soon</span>
                </span>
            @endif
        @endforeach
    </nav>
    <div class="border-t border-white/10 p-4">
        <form method="POST" action="{{ route('school.logout') }}">
            @csrf
            <button class="nav-link w-full"><x-icon name="logout" /> Sign out</button>
        </form>
        <p class="mt-3 px-3 text-[11px] text-white/40">Skooline by AivraSol</p>
    </div>
</div>
