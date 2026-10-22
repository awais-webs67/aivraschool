<x-layouts.admin :title="$ticket->number()">
    <div class="mb-6 min-w-0">
        <a href="{{ route('admin.tickets.index') }}" class="text-xs font-medium text-muted hover:text-ink">← Support tickets</a>
        <h1 class="page-title">{{ $ticket->subject }}</h1>
        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
            <span class="font-mono text-xs">{{ $ticket->number() }}</span>
            <span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportTicket::STATUSES[$ticket->status] }}</span>
            <span class="badge {{ $ticket->priorityBadge() }}">{{ \App\Models\SupportTicket::PRIORITIES[$ticket->priority] }}</span>
            @if ($ticket->school)<a href="{{ route('admin.schools.show', $ticket->school) }}" class="font-medium text-brand-text hover:underline">{{ $ticket->school->name }}</a>@endif
        </p>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card bg-canvas/60 p-4 sm:p-6">
                @include('partials.ticket-thread', ['mine' => 'platform'])
            </section>
            <form method="POST" action="{{ route('admin.tickets.reply', $ticket->id) }}" class="card">
                @csrf
                <div class="card-body">
                    <x-form.textarea name="body" label="Reply to the school" rows="5" required placeholder="Write your answer…" />
                </div>
                <div class="flex justify-end border-t border-line px-5 py-4">
                    <button class="btn btn-primary"><x-icon name="send" size="4" /> Send reply</button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.tickets.update', $ticket->id) }}" class="card">
                @csrf
                @method('PATCH')
                <div class="card-header"><h2 class="card-title">Ticket</h2></div>
                <div class="card-body space-y-4">
                    <x-form.select name="status" label="Status" :value="$ticket->status" :options="\App\Models\SupportTicket::STATUSES" />
                    <x-form.select name="priority" label="Priority" :value="$ticket->priority" :options="\App\Models\SupportTicket::PRIORITIES" />
                    <x-form.select name="assigned_to" label="Assigned to" :value="$ticket->assigned_to" :options="$staff" placeholder="Unassigned" />
                    <button class="btn btn-secondary w-full">Update ticket</button>
                </div>
            </form>
            <section class="card">
                <div class="card-header"><h2 class="card-title">Details</h2></div>
                <dl class="card-body space-y-3 text-sm">
                    @foreach (['Raised by' => $ticket->user ? $ticket->user->name.' ('.$ticket->user->email.')' : '—', 'Category' => \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? $ticket->category, 'Opened' => $ticket->created_at->format('d M Y, h:i A')] as $label => $value)
                        <div class="grid grid-cols-[5.5rem_1fr] gap-2"><dt class="text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value }}</dd></div>
                    @endforeach
                </dl>
                @can('impersonate')
                    @if ($ticket->school)
                        <form method="POST" action="{{ route('admin.schools.impersonate', $ticket->school) }}" class="border-t border-line px-5 py-4">
                            @csrf
                            <button class="btn btn-secondary btn-sm w-full"><x-icon name="login-as" size="4" /> Open this school's portal</button>
                        </form>
                    @endif
                @endcan
            </section>
        </div>
    </div>
</x-layouts.admin>
