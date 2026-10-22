<x-layouts.school :heading="'Result sheet · '.$section->label()" :subheading="$exam->name">
    <x-slot:actions>
        <a href="{{ route('school.exams.show', $exam) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
        <a href="{{ route('school.exams.report-cards', [$exam, $section]) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" size="4" /> Report cards</a>
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print sheet</button>
    </x-slot:actions>

    @if ($papers->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">No papers for this class in this exam.</div>
    @else
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[40rem] text-sm">
                <thead>
                    <tr class="border-b border-line bg-canvas text-left text-xs text-muted">
                        <th class="px-3 py-2.5 font-semibold">Pos.</th>
                        <th class="px-3 py-2.5 font-semibold">Student</th>
                        @foreach ($papers as $p)<th class="px-2 py-2.5 text-center font-semibold">{{ $p->subject?->name }}<span class="block font-normal">/{{ (float) $p->max_marks }}</span></th>@endforeach
                        <th class="px-2 py-2.5 text-center font-semibold">Total</th>
                        <th class="px-2 py-2.5 text-center font-semibold">%</th>
                        <th class="px-2 py-2.5 text-center font-semibold">Grade</th>
                        <th class="px-2 py-2.5 text-center font-semibold">Result</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows->sortBy(fn ($r) => $r['position'] ?? PHP_INT_MAX) as $r)
                        <tr class="border-b border-line">
                            <td class="px-3 py-2 font-semibold text-ink tabular">{{ $r['position'] ?? '—' }}</td>
                            <td class="px-3 py-2"><a href="{{ route('school.exams.report-cards', [$exam, $section, 'student' => $r['student']->id]) }}" target="_blank" class="font-medium text-ink hover:text-brand-text">{{ $r['student']->name }}</a></td>
                            @foreach ($papers as $p)
                                @php $s = $r['subjects'][$p->id]; @endphp
                                <td class="px-2 py-2 text-center tabular {{ $s['marks'] !== null && ! $s['passed'] ? 'font-semibold text-danger' : '' }}">{{ $s['absent'] ? 'Abs' : ($s['marks'] !== null ? (float) $s['marks'] : '—') }}</td>
                            @endforeach
                            <td class="px-2 py-2 text-center font-semibold tabular">{{ (float) $r['obtained'] }}/{{ (float) $r['max'] }}</td>
                            <td class="px-2 py-2 text-center tabular">{{ $r['percent'] ?? '—' }}</td>
                            <td class="px-2 py-2 text-center font-semibold">{{ $r['grade'] }}</td>
                            <td class="px-2 py-2 text-center">@if ($r['percent'] !== null)<span class="badge {{ $r['passed'] ? 'badge-success' : 'badge-danger' }}">{{ $r['passed'] ? 'Pass' : 'Fail' }}</span>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @php $done = $rows->filter(fn ($r) => $r['percent'] !== null); @endphp
        @if ($done->isNotEmpty())
            <p class="mt-3 text-sm text-body">Class average <span class="font-semibold text-ink">{{ round($done->avg('percent'), 1) }}%</span> · passed <span class="font-semibold text-ink">{{ $done->where('passed', true)->count() }}/{{ $done->count() }}</span></p>
        @endif
    @endif
</x-layouts.school>
