@if (auth('school')->user()->allows('staff.attendance'))
    <nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Attendance">
        @foreach (['school.attendance.index' => 'Students', 'school.attendance.staff' => 'Staff'] as $route => $label)
            <a href="{{ route($route, request()->only('date')) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold {{ request()->routeIs($route) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </nav>
@endif
