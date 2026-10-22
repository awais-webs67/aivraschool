<x-layouts.school heading="Staff" subheading="Everyone who works at your school, and who can sign in to the portal.">
    <x-slot:actions>
        <a href="{{ route('school.staff.class-teachers') }}" class="btn btn-secondary"><x-icon name="layers" size="4" /> Class teachers</a>
        <a href="{{ route('school.staff.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> Add staff</a>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Active staff" :value="number_format($counts['active'])" icon="briefcase" tone="brand" />
        <x-stat label="Teaching" :value="number_format($counts['teaching'])" icon="book-open" tone="info" />
        <x-stat label="With portal login" :value="number_format($counts['logins'])" icon="lock" tone="success" />
        <x-stat label="Left" :value="number_format($counts['left'])" icon="logout" tone="neutral" />
    </div>

    <form method="GET" class="card mt-6 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto_auto]">
        <label for="q" class="sr-only">Search</label>
        <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, employee no., phone or designation" class="input">
        <select name="department" class="input sm:w-44" aria-label="Department">
            <option value="">All departments</option>
            @foreach ($departments as $d)<option value="{{ $d }}" @selected(($filters['department'] ?? '') === $d)>{{ $d }}</option>@endforeach
        </select>
        <select name="status" class="input sm:w-36" aria-label="Status">
            @foreach (['active' => 'Active', 'left' => 'Left', 'all' => 'All'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? 'active') === $v)>{{ $l }}</option>@endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-secondary flex-1">Filter</button>
            <a data-no-progress href="{{ route('school.staff.export', request()->query()) }}" class="btn btn-ghost" title="Export CSV"><x-icon name="download" size="4" /><span class="sr-only">Export CSV</span></a>
        </div>
    </form>

    <div class="card mt-4">
        @if ($staff->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="briefcase" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">{{ array_filter($filters) ? 'No staff match your search' : 'No staff added yet' }}</p>
                <p class="mt-1 max-w-sm text-sm text-muted">Add your teachers and office staff. Give a portal login to anyone who should sign in.</p>
                <a href="{{ route('school.staff.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" size="4" /> Add staff</a>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($staff as $m)
                    <li>
                        <a href="{{ route('school.staff.show', $m) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5 transition hover:bg-canvas">
                            @include('school.staff._avatar', ['m' => $m])
                            <div class="min-w-[10rem] flex-1">
                                <p class="truncate font-semibold text-ink">{{ $m->name }}</p>
                                <p class="truncate text-xs text-muted">{{ $m->employee_no }} · {{ collect([$m->designation, $m->department])->filter()->join(', ') ?: 'No designation' }}</p>
                            </div>
                            <p class="hidden text-sm text-body md:block">{{ $m->phone ?? '' }}</p>
                            <div class="flex flex-wrap gap-1">
                                @if ($m->status === 'left')
                                    <span class="badge badge-neutral">Left</span>
                                @elseif ($m->user?->is_active)
                                    <span class="badge badge-success">{{ $m->user->roleLabel() }}</span>
                                @else
                                    <span class="badge badge-neutral">No login</span>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($staff->hasPages())<div class="border-t border-line px-4 py-3">{{ $staff->links() }}</div>@endif
        @endif
    </div>
</x-layouts.school>
