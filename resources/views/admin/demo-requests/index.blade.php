<x-layouts.admin heading="Leads" subheading="Schools that asked for a demo. Track each one from first call to signed-up school.">
    <x-slot:actions>
        <a data-no-progress href="{{ route('admin.demo-requests.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" size="4" /> Export CSV</a>
    </x-slot:actions>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        @php $all = $counts->sum(); @endphp
        <a href="{{ route('admin.demo-requests.index') }}" class="btn btn-sm shrink-0 {{ empty($filters['status']) && empty($filters['follow_up']) ? 'btn-dark' : 'btn-secondary' }}">All <span class="opacity-60">{{ $all }}</span></a>
        @foreach (\App\Models\DemoRequest::STATUS_LABELS as $value => $label)
            <a href="{{ route('admin.demo-requests.index', ['status' => $value]) }}" class="btn btn-sm shrink-0 {{ ($filters['status'] ?? null) === $value ? 'btn-dark' : 'btn-secondary' }}">{{ $label }} <span class="opacity-60">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
        <a href="{{ route('admin.demo-requests.index', ['follow_up' => 'due']) }}" class="btn btn-sm shrink-0 {{ ($filters['follow_up'] ?? null) === 'due' ? 'btn-dark' : 'btn-secondary' }}"><x-icon name="clock" size="4" /> Follow-up due <span class="opacity-60">{{ $dueCount }}</span></a>
    </div>

    <form method="GET" class="mb-4 flex max-w-xl gap-2">
        @foreach (['status', 'follow_up'] as $keep)@if (! empty($filters[$keep]))<input type="hidden" name="{{ $keep }}" value="{{ $filters[$keep] }}">@endif @endforeach
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Search name, school, phone, email or city" aria-label="Search leads">
        <button class="btn btn-dark"><x-icon name="search" size="4" /><span class="sr-only">Search</span></button>
    </form>

    <div class="card">
        @if ($requests->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-info-tint text-info"><x-icon name="inbox" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No leads here</p>
                <p class="mt-1 text-sm text-muted">Requests from the website demo form appear here.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($requests as $lead)
                    <li>
                        <a href="{{ route('admin.demo-requests.show', $lead) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-canvas">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-tint font-semibold text-brand-text">{{ strtoupper(mb_substr($lead->school_name, 0, 1)) }}</span>
                            <div class="min-w-[12rem] flex-1">
                                <p class="truncate font-semibold text-ink">{{ $lead->school_name }}</p>
                                <p class="truncate text-sm text-muted">{{ $lead->name }} · {{ $lead->phone }}{{ $lead->city ? ' · '.$lead->city : '' }}{{ $lead->students ? ' · '.number_format($lead->students).' students' : '' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($lead->followUpDue())<span class="badge badge-warning"><x-icon name="clock" size="3" /> Follow up</span>@endif
                                <span class="badge {{ $lead->statusBadge() }}">{{ \App\Models\DemoRequest::STATUS_LABELS[$lead->status] ?? $lead->status }}</span>
                                <span class="tabular w-24 text-right text-xs text-muted">{{ $lead->created_at->format('d M Y') }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($requests->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $requests->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
