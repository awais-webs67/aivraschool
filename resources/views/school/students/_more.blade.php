{{-- Extra cards on the student profile, one per module the user can see. --}}
@php $viewer = auth('school')->user(); @endphp

@if ($viewer->allows('fees.manage'))
    @php
        $owed = \App\Services\School\FeeService::balanceOf($student);
        $lastPaid = \App\Models\FeePayment::query()->where('student_id', $student->id)->latest('paid_on')->first();
    @endphp
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Fees</h2>
            <a href="{{ route('school.fees.student', $student) }}" class="btn btn-secondary btn-sm"><x-icon name="banknotes" size="4" /> Open fees</a>
        </div>
        <div class="card-body flex flex-wrap items-end gap-x-8 gap-y-3">
            <div><p class="font-display text-2xl {{ $owed > 0 ? 'text-danger' : 'text-success' }}">{{ money($owed) }}</p><p class="text-xs text-muted">Balance due</p></div>
            <div><p class="font-medium text-ink">{{ $lastPaid ? money($lastPaid->amount).' on '.$lastPaid->paid_on->format('d M Y') : '—' }}</p><p class="text-xs text-muted">Last payment</p></div>
            @if ((float) $student->fee_discount > 0)<div><p class="font-medium text-ink">{{ (float) $student->fee_discount }}%</p><p class="text-xs text-muted">Fee discount</p></div>@endif
        </div>
    </section>
@endif

@if ($viewer->allows('marks.enter') && $student->section)
    @php
        $exams = \App\Models\Exam::query()->whereHas('papers', fn ($q) => $q->where('class_id', $student->class_id))->latest('starts_on')->latest('id')->limit(4)->get();
        $results = $exams->map(fn ($e) => [$e, app(\App\Services\School\ResultService::class)->forStudent($e, $student)]);
    @endphp
    @if ($results->isNotEmpty())
        <section class="card">
            <div class="card-header"><h2 class="card-title">Exam results</h2></div>
            <ul class="divide-y divide-line">
                @foreach ($results as [$exam, $res])
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-sm">
                        <span class="min-w-0 basis-full font-medium text-ink sm:basis-auto sm:flex-1">{{ $exam->name }}</span>
                        @if ($res['row'] && $res['row']['percent'] !== null)
                            <span class="tabular">{{ $res['row']['percent'] }}%</span>
                            <span class="badge badge-brand">{{ $res['row']['grade'] }}</span>
                            <span class="text-muted">position {{ $res['row']['position'] }}/{{ $res['classSize'] }}</span>
                            <a href="{{ route('school.exams.report-cards', [$exam, $student->section_id, 'student' => $student->id]) }}" target="_blank" class="btn btn-ghost btn-sm" title="Report card"><x-icon name="printer" size="4" /><span class="sr-only">Report card</span></a>
                        @else
                            <span class="text-muted">No marks yet</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endif

@if ($viewer->allows('discipline'))
    @php $records = \App\Models\Incident::query()->where('student_id', $student->id)->latest('date')->limit(5)->get(); @endphp
    <section class="card">
        <div class="card-header">
            <h2 class="card-title">Behaviour</h2>
            <a href="{{ route('school.discipline.index', ['student' => $student->id]) }}" class="btn btn-secondary btn-sm"><x-icon name="plus" size="4" /> Add / view all</a>
        </div>
        <ul class="divide-y divide-line">
            @forelse ($records as $r)
                <li class="flex flex-wrap items-center gap-x-3 px-5 py-2.5 text-sm">
                    <span class="badge {{ $r->badge() }}">{{ $r->kind === 'merit' ? 'Merit' : \App\Models\Incident::SEVERITIES[$r->severity] }}</span>
                    <span class="min-w-0 flex-1 truncate text-ink">{{ $r->category }}: {{ $r->description }}</span>
                    <span class="text-xs text-muted">{{ $r->date->format('d M Y') }}</span>
                </li>
            @empty
                <li class="px-5 py-4 text-sm text-muted">Nothing recorded.</li>
            @endforelse
        </ul>
    </section>
@endif
