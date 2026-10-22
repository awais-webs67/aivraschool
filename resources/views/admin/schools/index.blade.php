<x-layouts.admin heading="Schools" subheading="Every school subscribed to Skooline. Each one has its own portal and fully separate data.">
    <x-slot:actions>
        @can('schools.view')<a data-no-progress href="{{ route('admin.schools.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" size="4" /> Export CSV</a>@endcan
        @can('schools.manage')<a href="{{ route('admin.schools.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> Add school</a>@endcan
    </x-slot:actions>

    <form method="GET" class="card mb-4 grid gap-3 p-4 sm:grid-cols-[1fr_auto_auto_auto]">
        <label class="sr-only" for="q">Search</label>
        <div class="relative">
            <x-icon name="search" size="4" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted" />
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, address, city or email" class="input pl-9">
        </div>
        <select name="status" class="input sm:w-40" aria-label="Status">
            <option value="">All statuses</option>
            @foreach (\App\Enums\SchoolStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <select name="plan" class="input sm:w-40" aria-label="Plan">
            <option value="">All plans</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((string) ($filters['plan'] ?? '') === (string) $plan->id)>{{ $plan->name }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-dark flex-1 sm:flex-none">Filter</button>
            @if (array_filter($filters))
                <a href="{{ route('admin.schools.index') }}" class="btn btn-ghost">Clear</a>
            @endif
        </div>
    </form>

    <div class="card">
        @if ($schools->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="building" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">{{ array_filter($filters) ? 'No schools match these filters' : 'No schools yet' }}</p>
                <p class="mt-1 text-sm text-muted">{{ array_filter($filters) ? 'Try a different search or clear the filters.' : 'Add a school to give it its own Skooline portal.' }}</p>
                @unless (array_filter($filters))
                    @can('schools.manage')<a href="{{ route('admin.schools.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" size="4" /> Add school</a>@endcan
                @endunless
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>School</th><th>Contact</th><th>Plan</th><th>Status</th><th>Access until</th><th class="text-right">Actions</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($schools as $school)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.schools.show', $school) }}" class="font-medium text-ink hover:text-brand-text">{{ $school->name }}</a>
                                    <p class="text-xs text-muted">{{ $school->displayUrl() }}</p>
                                </td>
                                <td>
                                    <p>{{ $school->principal_name ?? '—' }}</p>
                                    <p class="text-xs text-muted">{{ collect([$school->city, $school->phone])->filter()->join(' · ') }}</p>
                                </td>
                                <td>{{ $school->plan?->name ?? '—' }}</td>
                                <td><div class="flex flex-wrap gap-1">@include('admin.partials.school-status')</div></td>
                                <td class="tabular">{{ $school->accessEndsAt()?->format('d M Y') ?? ($school->status->value === 'active' ? 'Lifetime' : '—') }}</td>
                                <td class="text-right">
                                    <div class="inline-flex gap-1">
                                        <a href="{{ $school->url('login') }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm" title="Open school portal"><x-icon name="external" size="4" /><span class="sr-only">Open portal</span></a>
                                        <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-secondary btn-sm">Manage</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($schools->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $schools->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
