<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Report cards · '.$section->label(), 'index' => false])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .card-page { break-after: page; box-shadow: none !important; border: 0 !important; margin: 0 !important; } @page { size: A4; margin: 12mm; } }</style>
</head>
<body class="bg-canvas p-4">
    <div class="no-print mx-auto mb-4 flex max-w-3xl items-center justify-between gap-2">
        <p class="text-sm text-body">{{ $rows->count() }} report {{ \Illuminate\Support\Str::plural('card', $rows->count()) }}</p>
        <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" size="4" /> Print</button>
    </div>
    @forelse ($rows as $r)
        @php $att = $attendance[$r['student']->id] ?? null; @endphp
        <article class="card-page mx-auto mb-6 max-w-3xl rounded-lg border border-line bg-white p-6 text-sm text-ink shadow-card sm:p-8">
            <header class="flex items-center gap-4 border-b-2 border-ink pb-4">
                <x-school-badge :school="$school" size="lg" />
                <div class="min-w-0 flex-1">
                    <h1 class="text-xl font-bold break-words">{{ $school->name }}</h1>
                    <p class="text-xs text-muted">{{ collect([$school->address, $school->phone])->filter()->join(' · ') }}</p>
                </div>
                <div class="text-right"><p class="text-xs text-muted uppercase">Report card</p><p class="font-semibold">{{ $exam->name }}</p></div>
            </header>
            <dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-1 sm:grid-cols-3">
                <div><dt class="text-xs text-muted">Student</dt><dd class="font-semibold">{{ $r['student']->name }}</dd></div>
                <div><dt class="text-xs text-muted">Father</dt><dd>{{ $r['student']->father_name ?? '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Class</dt><dd>{{ $section->label() }}</dd></div>
                <div><dt class="text-xs text-muted">Roll no.</dt><dd>{{ $r['student']->roll_no ?? '—' }}</dd></div>
                <div><dt class="text-xs text-muted">Admission no.</dt><dd>{{ $r['student']->admission_no }}</dd></div>
                <div><dt class="text-xs text-muted">Attendance</dt><dd>{{ $att && $att['percent'] !== null ? $att['present'].'/'.$att['total'].' days ('.$att['percent'].'%)' : '—' }}</dd></div>
            </dl>
            <table class="mt-5 w-full">
                <thead><tr class="border-y border-ink bg-canvas text-left text-xs"><th class="px-2 py-2">Subject</th><th class="px-2 py-2 text-center">Max</th><th class="px-2 py-2 text-center">Obtained</th><th class="px-2 py-2 text-center">%</th><th class="px-2 py-2 text-center">Grade</th><th class="px-2 py-2">Remarks</th></tr></thead>
                <tbody>
                    @foreach ($papers as $p)
                        @php $s = $r['subjects'][$p->id]; @endphp
                        <tr class="border-b border-line">
                            <td class="px-2 py-1.5 font-medium">{{ $p->subject?->name }}</td>
                            <td class="px-2 py-1.5 text-center tabular">{{ (float) $p->max_marks }}</td>
                            <td class="px-2 py-1.5 text-center tabular {{ $s['marks'] !== null && ! $s['passed'] ? 'font-bold text-danger' : '' }}">{{ $s['absent'] ? 'Absent' : ($s['marks'] !== null ? (float) $s['marks'] : '—') }}</td>
                            <td class="px-2 py-1.5 text-center tabular">{{ $s['percent'] ?? '—' }}</td>
                            <td class="px-2 py-1.5 text-center">{{ \App\Models\Exam::gradeFor($s['percent']) }}</td>
                            <td class="px-2 py-1.5 text-xs">{{ $s['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-b-2 border-ink font-bold"><td class="px-2 py-2">Total</td><td class="px-2 py-2 text-center tabular">{{ (float) $r['max'] }}</td><td class="px-2 py-2 text-center tabular">{{ (float) $r['obtained'] }}</td><td class="px-2 py-2 text-center tabular">{{ $r['percent'] ?? '—' }}</td><td class="px-2 py-2 text-center">{{ $r['grade'] }}</td><td></td></tr>
                </tfoot>
            </table>
            <div class="mt-4 flex flex-wrap gap-x-8 gap-y-2">
                <p>Result: <span class="font-bold {{ $r['passed'] ? 'text-success' : 'text-danger' }}">{{ $r['percent'] === null ? '—' : ($r['passed'] ? 'PASS' : 'FAIL') }}</span></p>
                <p>Position: <span class="font-bold">{{ $r['position'] ? $r['position'].' of '.$classSize : '—' }}</span></p>
                @if ($r['failed'])<p class="text-xs text-danger">Needs improvement in: {{ implode(', ', $r['failed']) }}</p>@endif
            </div>
            <footer class="mt-16 grid grid-cols-3 gap-6 text-center text-xs text-muted">
                <p class="border-t border-line pt-1">Class teacher{{ $section->classTeacher ? ' · '.$section->classTeacher->name : '' }}</p>
                <p class="border-t border-line pt-1">Principal</p>
                <p class="border-t border-line pt-1">Parent</p>
            </footer>
        </article>
    @empty
        <p class="text-center text-sm text-muted">No students.</p>
    @endforelse
</body>
</html>
