<x-layouts.school :heading="$exam->name" subheading="Enter marks class by class, then open results and report cards.">
    <x-slot:actions>
        <a href="{{ route('school.exams.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Exams</a>
        @if ($canManage)
            <a href="{{ route('school.exams.analysis', $exam) }}" class="btn btn-secondary"><x-icon name="chart" size="4" /> Result analysis</a>
            <form method="POST" action="{{ route('school.exams.toggle', $exam) }}">@csrf<input type="hidden" name="field" value="is_locked"><button class="btn btn-secondary"><x-icon name="lock" size="4" /> {{ $exam->is_locked ? 'Unlock marks' : 'Lock marks' }}</button></form>
            <form method="POST" action="{{ route('school.exams.toggle', $exam) }}">@csrf<input type="hidden" name="field" value="is_published"><button class="btn {{ $exam->is_published ? 'btn-secondary' : 'btn-primary' }}">{{ $exam->is_published ? 'Hide results' : 'Publish results' }}</button></form>
        @endif
    </x-slot:actions>

    @if ($exam->is_locked)
        <div class="alert alert-info mb-4"><x-icon name="lock" class="mt-px" /><p>Marks are locked. Only results and report cards can be viewed.</p></div>
    @endif

    @if ($canManage)
        <section class="card mb-6">
            <div class="card-header"><div><h2 class="card-title">Papers</h2><p class="mt-0.5 text-sm text-muted">Tick the subjects each class sits, with maximum and pass marks.</p></div></div>
            <div class="divide-y divide-line">
                @forelse ($classes as $class)
                    @php $current = ($papers[$class->id] ?? collect())->keyBy('subject_id'); $list = $class->subjects->isNotEmpty() ? $class->subjects : $subjects; @endphp
                    <details class="group" @if ($loop->first && $papers->isEmpty()) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-3 px-5 py-3 hover:bg-canvas">
                            <span class="min-w-0 flex-1 font-semibold text-ink">{{ $class->name }}</span>
                            <span class="text-xs text-muted">{{ $current->count() }} papers</span>
                            <x-icon name="chevron-right" size="4" class="text-muted transition group-open:rotate-90" />
                        </summary>
                        <form method="POST" action="{{ route('school.exams.papers', [$exam, $class]) }}" class="space-y-2 bg-canvas/60 px-5 py-4">
                            @csrf
                            @method('PUT')
                            @forelse ($list as $subject)
                                @php $p = $current[$subject->id] ?? null; @endphp
                                <div class="grid items-center gap-2 sm:grid-cols-[1fr_6rem_6rem_9rem]">
                                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="papers[{{ $subject->id }}][on]" value="1" class="checkbox" @checked($p || $papers->isEmpty())> {{ $subject->name }}</label>
                                    <input type="number" name="papers[{{ $subject->id }}][max_marks]" value="{{ $p ? (float) $p->max_marks : 100 }}" min="1" step="0.5" class="input py-1.5 text-sm" aria-label="Max marks">
                                    <input type="number" name="papers[{{ $subject->id }}][pass_marks]" value="{{ $p ? (float) $p->pass_marks : '' }}" min="0" step="0.5" class="input py-1.5 text-sm" placeholder="Pass" aria-label="Pass marks">
                                    <input type="date" name="papers[{{ $subject->id }}][paper_date]" value="{{ $p?->paper_date?->toDateString() }}" class="input py-1.5 text-sm" aria-label="Paper date">
                                </div>
                            @empty
                                <p class="text-sm text-muted">Add subjects first under School setup → Subjects.</p>
                            @endforelse
                            @if ($list->isNotEmpty())<div class="flex justify-end pt-2"><button class="btn btn-primary btn-sm">Save papers of {{ $class->name }}</button></div>@endif
                        </form>
                    </details>
                @empty
                    <p class="px-5 py-6 text-sm text-muted">Add classes first.</p>
                @endforelse
            </div>
        </section>
    @endif

    <div class="space-y-4">
        @forelse ($sections as $section)
            @php $list = $papers[$section->class_id] ?? collect(); $n = (int) ($strength[$section->id] ?? 0); @endphp
            @continue($list->isEmpty())
            <section class="card">
                <div class="card-header flex-wrap gap-2">
                    <h2 class="card-title">{{ $section->label() }} <span class="text-sm font-normal text-muted">· {{ $n }} students</span></h2>
                    <div class="flex flex-wrap gap-1">
                        <a href="{{ route('school.exams.results', [$exam, $section]) }}" class="btn btn-secondary btn-sm">Result sheet</a>
                        <a href="{{ route('school.exams.report-cards', [$exam, $section]) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="printer" size="4" /> Report cards</a>
                    </div>
                </div>
                <ul class="grid divide-y divide-line sm:grid-cols-2 sm:divide-y-0 xl:grid-cols-3">
                    @foreach ($list->sortBy(fn ($p) => $p->subject?->name) as $paper)
                        @php $done = $entered[$paper->id.'-'.$section->id] ?? 0; @endphp
                        <li><a href="{{ route('school.exams.marks', [$exam, $paper, $section]) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                            <span class="min-w-0 flex-1"><span class="block truncate text-sm font-medium text-ink">{{ $paper->subject?->name }}</span><span class="text-xs text-muted">out of {{ (float) $paper->max_marks }}</span></span>
                            <span class="badge {{ $n && $done >= $n ? 'badge-success' : ($done ? 'badge-warning' : 'badge-neutral') }}">{{ $done }}/{{ $n }}</span>
                        </a></li>
                    @endforeach
                </ul>
            </section>
        @empty
            <div class="card px-4 py-14 text-center text-sm text-muted">No classes to show.</div>
        @endforelse
    </div>
</x-layouts.school>
