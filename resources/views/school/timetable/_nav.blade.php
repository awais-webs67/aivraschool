@if (auth('school')->user()->allows('timetable.manage'))
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Timetable">
        @foreach (['school.timetable.index' => 'Timetable', 'school.timetable.substitutions' => 'Substitutions', 'school.timetable.periods' => 'Periods & days'] as $route => $label)
            <a href="{{ route($route) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap {{ request()->routeIs($route) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </nav>
@endif
