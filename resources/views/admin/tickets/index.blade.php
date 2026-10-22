<x-layouts.admin heading="Support tickets" subheading="Questions and problems raised by schools from their portal.">
    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm shrink-0 {{ empty($filters['status']) ? 'btn-dark' : 'btn-secondary' }}">All <span class="opacity-60">{{ $counts->sum() }}</span></a>
        @foreach (\App\Models\SupportTicket::STATUSES as $value => $label)
            <a href="{{ route('admin.tickets.index', ['status' => $value]) }}" class="btn btn-sm shrink-0 {{ ($filters['status'] ?? null) === $value ? 'btn-dark' : 'btn-secondary' }}">{{ $label }} <span class="opacity-60">{{ $counts[$value] ?? 0 }}</span></a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        @if (! empty($filters['status']))<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input max-w-sm" placeholder="Search subject or school" aria-label="Search tickets">
        <select name="priority" class="input w-auto" aria-label="Priority">
            <option value="">Any priority</option>
            @foreach (\App\Models\SupportTicket::PRIORITIES as $value => $label)<option value="{{ $value }}" @selected(($filters['priority'] ?? null) === $value)>{{ $label }}</option>@endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-ink"><input type="checkbox" name="mine" value="1" class="checkbox" @checked($filters['mine'] ?? false)> Assigned to me</label>
        <button class="btn btn-dark"><x-icon name="filter" size="4" /> Filter</button>
    </form>

    <div class="card">
        @if ($tickets->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No tickets here</p>
                <p class="mt-1 text-sm text-muted">Schools open tickets from Help & support in their portal.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($tickets as $ticket)
                    <li>
                        <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-4 transition hover:bg-canvas">
                            <div class="min-w-[12rem] flex-1">
                                <p class="truncate font-semibold text-ink">{{ $ticket->subject }}</p>
                                <p class="truncate text-sm text-muted"><span class="font-mono text-xs">{{ $ticket->number() }}</span> · {{ $ticket->school?->name ?? 'Deleted school' }} · {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge {{ $ticket->priorityBadge() }}">{{ \App\Models\SupportTicket::PRIORITIES[$ticket->priority] }}</span>
                                <span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportTicket::STATUSES[$ticket->status] }}</span>
                                <span class="w-28 truncate text-right text-xs text-muted">{{ $ticket->assignee?->name ?? 'Unassigned' }}</span>
                                <span class="w-20 text-right text-xs text-muted">{{ ($ticket->last_reply_at ?? $ticket->created_at)->diffForHumans(short: true) }}</span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($tickets->hasPages())<div class="border-t border-line px-4 py-3">{{ $tickets->links() }}</div>@endif
        @endif
    </div>
</x-layouts.admin>
