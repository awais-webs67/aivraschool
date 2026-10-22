<x-layouts.admin heading="Announcements" subheading="Notices shown on school dashboards: new features, maintenance, payment reminders.">
    <x-slot:actions>
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> New announcement</a>
    </x-slot:actions>

    <div class="card">
        @if ($announcements->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="bolt" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No announcements yet</p>
                <p class="mt-1 max-w-sm text-sm text-muted">Tell every school about a new feature, or remind one school about its renewal.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($announcements as $a)
                    <li class="flex flex-wrap items-start gap-4 px-5 py-4">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full {{ ['info' => 'bg-info', 'success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger'][$a->tone] ?? 'bg-info' }}"></span>
                        <div class="min-w-[12rem] flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                                <span class="min-w-0">{{ $a->title }}</span>
                                <span class="badge {{ $a->isLive() ? 'badge-success' : 'badge-neutral' }}">{{ $a->isLive() ? 'Live' : ($a->is_active ? 'Scheduled / ended' : 'Off') }}</span>
                            </p>
                            <p class="mt-1 line-clamp-2 text-sm text-body">{{ $a->body }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $a->audienceLabel() }} · {{ $a->starts_on?->format('d M Y') ?? 'Now' }} – {{ $a->ends_on?->format('d M Y') ?? 'no end' }}</p>
                        </div>
                        <div class="flex gap-1">
                            <a href="{{ route('admin.announcements.edit', $a) }}" class="btn btn-ghost btn-sm" title="Edit"><x-icon name="pencil" size="4" /><span class="sr-only">Edit</span></a>
                            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" data-confirm="Delete this announcement?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
            @if ($announcements->hasPages())<div class="border-t border-line px-4 py-3">{{ $announcements->links() }}</div>@endif
        @endif
    </div>
</x-layouts.admin>
