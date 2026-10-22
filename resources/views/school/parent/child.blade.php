<x-layouts.school :heading="$student->name" :subheading="$student->classLabel().($student->section?->classTeacher ? ' · Class teacher: '.$student->section->classTeacher->name : '')">
    <x-slot:actions><a href="{{ route('school.parent.home') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> My children</a></x-slot:actions>

    <nav class="sticky top-16 z-10 -mx-4 mb-6 flex gap-1 overflow-x-auto border-b border-line bg-canvas/95 px-4 backdrop-blur sm:mx-0 sm:px-0" aria-label="Sections">
        @foreach (['attendance' => 'Attendance', 'fees' => 'Fees', 'results' => 'Results', 'homework' => 'Homework', 'timetable' => 'Timetable'] as $id => $label)
            <a href="#{{ $id }}" class="shrink-0 border-b-2 border-transparent px-3 py-2.5 text-sm font-semibold text-muted hover:border-brand hover:text-ink">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="space-y-6">
        <section id="attendance" class="card scroll-mt-32">
            <div class="card-header"><h2 class="card-title">Attendance</h2><span class="text-xs text-muted">this month</span></div>
            <div class="card-body grid grid-cols-2 gap-4 text-center sm:grid-cols-4">
                <div><p class="font-display text-2xl {{ $month['percent'] !== null && $month['percent'] < 75 ? 'text-danger' : 'text-ink' }}">{{ $month['percent'] !== null ? $month['percent'].'%' : '—' }}</p><p class="text-xs text-muted">Attendance</p></div>
                <div><p class="font-display text-2xl text-success">{{ $month['present'] }}</p><p class="text-xs text-muted">Present</p></div>
                <div><p class="font-display text-2xl text-danger">{{ $month['absent'] }}</p><p class="text-xs text-muted">Absent</p></div>
                <div><p class="font-display text-2xl text-info">{{ $month['leave'] }}</p><p class="text-xs text-muted">Leave</p></div>
            </div>
            @if ($recentAttendance->isNotEmpty())
                <div class="flex flex-wrap gap-1.5 border-t border-line px-5 py-4">
                    @foreach ($recentAttendance as $r)
                        <span class="badge {{ \App\Models\StudentAttendance::badge($r->status) }}" title="{{ \App\Models\StudentAttendance::STATUSES[$r->status] }}">{{ $r->date->format('d M') }} · {{ \App\Models\StudentAttendance::SHORT[$r->status] }}</span>
                    @endforeach
                </div>
            @endif
        </section>

        <section id="fees" class="card scroll-mt-32">
            <div class="card-header flex-wrap gap-2">
                <h2 class="card-title">Fees</h2>
                <div class="flex items-center gap-3">
                    <span class="font-display text-xl {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ $balance > 0 ? money($balance).' due' : 'All paid' }}</span>
                    @if ($balance > 0)<a href="{{ route('school.parent.vouchers', $student) }}" target="_blank" class="btn btn-secondary btn-sm"><x-icon name="printer" size="4" /> Print voucher</a>@endif
                </div>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($invoices as $inv)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                        <span class="min-w-0 basis-full sm:basis-auto sm:flex-1"><span class="font-medium text-ink">{{ $inv->title }}</span> <span class="text-xs text-muted">· due {{ $inv->due_date->format('d M Y') }}</span></span>
                        <span class="font-semibold tabular">{{ money($inv->total) }}</span>
                        <span class="badge {{ $inv->statusBadge() }}">{{ \App\Models\FeeInvoice::STATUSES[$inv->status] }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">No fee vouchers yet.</li>
                @endforelse
            </ul>
        </section>

        <section id="results" class="card scroll-mt-32">
            <div class="card-header"><h2 class="card-title">Results</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($results as [$exam, $res])
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                        <span class="min-w-0 basis-full font-medium text-ink sm:basis-auto sm:flex-1">{{ $exam->name }}</span>
                        @if ($res['row'] && $res['row']['percent'] !== null)
                            <span class="tabular">{{ $res['row']['percent'] }}%</span>
                            <span class="badge badge-brand">{{ $res['row']['grade'] }}</span>
                            <span class="badge {{ $res['row']['passed'] ? 'badge-success' : 'badge-danger' }}">{{ $res['row']['passed'] ? 'Pass' : 'Fail' }}</span>
                            <a href="{{ route('school.parent.report-card', [$student, $exam]) }}" target="_blank" class="btn btn-secondary btn-sm">Report card</a>
                        @else
                            <span class="text-muted">Result not ready</span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-muted">No results published yet.</li>
                @endforelse
            </ul>
        </section>

        <section id="homework" class="card scroll-mt-32">
            <div class="card-header"><h2 class="card-title">Homework & diary</h2><span class="text-xs text-muted">last 2 weeks</span></div>
            <div class="divide-y divide-line">
                @forelse ($homework as $date => $items)
                    <div class="px-5 py-3">
                        <p class="text-xs font-semibold tracking-wide text-muted uppercase">{{ \Illuminate\Support\Carbon::parse($date)->format('l, d M') }}</p>
                        <ul class="mt-1 space-y-1.5">
                            @foreach ($items as $h)
                                <li class="text-sm">
                                    <span class="font-medium text-brand-text">{{ $h->subject?->name ?? 'General' }}:</span> <span class="text-ink">{{ $h->title }}</span>
                                    @if ($h->details)<p class="text-body whitespace-pre-line">{{ $h->details }}</p>@endif
                                    @if ($h->due_date)<p class="text-xs text-muted">Hand in by {{ $h->due_date->format('d M') }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-muted">No homework in the last two weeks.</p>
                @endforelse
            </div>
        </section>

        <section id="timetable" class="card scroll-mt-32 overflow-x-auto">
            <div class="card-header"><h2 class="card-title">Timetable</h2></div>
            @if ($timetable->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">The timetable is not ready yet.</p>
            @else
                <table class="w-full min-w-[40rem] text-sm">
                    <thead><tr class="border-b border-line bg-canvas text-left text-xs text-muted"><th class="px-3 py-2 font-semibold">Period</th>@foreach ($days as $d => $name)<th class="px-2 py-2 font-semibold">{{ $name }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($periods->where('is_break', false) as $p)
                            <tr class="border-b border-line">
                                <td class="px-3 py-2 text-xs"><span class="font-semibold text-ink">{{ $p->name }}</span><br><span class="text-muted">{{ $p->timeRange() }}</span></td>
                                @foreach ($days as $d => $name)
                                    @php $e = $timetable[$d.'-'.$p->id] ?? null; @endphp
                                    <td class="px-2 py-2">{{ $e?->subject?->name ?? '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</x-layouts.school>
