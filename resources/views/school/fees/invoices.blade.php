<x-layouts.school heading="Fees" subheading="Every voucher. Filter, then print the ones you need.">
    <x-slot:actions>
        <a href="{{ route('school.fees.print', request()->query()) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print these</a>
    </x-slot:actions>
    @include('school.fees._nav')

    <form method="GET" class="card mb-4 grid gap-3 p-4 md:grid-cols-[1fr_auto_auto_auto_auto]">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Voucher no., student or admission no." class="input" aria-label="Search">
        <input type="month" name="month" value="{{ $filters['month'] ?? '' }}" class="input md:w-40" aria-label="Month">
        @include('school.partials.section-select', ['name' => 'section_id', 'groups' => $groups, 'value' => $filters['section_id'] ?? null, 'placeholder' => 'All classes', 'class' => 'md:w-44'])
        <select name="status" class="input md:w-36" aria-label="Status">
            <option value="">Any status</option>
            <option value="open" @selected(($filters['status'] ?? '') === 'open')>Not fully paid</option>
            @foreach (\App\Models\FeeInvoice::STATUSES as $v => $l)<option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>@endforeach
        </select>
        <button class="btn btn-secondary">Filter</button>
    </form>

    <div class="card">
        @if ($invoices->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No vouchers found. <a href="{{ route('school.fees.generate') }}" class="font-semibold text-brand-text underline">Make vouchers</a></p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($invoices as $inv)
                    <li><a href="{{ route('school.fees.student', $inv->student_id) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 hover:bg-canvas">
                        <div class="min-w-[11rem] flex-1">
                            <p class="truncate font-medium text-ink">{{ $inv->student?->name }}</p>
                            <p class="truncate text-xs text-muted">{{ $inv->invoice_no }} · {{ $inv->title }} · {{ $inv->student?->classLabel() }}</p>
                        </div>
                        <p class="text-xs {{ $inv->isOverdue() ? 'font-semibold text-danger' : 'text-muted' }}">due {{ $inv->due_date->format('d M') }}</p>
                        <p class="w-28 text-right font-semibold tabular text-ink">{{ money($inv->total) }}</p>
                        <span class="badge {{ $inv->statusBadge() }}">{{ \App\Models\FeeInvoice::STATUSES[$inv->status] }}</span>
                    </a></li>
                @endforeach
            </ul>
            @if ($invoices->hasPages())<div class="border-t border-line px-4 py-3">{{ $invoices->links() }}</div>@endif
        @endif
    </div>
</x-layouts.school>
