@if ($items->isEmpty())
    <p class="card-body text-sm text-muted">No activity yet.</p>
@else
    <ol class="divide-y divide-line">
        @foreach ($items as $log)
            <li class="flex gap-3 px-5 py-3">
                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full {{ $log->actor_type === 'school' ? 'bg-info-tint text-info' : 'bg-brand-tint text-brand-text' }}"><x-icon :name="$log->icon()" size="4" /></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink">{{ $log->description }}</p>
                    <p class="truncate text-xs text-muted" title="{{ $log->created_at->format('d M Y, h:i A') }}">{{ $log->actor_name }} · {{ $log->created_at->diffForHumans() }}</p>
                </div>
            </li>
        @endforeach
    </ol>
@endif
