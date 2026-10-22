<x-layouts.school heading="Fees" subheading="Students with fees past the due date.">
    <x-slot:actions>
        <a data-no-progress href="{{ route('school.fees.defaulters', ['section_id' => $sectionId, 'export' => 1]) }}" class="btn btn-secondary"><x-icon name="download" size="4" /> Export</a>
    </x-slot:actions>
    @include('school.fees._nav')

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        @include('school.partials.section-select', ['name' => 'section_id', 'groups' => $groups, 'value' => $sectionId, 'placeholder' => 'All classes', 'class' => 'w-56'])
        <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="card">
        @if ($rows->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No defaulters. Well done!</p>
        @else
            <div class="card-header"><p class="text-sm text-body">{{ $rows->count() }} students owe <span class="font-semibold text-danger">{{ money($rows->sum('owed')) }}</span></p></div>
            <ul class="divide-y divide-line">
                @foreach ($rows as $r)
                    @php $s = $students[$r->student_id] ?? null; @endphp
                    @continue(! $s)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
                        <a href="{{ route('school.fees.student', $s) }}" class="min-w-[11rem] flex-1 hover:underline">
                            <p class="truncate font-medium text-ink">{{ $s->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $s->classLabel() }} · {{ $s->father_name }} · since {{ \Illuminate\Support\Carbon::parse($r->since)->format('d M Y') }}</p>
                        </a>
                        @if ($phone = $s->parentPhone())
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="btn btn-ghost btn-sm"><x-icon name="phone" size="4" /> {{ $phone }}</a>
                        @endif
                        <p class="w-32 text-right font-semibold text-danger tabular">{{ money($r->owed) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.school>
