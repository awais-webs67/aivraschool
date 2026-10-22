<x-layouts.school heading="Timetable" subheading="Cover the lessons of teachers who are absent or on leave.">
    @include('school.timetable._nav')
    <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
        <input type="date" name="date" value="{{ $date->toDateString() }}" class="input w-auto" aria-label="Date" onchange="this.form.submit()">
        <span class="text-sm font-medium text-ink">{{ $date->format('l, d M Y') }}</span>
    </form>

    @if ($absentStaff->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No teacher is absent or on leave on this day. Mark staff attendance or approve leave first.</div>
    @else
        <p class="mb-3 text-sm text-body">Away: <span class="font-semibold text-ink">{{ $absentStaff->pluck('name')->join(', ') }}</span></p>
        @if ($lessons->isEmpty())
            <div class="card px-4 py-14 text-center text-sm text-muted">They have no lessons on {{ $date->format('l') }}.</div>
        @else
            <form method="POST" action="{{ route('school.timetable.substitutions.store') }}" class="card">
                @csrf
                <input type="hidden" name="date" value="{{ $date->toDateString() }}">
                <ul class="divide-y divide-line">
                    @foreach ($lessons as $e)
                        <li class="grid items-center gap-2 px-5 py-3 md:grid-cols-[1fr_16rem]">
                            <div class="min-w-0">
                                <p class="font-medium text-ink">{{ $e->period?->name }} · {{ $e->section?->label() }} · {{ $e->subject?->name ?? 'Lesson' }}</p>
                                <p class="text-xs text-muted">{{ $e->period?->timeRange() }} · usually {{ $e->staff?->name }}</p>
                            </div>
                            <select name="sub[{{ $e->id }}]" class="input py-1.5 text-sm" aria-label="Substitute for {{ $e->period?->name }}">
                                <option value="">No cover</option>
                                @php $current = $existing[$e->id]->substitute_staff_id ?? null; @endphp
                                @if ($current && ! isset($free[$e->id][$current]))<option value="{{ $current }}" selected>{{ $existing[$e->id]->substitute?->name }}</option>@endif
                                @foreach ($free[$e->id] as $id => $name)<option value="{{ $id }}" @selected($current === $id)>{{ $name }} (free)</option>@endforeach
                            </select>
                        </li>
                    @endforeach
                </ul>
                <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Save substitutions</button></div>
            </form>
        @endif
    @endif
</x-layouts.school>
