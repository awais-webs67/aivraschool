{{-- Conversation of a support ticket. $mine = author_type shown on the right ('platform' or 'school'). --}}
<ol class="space-y-4">
    @foreach ($ticket->messages as $message)
        @php $own = $message->author_type === $mine; @endphp
        <li class="flex {{ $own ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[min(36rem,100%)] rounded-lg px-4 py-3 {{ $own ? 'bg-ink text-white' : 'border border-line bg-white text-ink' }}">
                <p class="flex flex-wrap items-baseline gap-x-2 text-xs {{ $own ? 'text-white/60' : 'text-muted' }}">
                    <span class="font-semibold {{ $own ? 'text-white' : 'text-ink' }}">{{ $message->author_name }}</span>
                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d M Y, h:i A') }}</time>
                </p>
                <p class="mt-1.5 text-sm break-words whitespace-pre-line">{{ $message->body }}</p>
            </div>
        </li>
    @endforeach
</ol>
