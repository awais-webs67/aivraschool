<x-layouts.school heading="Help & support" subheading="Ask AivraSol a question or report a problem. We reply right here.">
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Your tickets</h2></div>
            @if ($tickets->isEmpty())
                <div class="flex flex-col items-center px-4 py-14 text-center">
                    <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="chat" size="7" /></span>
                    <p class="mt-4 font-semibold text-ink">No tickets yet</p>
                    <p class="mt-1 max-w-sm text-sm text-muted">Need help? Send us a message with the form.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($tickets as $ticket)
                        <li>
                            <a href="{{ route('school.support.show', $ticket) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4 transition hover:bg-canvas">
                                <div class="min-w-[12rem] flex-1">
                                    <p class="truncate font-semibold text-ink">{{ $ticket->subject }}</p>
                                    <p class="text-xs text-muted"><span class="font-mono">{{ $ticket->number() }}</span> · {{ ($ticket->last_reply_at ?? $ticket->created_at)->diffForHumans() }}</p>
                                </div>
                                <span class="badge {{ $ticket->statusBadge() }}">{{ $ticket->status === 'answered' ? 'Reply from AivraSol' : \App\Models\SupportTicket::STATUSES[$ticket->status] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($tickets->hasPages())<div class="border-t border-line px-4 py-3">{{ $tickets->links() }}</div>@endif
            @endif
        </section>

        <form method="POST" action="{{ route('school.support.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">New ticket</h2></div>
            <div class="card-body space-y-4">
                <x-form.input name="subject" label="Subject" required placeholder="e.g. Cannot add a new class" />
                <x-form.select name="category" label="Topic" value="general" :options="\App\Models\SupportTicket::CATEGORIES" />
                <x-form.select name="priority" label="How urgent?" value="normal" :options="\App\Models\SupportTicket::PRIORITIES" />
                <x-form.textarea name="body" label="Message" rows="5" required placeholder="Describe what happened and what you expected." />
                <button class="btn btn-primary w-full"><x-icon name="send" size="4" /> Send to support</button>
            </div>
        </form>
    </div>
</x-layouts.school>
