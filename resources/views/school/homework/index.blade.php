<x-layouts.school heading="Homework & diary" :subheading="$date->format('l, d M Y')">
    <x-slot:actions>
        <button type="button" class="btn btn-primary" data-toggle="#new-diary"><x-icon name="plus" size="4" /> Add diary</button>
    </x-slot:actions>

    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="input w-auto" aria-label="Date">
        <select name="section" class="input w-48" aria-label="Class">
            <option value="">All my classes</option>
            @foreach ($sections as $s)<option value="{{ $s->id }}" @selected($sectionId === $s->id)>{{ $s->label() }}</option>@endforeach
        </select>
        <button class="btn btn-secondary">Show</button>
    </form>

    <form id="new-diary" method="POST" action="{{ route('school.homework.store') }}" enctype="multipart/form-data" class="card mb-6" @unless ($errors->any()) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">Add diary / homework</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-form.select name="section_id" label="Class" required :options="$sections->mapWithKeys(fn ($s) => [$s->id => $s->label()])" :value="$sectionId" placeholder="Choose class" />
            <x-form.select name="subject_id" label="Subject" :options="$subjects" placeholder="General" />
            <x-form.input name="title" label="Title" required maxlength="150" placeholder="e.g. Exercise 4.2, Q1–5" class="sm:col-span-2" />
            <x-form.textarea name="details" label="Details" rows="3" class="sm:col-span-2" />
            <x-form.input name="date" type="date" label="Date" :value="$date->toDateString()" required />
            <x-form.input name="due_date" type="date" label="Hand in by" />
            <div class="sm:col-span-2">
                <label for="attachment" class="label">Attachment (optional)</label>
                <input id="attachment" type="file" name="attachment" class="input">
                @error('attachment')<p class="error">{{ $message }}</p>@else<p class="hint">PDF, Word, Excel or a photo, up to 5 MB.</p>@enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <button type="button" class="btn btn-ghost" data-toggle="#new-diary">Cancel</button>
            <button class="btn btn-primary">Save diary</button>
        </div>
    </form>

    @if ($missing->isNotEmpty() && ! $sectionId && $date->isToday())
        <div class="alert alert-warning mb-4"><x-icon name="clock" class="mt-px" /><p><span class="font-semibold">No diary yet:</span> {{ $missing->map->label()->join(', ') }}</p></div>
    @endif

    @if ($homework->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No diary for this day yet.</div>
    @else
        <div class="space-y-4">
            @foreach ($homework as $items)
                <section class="card">
                    <div class="card-header"><h2 class="card-title">{{ $items->first()->section?->label() }}</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($items as $h)
                            <li class="flex flex-wrap items-start gap-x-4 gap-y-2 px-5 py-3">
                                <div class="min-w-[12rem] flex-1">
                                    <p class="font-medium text-ink"><span class="text-brand-text">{{ $h->subject?->name ?? 'General' }}:</span> {{ $h->title }}</p>
                                    @if ($h->details)<p class="mt-0.5 text-sm break-words whitespace-pre-line text-body">{{ $h->details }}</p>@endif
                                    <p class="mt-1 text-xs text-muted">{{ $h->author?->name }}{{ $h->due_date ? ' · hand in by '.$h->due_date->format('d M') : '' }}</p>
                                </div>
                                <div class="flex gap-1">
                                    @if ($h->attachment_path)<a href="{{ route('school.homework.file', $h) }}" data-no-progress class="btn btn-ghost btn-sm" title="{{ $h->attachment_name }}"><x-icon name="download" size="4" /><span class="sr-only">Download</span></a>@endif
                                    @if ($h->created_by === auth('school')->id() || auth('school')->user()->allows('attendance.all'))
                                        <form method="POST" action="{{ route('school.homework.destroy', $h) }}" data-confirm="Delete this diary entry?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete"><x-icon name="trash" size="4" /></button></form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.school>
