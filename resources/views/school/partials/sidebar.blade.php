<div class="flex h-full flex-col">
    <div class="flex h-16 items-center px-5">
        <a href="{{ route('school.dashboard') }}"><x-logo dark /></a>
    </div>
    <div class="mx-4 mb-2 truncate rounded border border-white/10 bg-white/5 px-3 py-2 text-xs font-medium text-white/80" title="{{ $school->name }}">{{ $school->name }}</div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Main">
        <a href="{{ route('school.dashboard') }}" class="nav-link" @if (request()->routeIs('school.dashboard')) aria-current="page" @endif>
            <x-icon name="home" /> Dashboard
        </a>
        <p class="nav-section">Modules</p>
        @foreach (config('skooline.modules') as $key => $module)
            @if ($school->hasModule($key))
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
