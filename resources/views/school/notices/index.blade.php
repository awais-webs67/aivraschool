<x-layouts.school heading="Notices" subheading="The school notice board.">
    @if ($canPost)
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-toggle="#new-notice"><x-icon name="plus" size="4" /> Post notice</button>
        </x-slot:actions>

        <form id="new-notice" method="POST" action="{{ route('school.notices.store') }}" enctype="multipart/form-data" class="card mb-6" @unless ($errors->any()) hidden @endunless>
            @csrf
            <div class="card-header"><h2 class="card-title">New notice</h2></div>
            <div class="card-body grid gap-4 sm:grid-cols-2">
                <x-form.input name="title" label="Title" required maxlength="150" class="sm:col-span-2" />
                <x-form.textarea name="body" label="Message" rows="4" required class="sm:col-span-2" />
                <x-form.select name="audience" label="Who sees it" :options="\App\Models\Notice::AUDIENCES" value="everyone" required />
                <div data-show-when="audience=section">
                    @include('school.partials.section-select', ['name' => 'section_id', 'label' => 'Class', 'groups' => $groups])
                </div>
                <x-form.input name="publish_on" type="date" label="Show from" :value="today()->toDateString()" required />
                <x-form.input name="expires_on" type="date" label="Hide after" hint="Optional" />
                <div class="sm:col-span-2">
                    <label for="n-attachment" class="label">Attachment (optional)</label>
                    <input id="n-attachment" type="file" name="attachment" class="input">
                    @error('attachment')<p class="error">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_pinned" value="1" class="checkbox"> Pin to the top</label>
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
                <button type="button" class="btn btn-ghost" data-toggle="#new-notice">Cancel</button>
                <button class="btn btn-primary">Post notice</button>
            </div>
        </form>
    @endif

    @if ($notices->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No notices yet.</div>
    @else
        <div class="space-y-4">
            @foreach ($notices as $n)
                @php $live = $n->publish_on->lte(today()) && (! $n->expires_on || $n->expires_on->gte(today())); @endphp
                <article class="card p-5 {{ $n->is_pinned ? 'border-brand/40' : '' }}">
                    <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
                        <div class="min-w-[12rem] flex-1">
                            <h2 class="flex flex-wrap items-center gap-2 font-semibold text-ink">
                                @if ($n->is_pinned)<x-icon name="bolt" size="4" class="text-brand" />@endif
                                <span class="min-w-0 break-words">{{ $n->title }}</span>
                                @if ($canPost)
                                    <span class="badge badge-neutral">{{ $n->audienceLabel() }}</span>
                                    @unless ($live)<span class="badge badge-warning">{{ $n->publish_on->isFuture() ? 'Scheduled' : 'Ended' }}</span>@endunless
                                @endif
                            </h2>
                            <p class="mt-2 text-sm break-words whitespace-pre-line text-body">{{ $n->body }}</p>
                            <p class="mt-2 text-xs text-muted">{{ $n->author?->name }} · {{ $n->publish_on->format('d M Y') }}</p>
                        </div>
                        <div class="flex gap-1">
                            @if ($n->attachment_path)<a href="{{ route('school.notices.file', $n) }}" data-no-progress class="btn btn-secondary btn-sm"><x-icon name="download" size="4" /> {{ \Illuminate\Support\Str::limit($n->attachment_name, 20) }}</a>@endif
                            @if ($canPost)
                                <form method="POST" action="{{ route('school.notices.destroy', $n) }}" data-confirm="Delete this notice?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete"><x-icon name="trash" size="4" /></button></form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        @if ($notices->hasPages())<div class="mt-4">{{ $notices->links() }}</div>@endif
    @endif
</x-layouts.school>
