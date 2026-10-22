@php $canManage = auth('school')->user()->allows('students.manage'); @endphp
<x-layouts.school heading="Students" :subheading="$limit ? number_format($counts['active']).' of '.number_format($limit).' students on your plan.' : 'All students of your school.'">
    @if ($canManage)
        <x-slot:actions>
            <a href="{{ route('school.students.promote') }}" class="btn btn-secondary"><x-icon name="arrow-right" size="4" /> Promote</a>
            <a href="{{ route('school.students.import') }}" class="btn btn-secondary"><x-icon name="upload" size="4" /> Import</a>
            <a href="{{ route('school.students.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> Admit student</a>
        </x-slot:actions>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Students" :value="number_format($counts['active'])" icon="users" tone="brand" />
        <x-stat label="Boys" :value="number_format($counts['boys'])" icon="users" tone="info" />
        <x-stat label="Girls" :value="number_format($counts['girls'])" icon="users" tone="danger" />
        <x-stat label="New this month" :value="number_format($counts['new'])" icon="plus" tone="success" />
    </div>

    <form method="GET" class="card mt-6 grid gap-3 p-4 md:grid-cols-[1fr_auto_auto_auto_auto]">
        <label for="q" class="sr-only">Search</label>
        <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, admission no., father or phone" class="input">
        @include('school.partials.section-select', ['name' => 'section_id', 'groups' => $groups, 'value' => $filters['section_id'] ?? null, 'placeholder' => 'All classes', 'class' => 'md:w-44'])
        <select name="gender" class="input md:w-32" aria-label="Gender">
            <option value="">Boys & girls</option>
            @foreach (\App\Models\Student::GENDERS as $v => $l)<option value="{{ $v }}" @selected(($filters['gender'] ?? '') === $v)>{{ $l }}</option>@endforeach
        </select>
        <select name="status" class="input md:w-36" aria-label="Status">
            @foreach ([...\App\Models\Student::STATUSES, 'all' => 'All'] as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? 'active') === $v)>{{ $l }}</option>@endforeach
        </select>
        <div class="flex gap-2">
            <button class="btn btn-secondary flex-1">Filter</button>
            <a data-no-progress href="{{ route('school.students.export', request()->query()) }}" class="btn btn-ghost" title="Export CSV"><x-icon name="download" size="4" /><span class="sr-only">Export CSV</span></a>
        </div>
    </form>

    <div class="card mt-4">
        @if ($students->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="users" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">{{ array_filter($filters) ? 'No students match your search' : 'No students yet' }}</p>
                @if ($canManage && ! array_filter($filters))
                    <p class="mt-1 max-w-sm text-sm text-muted">Admit students one by one, or import your whole list from Excel in one go.</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('school.students.import') }}" class="btn btn-secondary"><x-icon name="upload" size="4" /> Import from Excel</a>
                        <a href="{{ route('school.students.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> Admit student</a>
                    </div>
                @endif
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($students as $s)
                    <li>
                        <a href="{{ route('school.students.show', $s) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 transition hover:bg-canvas">
                            @include('school.students._avatar', ['s' => $s])
                            <div class="min-w-[10rem] flex-1">
                                <p class="truncate font-semibold text-ink">{{ $s->name }}</p>
                                <p class="truncate text-xs text-muted">{{ $s->admission_no }}{{ $s->father_name ? ' · s/o '.$s->father_name : '' }}</p>
                            </div>
                            <p class="text-sm text-body">{{ $s->classLabel() }}{{ $s->roll_no ? ' · Roll '.$s->roll_no : '' }}</p>
                            @if ($s->status !== 'active')<span class="badge badge-neutral">{{ \App\Models\Student::STATUSES[$s->status] }}</span>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($students->hasPages())<div class="border-t border-line px-4 py-3">{{ $students->links() }}</div>@endif
        @endif
    </div>
</x-layouts.school>
