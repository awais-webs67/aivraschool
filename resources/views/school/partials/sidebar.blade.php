<div class="flex h-full flex-col">
    <div class="flex h-16 items-center px-5">
        <a href="{{ route('school.dashboard') }}"><x-logo dark /></a>
    </div>
    <div class="mx-4 mb-2 flex items-center gap-2.5 rounded border border-white/10 bg-white/5 p-2" title="{{ $school->name }}">
        <x-school-badge :school="$school" size="sm" />
        <span class="truncate text-xs font-medium text-white/85">{{ $school->name }}</span>
    </div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Main">
        @foreach (\App\Support\SchoolMenu::for($user, $school) as $section => $items)
            @unless ($loop->first)<p class="nav-section">{{ $section }}</p>@endunless
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" class="nav-link" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" /> <span class="truncate">{{ $item['label'] }}</span>
                    @if ($item['route'] === 'school.ai.index')<span class="ml-auto rounded-full bg-brand/90 px-1.5 text-[10px] leading-5 font-bold text-white">AI</span>@endif
                </a>
            @endforeach
        @endforeach
        @if ($user->allows('setup') && ($soon = \App\Support\SchoolMenu::comingNext($school)))
            <p class="nav-section">Coming next</p>
            @foreach ($soon as $module)
                <span class="nav-link cursor-default opacity-60" title="Coming soon">
                    <x-icon :name="$module['icon']" /> <span class="truncate">{{ $module['label'] }}</span>
                    <span class="ml-auto rounded-sm bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold">Soon</span>
                </span>
            @endforeach
        @endif
    </nav>
    <div class="border-t border-white/10 p-4">
        <form method="POST" action="{{ route('school.logout') }}">
            @csrf
            <button class="nav-link w-full"><x-icon name="logout" /> Sign out</button>
        </form>
        <p class="mt-3 px-3 text-[11px] text-white/40">Skooline by AivraSol</p>
    </div>
</div>
