<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Front office">
    @foreach (['school.visitors.index' => 'Visitors', 'school.visitors.complaints' => 'Complaints & suggestions', 'school.enquiries.index' => 'Admission enquiries'] as $route => $label)
        @continue($route === 'school.enquiries.index' && ! auth('school')->user()->allows('admissions'))
        <a href="{{ route($route) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap {{ request()->routeIs($route) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
    @endforeach
</nav>
