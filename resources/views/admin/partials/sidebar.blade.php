<div class="flex h-full flex-col">
    <div class="flex h-16 items-center px-5">
        <a href="{{ route('admin.dashboard') }}"><x-logo dark /></a>
    </div>
    <div class="mx-4 mb-2 rounded border border-white/10 bg-white/5 px-3 py-2 text-[11px] font-semibold tracking-wider text-brand uppercase">Super Admin</div>
    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2" aria-label="Main">
        <p class="nav-section">Platform</p>
        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" class="nav-link" @if (request()->routeIs($item['match'])) aria-current="page" @endif>
                <x-icon :name="$item['icon']" /> {{ $item['label'] }}
            </a>
        @endforeach
        <p class="nav-section">Account</p>
    <a href="{{ route('admin.system') }}" class="nav-link" @if (request()->routeIs('admin.system')) aria-current="page" @endif>
        <x-icon name="cube" /> System
    </a>
        <a href="{{ route('admin.account') }}" class="nav-link" @if (request()->routeIs('admin.account')) aria-current="page" @endif>
            <x-icon name="sliders" /> Account settings
        </a>
        <a href="{{ route('home') }}" class="nav-link" target="_blank" rel="noopener">
            <x-icon name="globe" /> View website <x-icon name="external" size="4" class="ml-auto opacity-50" />
        </a>
    </nav>
    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3">
            <span class="grid size-9 place-items-center rounded-full bg-brand font-semibold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white">{{ $user->name }}</p>
                <p class="truncate text-xs text-white/50">{{ $user->email }}</p>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="rounded p-2 text-white/60 hover:bg-white/10 hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="logout" /></button>
            </form>
        </div>
    </div>
</div>
