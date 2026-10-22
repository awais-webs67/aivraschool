<x-layouts.school heading="Exams & results" subheading="Create an exam, enter marks, then print results and report cards.">
    @if ($canManage)
        <x-slot:actions><button type="button" class="btn btn-primary" data-toggle="#new-exam"><x-icon name="plus" size="4" /> New exam</button></x-slot:actions>

        <form id="new-exam" method="POST" action="{{ route('school.exams.store') }}" class="card mb-6" @unless ($errors->has('name')) hidden @endunless>
            @csrf
            <div class="card-header"><h2 class="card-title">New exam</h2></div>
            <div class="card-body grid gap-4 sm:grid-cols-3">
                <x-form.input name="name" label="Name" required maxlength="80" placeholder="e.g. Mid Term 2026" />
                <x-form.input name="starts_on" type="date" label="Starts" />
                <x-form.input name="ends_on" type="date" label="Ends" />
            </div>
            <div class="flex justify-end gap-2 border-t border-line px-5 py-4"><button type="button" class="btn btn-ghost" data-toggle="#new-exam">Cancel</button><button class="btn btn-primary">Create exam</button></div>
        </form>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Exams</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($exams as $exam)
                    <li><a href="{{ route('school.exams.show', $exam) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5 hover:bg-canvas">
                        <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="academic-cap" size="5" /></span>
                        <div class="min-w-[10rem] flex-1">
                            <p class="font-semibold text-ink">{{ $exam->name }}</p>
                            <p class="text-xs text-muted">{{ $exam->starts_on ? $exam->starts_on->format('d M').($exam->ends_on ? ' – '.$exam->ends_on->format('d M Y') : '') : 'No dates' }} · {{ $exam->papers_count }} papers</p>
                        </div>
                        <div class="flex gap-1">
                            @if ($exam->is_locked)<span class="badge badge-neutral">Locked</span>@endif
                            <span class="badge {{ $exam->is_published ? 'badge-success' : 'badge-warning' }}">{{ $exam->is_published ? 'Published' : 'Not published' }}</span>
                        </div>
                    </a></li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-muted">No exams yet.</li>
                @endforelse
            </ul>
        </section>

        @if ($canManage)
            <form method="POST" action="{{ route('school.exams.settings') }}" class="card self-start">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="card-title">Grading</h2></div>
                <div class="card-body space-y-3">
                    <x-form.input name="pass_percent" type="number" min="0" max="100" step="0.01" label="Pass marks (% of each paper)" :value="$passPercent" required />
                    <p class="label">Grades (from % and above)</p>
                    @foreach (array_pad($grades, 9, [null, null]) as $i => [$min, $grade])
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="grades[{{ $i }}][min]" value="{{ $min }}" min="0" max="100" step="0.01" class="input py-1.5 text-sm" placeholder="%" aria-label="Minimum percent">
                            <input type="text" name="grades[{{ $i }}][grade]" value="{{ $grade }}" maxlength="5" class="input py-1.5 text-sm" placeholder="Grade" aria-label="Grade">
                        </div>
                    @endforeach
                    <button class="btn btn-secondary w-full">Save grading</button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.school>
