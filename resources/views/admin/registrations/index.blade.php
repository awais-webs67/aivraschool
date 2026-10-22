<x-layouts.admin heading="Registrations" subheading="Schools that signed up from the website. Approve one to create its portal.">
    <x-slot:actions>
        @can('settings')<a href="{{ route('admin.settings.registration') }}" class="btn btn-secondary"><x-icon name="sliders" size="4" /> Registration settings</a>@endcan
    </x-slot:actions>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('admin.registrations.index') }}" class="btn btn-sm shrink-0 {{ empty($filters['status']) ? 'btn-dark' : 'btn-secondary' }}">All <span class="opacity-60">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\SchoolRegistration::STATUSES as $value => $label)
            <a href="{{ route('admin.registrations.index', ['status' => $value]) }}" class="btn btn-sm shrink-0 {{ ($filters['status'] ?? null) === $value ? 'btn-dark' : 'btn-secondary' }}">{{ $label }} <span class="opacity-60">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 flex max-w-xl gap-2">
        @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Search school, principal, email or city" aria-label="Search registrations">
        <button class="btn btn-dark"><x-icon name="search" size="4" /><span class="sr-only">Search</span></button>
    </form>

    <div class="card">
        @if ($registrations->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="clipboard" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No registrations here</p>
                <p class="mt-1 text-sm text-muted">Schools that use "Register your school" on the website appear here.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($registrations as $r)
                    <li>
                        <a href="{{ route('admin.registrations.show', $r) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-canvas">
                            @if ($r->school_photo_path)
                                <img src="{{ route('admin.registrations.photo', [$r, 'school']) }}" alt="" class="size-11 shrink-0 rounded-lg object-cover ring-1 ring-line" loading="lazy">
                            @else
                                <span class="font-display grid size-11 shrink-0 place-items-center rounded-lg bg-brand-tint text-brand-text">{{ strtoupper(mb_substr($r->school_name, 0, 1)) }}</span>
                            @endif
                            <div class="min-w-[12rem] flex-1">
                                <p class="truncate font-semibold text-ink">{{ $r->school_name }}</p>
                                <p class="truncate text-sm text-muted">{{ $r->principal_name }} · {{ $r->principal_email }}{{ $r->city ? ' · '.$r->city : '' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge badge-neutral">{{ $r->plan?->name ?? 'No plan' }} · {{ \App\Models\SchoolRegistration::CHOICES[$r->billing_choice] ?? $r->billing_choice }}</span>
                                @if ($r->isPaid())<span class="badge badge-success">Paid</span>@endif
                                <span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>
                                <span class="w-24 text-right text-xs text-muted">{{ $r->created_at->format('d M Y') }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($registrations->hasPages())<div class="border-t border-line px-4 py-3">{{ $registrations->links() }}</div>@endif
        @endif
    </div>
</x-layouts.admin>
