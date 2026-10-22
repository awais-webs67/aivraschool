<x-layouts.school :heading="'Register · '.$section->label()" :subheading="$month->format('F Y')">
    <x-slot:actions>
        <a href="{{ route('school.attendance.mark', $section) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Mark today</a>
        <a href="{{ route('school.attendance.register', [$section, 'month' => $month->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-secondary" aria-label="Previous month"><x-icon name="arrow-left" size="4" /></a>
        @if ($month->lt(today()->startOfMonth()))
            <a href="{{ route('school.attendance.register', [$section, 'month' => $month->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-secondary" aria-label="Next month"><x-icon name="arrow-right" size="4" /></a>
        @endif
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print</button>
    </x-slot:actions>

    <div class="card">
        @if ($students->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No students in this section.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-line bg-canvas text-muted">
                            <th class="sticky left-0 z-10 bg-canvas px-3 py-2 text-left font-semibold">Student</th>
                            @foreach ($days as $d)
                                <th class="w-7 px-0.5 py-2 text-center font-semibold {{ $d->isSunday() ? 'text-danger' : '' }}">{{ $d->day }}</th>
                            @endforeach
                            <th class="px-2 py-2 text-center font-semibold">P</th>
                            <th class="px-2 py-2 text-center font-semibold">A</th>
                            <th class="px-2 py-2 text-center font-semibold">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $s)
                            @php $sum = $summary[$s->id]; @endphp
                            <tr class="border-b border-line">
                                <td class="sticky left-0 z-10 max-w-[10rem] truncate bg-white px-3 py-1.5 font-medium text-ink">{{ $s->roll_no ? $s->roll_no.'. ' : '' }}{{ $s->name }}</td>
                                @foreach ($days as $d)
                                    @php $r = $records[$s->id][$d->day] ?? null; @endphp
                                    <td class="px-0.5 py-1.5 text-center {{ $d->isSunday() ? 'bg-canvas' : '' }} {{ $r ? ['present' => 'text-success', 'absent' => 'font-bold text-danger', 'late' => 'text-warning', 'leave' => 'text-info', 'half_day' => 'text-warning'][$r->status] : 'text-line-strong' }}">{{ $r ? \App\Models\StudentAttendance::SHORT[$r->status] : '·' }}</td>
                                @endforeach
                                <td class="px-2 text-center tabular">{{ $sum['present'] }}</td>
                                <td class="px-2 text-center tabular">{{ $sum['absent'] }}</td>
                                <td class="px-2 text-center font-semibold tabular {{ $sum['percent'] !== null && $sum['percent'] < 75 ? 'text-danger' : 'text-ink' }}">{{ $sum['percent'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="border-t border-line px-5 py-3 text-xs text-muted">P present · A absent · L late · Lv leave · H half day. Below 75% is shown in red.</p>
        @endif
    </div>
</x-layouts.school>
