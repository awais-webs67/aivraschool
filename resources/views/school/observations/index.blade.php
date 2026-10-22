<x-layouts.school heading="Class observations" subheading="Watch a lesson, rate it, and track each teacher over time.">
    <x-slot:actions><button type="button" class="btn btn-primary" data-toggle="#new-obs"><x-icon name="plus" size="4" /> New observation</button></x-slot:actions>

    <form id="new-obs" method="POST" action="{{ route('school.observations.store') }}" class="card mb-6" @unless ($errors->any()) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">New observation</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-form.select name="staff_id" label="Teacher" required :options="$teachers->pluck('name', 'id')" placeholder="Choose" :value="$staffId" />
            @include('school.partials.section-select', ['name' => 'section_id', 'label' => 'Class', 'groups' => $groups, 'placeholder' => '—'])
            <x-form.select name="subject_id" label="Subject" :options="$subjects" placeholder="—" />
            <x-form.input name="date" type="date" label="Date" :value="today()->toDateString()" required />
        </div>
        <div class="grid gap-3 border-t border-line p-5 md:grid-cols-2">
            @foreach (\App\Models\Observation::AREAS as $key => $label)
                <fieldset class="rounded-lg border border-line p-3">
                    <legend class="px-1 text-sm font-semibold text-ink">{{ $label }}</legend>
                    <div class="flex flex-wrap gap-1">
                        @foreach (\App\Models\Observation::SCALE as $n => $word)
                            <label class="cursor-pointer">
                                <input type="radio" name="ratings[{{ $key }}]" value="{{ $n }}" class="peer sr-only" @checked((int) old("ratings.$key", 3) === $n) required>
                                <span class="inline-flex items-center rounded-md border border-line px-2.5 py-1 text-xs font-semibold text-muted peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand" title="{{ $word }}">{{ $n }}<span class="sr-only"> {{ $word }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach
            <p class="text-xs text-muted md:col-span-2">1 weak · 2 needs work · 3 good · 4 very good · 5 excellent</p>
            <x-form.textarea name="strengths" label="What went well" rows="2" />
            <x-form.textarea name="improvements" label="To improve" rows="2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4"><button type="button" class="btn btn-ghost" data-toggle="#new-obs">Cancel</button><button class="btn btn-primary">Save observation</button></div>
    </form>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Teachers</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($teachers as $t)
                    @php $a = $averages[$t->id] ?? null; @endphp
                    <li><a href="{{ route('school.observations.index', ['staff' => $t->id]) }}" class="flex items-center gap-3 px-5 py-2.5 text-sm hover:bg-canvas {{ $staffId === $t->id ? 'bg-brand-tint/40' : '' }}">
                        <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $t->name }}</span>
                        @if ($a)<span class="text-xs text-muted">{{ $a->n }}×</span><span class="badge {{ $a->avg >= 4 ? 'badge-success' : ($a->avg >= 3 ? 'badge-brand' : 'badge-warning') }}">{{ number_format((float) $a->avg, 1) }}</span>@else<span class="text-xs text-muted">Not observed</span>@endif
                    </a></li>
                @empty
                    <li class="px-5 py-6 text-sm text-muted">Add staff first.</li>
                @endforelse
            </ul>
        </section>
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Observations</h2>@if ($staffId)<a href="{{ route('school.observations.index') }}" class="text-sm font-semibold text-brand-text">Show all</a>@endif</div>
            <ul class="divide-y divide-line">
                @forelse ($observations as $o)
                    <li class="px-5 py-4">
                        <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
                            <div class="min-w-[12rem] flex-1">
                                <p class="font-semibold text-ink">{{ $o->staff?->name }} <span class="badge badge-brand ml-1">{{ $o->overall }}/5</span></p>
                                <p class="text-xs text-muted">{{ $o->date->format('d M Y') }}{{ $o->section ? ' · '.$o->section->label() : '' }}{{ $o->subject ? ' · '.$o->subject->name : '' }} · by {{ $o->observer?->name }}</p>
                                <div class="mt-2 flex flex-wrap gap-1">@foreach ($o->ratings as $k => $v)<span class="badge badge-neutral">{{ \App\Models\Observation::AREAS[$k] ?? $k }}: {{ $v }}</span>@endforeach</div>
                                @if ($o->strengths)<p class="mt-2 text-sm text-body"><span class="font-medium text-success">Went well:</span> {{ $o->strengths }}</p>@endif
                                @if ($o->improvements)<p class="mt-1 text-sm text-body"><span class="font-medium text-warning">To improve:</span> {{ $o->improvements }}</p>@endif
                            </div>
                            <form method="POST" action="{{ route('school.observations.destroy', $o) }}" data-confirm="Delete this observation?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete"><x-icon name="trash" size="4" /></button></form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-muted">No observations yet.</li>
                @endforelse
            </ul>
            @if ($observations->hasPages())<div class="border-t border-line px-4 py-3">{{ $observations->links() }}</div>@endif
        </section>
    </div>
</x-layouts.school>
