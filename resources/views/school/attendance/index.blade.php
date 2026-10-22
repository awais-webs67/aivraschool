@php $pct = $totals['marked'] ? round($totals['attended'] / $totals['marked'] * 100) : null; @endphp
<x-layouts.school heading="Attendance" subheading="Tap a class to mark today's attendance.">
    @include('school.attendance._tabs')

    <div class="mb-6">@include('school.attendance._date', ['route' => 'school.attendance.index'])</div>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Students" :value="number_format($totals['students'])" icon="users" tone="neutral" />
        <x-stat label="Marked" :value="number_format($totals['marked'])" icon="check" tone="info" :hint="$sections->count().' classes'" />
        <x-stat label="Present" :value="$pct !== null ? $pct.'%' : '—'" icon="check-circle" tone="success" />
        <x-stat label="Absent" :value="number_format($totals['absent'])" icon="x" tone="danger" />
    </div>

    <div class="card mt-6">
        @if ($sections->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">{{ auth('school')->user()->allows('attendance.all') ? 'Add classes and sections first.' : 'You are not the class teacher of any section yet. Ask the principal.' }}</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($sections as $section)
                    @php
                        $m = $marked[$section->id] ?? null;
                        $n = (int) ($strength[$section->id] ?? 0);
                    @endphp
                    <li>
                        <a href="{{ route('school.attendance.mark', [$section, 'date' => $date->toDateString()]) }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3.5 transition hover:bg-canvas">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full {{ $m ? 'bg-success-tint text-success' : 'bg-chip text-muted' }}"><x-icon :name="$m ? 'check' : 'clock'" size="5" /></span>
                            <div class="min-w-[9rem] flex-1">
                                <p class="font-semibold text-ink">{{ $section->label() }}</p>
                                <p class="text-xs text-muted">{{ $n }} students{{ $section->classTeacher ? ' · '.$section->classTeacher->name : '' }}</p>
                            </div>
                            @if ($m)
                                <span class="text-sm text-body">{{ $m->attended }}/{{ $m->total }} present</span>
                                <span class="badge {{ $m->total && $m->attended / $m->total < 0.75 ? 'badge-warning' : 'badge-success' }}">{{ $m->total ? round($m->attended / $m->total * 100) : 0 }}%</span>
                            @else
                                <span class="badge badge-neutral">Not marked</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.school>
