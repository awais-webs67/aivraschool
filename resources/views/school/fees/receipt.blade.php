@php $first = $payments->first(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'Receipt '.$first->receipt_no, 'index' => false])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .sheet { box-shadow: none !important; border: 0 !important; } @page { margin: 10mm; } }</style>
</head>
<body class="bg-canvas p-4">
    <div class="no-print mx-auto mb-4 flex max-w-md justify-between gap-2">
        <a href="{{ route('school.fees.student', $student) }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
        <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" size="4" /> Print</button>
    </div>
    <main class="sheet mx-auto max-w-md rounded-lg border border-line bg-white p-6 text-sm text-ink shadow-card">
        <div class="text-center">
            <x-school-badge :school="$school" class="mx-auto" />
            <p class="mt-2 font-bold break-words">{{ $school->name }}</p>
            <p class="text-xs text-muted">{{ collect([$school->address, $school->phone])->filter()->join(' · ') }}</p>
            <p class="mt-3 inline-block rounded bg-ink px-2 py-0.5 text-xs font-semibold text-white">FEE RECEIPT</p>
        </div>
        <dl class="mt-4 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
            <dt class="text-muted">Receipt no.</dt><dd class="font-mono font-semibold">{{ $first->receipt_no }}</dd>
            <dt class="text-muted">Date</dt><dd>{{ $first->paid_on->format('d M Y') }}</dd>
            <dt class="text-muted">Student</dt><dd class="font-semibold">{{ $student->name }} ({{ $student->admission_no }})</dd>
            <dt class="text-muted">Class</dt><dd>{{ $student->classLabel() }}</dd>
            <dt class="text-muted">Paid by</dt><dd>{{ \App\Models\FeePayment::METHODS[$first->method] ?? $first->method }}{{ $first->reference ? ' · '.$first->reference : '' }}</dd>
        </dl>
        <table class="mt-4 w-full">
            <thead><tr class="border-y border-line text-left text-xs text-muted"><th class="py-1.5">For</th><th class="py-1.5 text-right">Amount</th></tr></thead>
            <tbody>
                @foreach ($payments as $p)
                    <tr class="border-b border-line"><td class="py-1.5">{{ $p->invoice?->title }} <span class="text-xs text-muted">{{ $p->invoice?->invoice_no }}</span></td><td class="py-1.5 text-right tabular">{{ money($p->amount, true) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="font-bold"><td class="pt-2">Total received</td><td class="pt-2 text-right tabular">{{ money($payments->sum('amount'), true) }}</td></tr>
                <tr class="text-muted"><td>Balance still due</td><td class="text-right tabular">{{ money($balance, true) }}</td></tr>
            </tfoot>
        </table>
        <p class="mt-8 flex justify-between text-xs text-muted"><span>Received by {{ $first->receiver?->name ?? '—' }}</span><span>Signature</span></p>
    </main>
</body>
</html>
