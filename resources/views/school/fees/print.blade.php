<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Fee vouchers', 'index' => false])
    <style>
        @media print { .no-print { display: none !important; } body { background: #fff !important; } .voucher { break-inside: avoid; box-shadow: none !important; } @page { size: A4 landscape; margin: 8mm; } }
    </style>
</head>
<body class="bg-canvas p-4">
    <div class="no-print mx-auto mb-4 flex max-w-6xl flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-body">{{ $invoices->count() }} vouchers · 3 copies each (bank, school, parent)</p>
        <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" size="4" /> Print</button>
    </div>
    @forelse ($invoices as $inv)
        @php
            $previous = ($arrears[$inv->student_id] ?? collect())->where('id', '!=', $inv->id)->sum(fn ($i) => $i->balance());
            $balance = $inv->balance();
            $late = (float) current_school()->setting('fees.late_fee', 0);
        @endphp
        <div class="voucher mx-auto mb-4 grid max-w-6xl gap-3 bg-white p-3 text-[11px] leading-snug text-ink sm:grid-cols-3">
            @foreach (['Bank copy', 'School copy', 'Parent copy'] as $copy)
                <div class="rounded border border-dashed border-line-strong p-3">
                    <div class="flex items-start justify-between gap-2 border-b border-line pb-2">
                        <div class="min-w-0"><p class="text-xs font-bold break-words">{{ $school->name }}</p><p class="text-muted">{{ $school->address }}</p></div>
                        <span class="shrink-0 rounded bg-ink px-1.5 py-0.5 text-[10px] font-semibold text-white">{{ $copy }}</span>
                    </div>
                    <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-2 gap-y-0.5">
                        <dt class="text-muted">Voucher</dt><dd class="font-mono font-semibold">{{ $inv->invoice_no }}</dd>
                        <dt class="text-muted">Student</dt><dd class="font-semibold">{{ $inv->student?->name }}</dd>
                        <dt class="text-muted">Adm. no.</dt><dd>{{ $inv->student?->admission_no }}</dd>
                        <dt class="text-muted">Class</dt><dd>{{ $inv->student?->classLabel() }}</dd>
                        <dt class="text-muted">Father</dt><dd>{{ $inv->student?->father_name }}</dd>
                        <dt class="text-muted">For</dt><dd>{{ $inv->title }}</dd>
                        <dt class="text-muted">Due date</dt><dd class="font-semibold">{{ $inv->due_date->format('d M Y') }}</dd>
                    </dl>
                    <table class="mt-2 w-full">
                        @foreach ($inv->items as $item)<tr><td>{{ $item->description }}</td><td class="text-right tabular">{{ number_format((float) $item->amount) }}</td></tr>@endforeach
                        @if ((float) $inv->discount > 0)<tr><td>Discount</td><td class="text-right tabular">−{{ number_format((float) $inv->discount) }}</td></tr>@endif
                        @if ((float) $inv->fine > 0)<tr><td>Late fee</td><td class="text-right tabular">{{ number_format((float) $inv->fine) }}</td></tr>@endif
                        @if ((float) $inv->paid > 0)<tr><td>Already paid</td><td class="text-right tabular">−{{ number_format((float) $inv->paid) }}</td></tr>@endif
                        @if ($previous > 0)<tr><td>Previous balance</td><td class="text-right tabular">{{ number_format($previous) }}</td></tr>@endif
                        <tr class="border-t border-ink font-bold"><td class="pt-1">Payable by due date</td><td class="pt-1 text-right tabular">{{ money($balance + $previous) }}</td></tr>
                        @if ($late > 0)<tr class="text-muted"><td>After due date</td><td class="text-right tabular">{{ money($balance + $previous + $late) }}</td></tr>@endif
                    </table>
                    <p class="mt-4 border-t border-line pt-1 text-right text-muted">Signature & stamp</p>
                </div>
            @endforeach
        </div>
    @empty
        <p class="text-center text-sm text-muted">No vouchers match.</p>
    @endforelse
</body>
</html>
