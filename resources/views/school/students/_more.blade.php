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

@stack('student-more')
