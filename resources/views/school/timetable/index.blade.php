<x-layouts.school heading="Timetable" :subheading="$teacher ? 'Weekly timetable of '.$teacher->name : ($section ? 'Weekly timetable of '.$section->label() : 'Weekly timetable')">
    <x-slot:actions>
        @if ($canManage && $section)<a href="{{ route('school.timetable.edit', $section) }}" class="btn btn-primary"><x-icon name="pencil" size="4" /> Edit {{ $section->label() }}</a>@endif
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print</button>
    </x-slot:actions>
    @include('school.timetable._nav')

    @if ($canManage)
        <div class="mb-4 flex flex-wrap gap-3">
            <form method="GET" class="flex gap-2">
                @include('school.partials.section-select', ['name' => 'section', 'groups' => $groups, 'value' => $section?->id, 'placeholder' => 'Choose class', 'class' => 'w-48'])
                <button class="btn btn-secondary">Show</button>
            </form>
            <form method="GET" class="flex gap-2">
                <select name="teacher" class="input w-48" aria-label="Teacher"><option value="">Choose teacher</option>@foreach ($teachers as $id => $name)<option value="{{ $id }}" @selected($teacher?->id === $id)>{{ $name }}</option>@endforeach</select>
                <button class="btn btn-secondary">Show</button>
            </form>
        </div>
    @endif

    @if ($periods->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No periods yet. {{ $canManage ? '' : 'Ask the principal to set up the timetable.' }}
            @if ($canManage)<a href="{{ route('school.timetable.periods') }}" class="font-semibold text-brand-text underline">Set up periods</a>@endif
        </div>
    @elseif (! $section && ! $teacher)
        <div class="card px-4 py-14 text-center text-sm text-muted">Your login is not linked to a staff record.</div>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[44rem] text-sm">
                <thead>
                    <tr class="border-b border-line bg-canvas text-left text-xs text-muted">
                        <th class="w-28 px-3 py-2.5 font-semibold">Period</th>
                        @foreach ($days as $d => $dayName)<th class="px-2 py-2.5 font-semibold {{ $d === today()->dayOfWeekIso ? 'text-brand-text' : '' }}">{{ $dayName }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($periods as $p)
                        <tr class="border-b border-line {{ $p->is_break ? 'bg-canvas' : '' }}">
                            <td class="px-3 py-2 align-top"><p class="font-semibold text-ink">{{ $p->name }}</p><p class="text-xs text-muted tabular">{{ $p->timeRange() }}</p></td>
                            @if ($p->is_break)
                                <td colspan="{{ count($days) }}" class="px-2 py-2 text-center text-xs font-semibold tracking-widest text-muted uppercase">{{ $p->name }}</td>
                            @else
                                @foreach ($days as $d => $dayName)
                                    @php $e = $entries[$d.'-'.$p->id] ?? null; @endphp
                                    <td class="px-2 py-2 align-top">
                                        @if ($e)
                                            <p class="font-medium text-ink">{{ $e->subject?->name ?? '—' }}</p>
                                            <p class="text-xs text-muted">{{ $teacher ? $e->section?->label() : ($e->staff?->name ?? 'No teacher') }}{{ $e->room ? ' · '.$e->room : '' }}</p>
                                        @else
                                            <span class="text-xs text-line-strong">Free</span>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.school>
