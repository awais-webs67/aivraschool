<x-layouts.school heading="Reports" subheading="Open a report on screen or download it for Excel.">
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($reports as $key => [$title, $hint, $icon])
            <a href="{{ route('school.reports.show', $key) }}" class="card flex items-start gap-4 p-5 transition hover:border-brand/40">
                <span class="grid size-11 shrink-0 place-items-center rounded-md bg-brand-tint text-brand-text"><x-icon :name="$icon" /></span>
                <span class="min-w-0"><span class="block font-semibold text-ink">{{ $title }}</span><span class="block text-sm text-muted">{{ $hint }}</span></span>
            </a>
        @endforeach
        @if (auth('school')->user()->allows('fees.manage'))
            <a href="{{ route('school.fees.defaulters') }}" class="card flex items-start gap-4 p-5 transition hover:border-brand/40">
                <span class="grid size-11 shrink-0 place-items-center rounded-md bg-danger-tint text-danger"><x-icon name="alert" /></span>
                <span class="min-w-0"><span class="block font-semibold text-ink">Fee defaulters</span><span class="block text-sm text-muted">Students with fees past the due date.</span></span>
            </a>
        @endif
        @if (auth('school')->user()->allows('exams.manage'))
            <a href="{{ route('school.exams.index') }}" class="card flex items-start gap-4 p-5 transition hover:border-brand/40">
                <span class="grid size-11 shrink-0 place-items-center rounded-md bg-info-tint text-info"><x-icon name="academic-cap" /></span>
                <span class="min-w-0"><span class="block font-semibold text-ink">Exam result analysis</span><span class="block text-sm text-muted">Open an exam, then "Result analysis".</span></span>
            </a>
        @endif
    </div>
</x-layouts.school>
