<x-layouts.admin heading="Activity log" subheading="A record of who did what, by your team and inside school portals.">
    <form method="GET" class="card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] lg:items-end">
        <div>
            <label for="q" class="label">Search</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Text or person">
        </div>
        <x-form.select name="area" label="Area" :value="$filters['area'] ?? null" placeholder="All" :options="\App\Http\Controllers\Admin\ActivityController::AREAS" />
        <x-form.select name="who" label="By" :value="$filters['who'] ?? null" placeholder="Anyone" :options="['platform' => 'AivraSol team', 'school' => 'School users', 'system' => 'System']" />
        <x-form.input name="from" type="date" label="From" :value="$filters['from'] ?? null" />
        <x-form.input name="to" type="date" label="To" :value="$filters['to'] ?? null" />
        <div class="flex gap-2">
            <button class="btn btn-dark flex-1"><x-icon name="filter" size="4" /> Filter</button>
            @if (array_filter($filters))<a href="{{ route('admin.activity') }}" class="btn btn-ghost">Clear</a>@endif
        </div>
    </form>

    <div class="card">
        @if ($logs->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No activity found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>When</th><th>Who</th><th>What</th><th>School</th><th>IP</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="tabular text-xs whitespace-nowrap" title="{{ $log->created_at->diffForHumans() }}">{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <p class="font-medium text-ink">{{ $log->actor_name }}</p>
                                    <p class="text-xs text-muted">{{ ['platform' => 'AivraSol', 'school' => 'School user', 'system' => 'System'][$log->actor_type] ?? $log->actor_type }}</p>
                                </td>
                                <td><span class="flex items-start gap-2 text-ink"><x-icon :name="$log->icon()" size="4" class="mt-0.5 text-muted" /> {{ $log->description }}</span></td>
                                <td>@if ($log->school)<a href="{{ route('admin.schools.show', $log->school_id) }}" class="text-brand-text hover:underline">{{ $log->school->name }}</a>@else <span class="text-muted">—</span> @endif</td>
                                <td class="font-mono text-xs text-muted">{{ $log->ip_address ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())<div class="border-t border-line px-4 py-3">{{ $logs->links() }}</div>@endif
        @endif
    </div>
</x-layouts.admin>
