<x-layouts.school :title="$ticket->number()">
    <div class="mb-6 min-w-0">
        <a href="{{ route('school.support.index') }}" class="text-xs font-medium text-muted hover:text-ink">← Help & support</a>
        <h1 class="page-title">{{ $ticket->subject }}</h1>
        <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
            <span class="font-mono text-xs">{{ $ticket->number() }}</span>
            <span class="badge {{ $ticket->statusBadge() }}">{{ \App\Models\SupportTicket::STATUSES[$ticket->status] }}</span>
            {{ \App\Models\SupportTicket::CATEGORIES[$ticket->category] ?? '' }}
        </p>
    </div>

    <div class="max-w-3xl space-y-6">
        <section class="card bg-canvas/60 p-4 sm:p-6">
            @include('partials.ticket-thread', ['mine' => 'school'])
        </section>

        @if ($ticket->isClosed())
            <div class="alert alert-info"><x-icon name="info" class="mt-px" /><p>This ticket is closed. <a href="{{ route('school.support.index') }}" class="font-semibold underline">Open a new ticket</a> if you need more help.</p></div>
        @else
            <form method="POST" action="{{ route('school.support.reply', $ticket) }}" class="card">
                @csrf
                <div class="card-body"><x-form.textarea name="body" label="Your reply" rows="4" required /></div>
                <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary"><x-icon name="send" size="4" /> Send</button></div>
            </form>
        @endif
    </div>
</x-layouts.school>
