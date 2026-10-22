<x-layouts.school heading="Fees" :subheading="'Collection for '.$month->format('F Y').'.'">
    <x-slot:actions>
        <form method="GET" action="{{ route('school.fees.search') }}" class="flex gap-2" role="search">
            <input type="search" name="q" class="input w-56" placeholder="Student name or admission no." aria-label="Find student" required>
            <button class="btn btn-primary"><x-icon name="banknotes" size="4" /> Collect</button>
        </form>
    </x-slot:actions>
    @include('school.fees._nav')

    @unless ($hasSetup)
        <div class="alert alert-info mb-6"><x-icon name="info" class="mt-px" /><p>Start by setting the fee for each class. <a href="{{ route('school.fees.setup') }}" class="font-semibold underline">Open fee structure</a></p></div>
    @endunless

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <x-stat label="Collected today" :value="money($collectedToday)" icon="banknotes" tone="success" />
        <x-stat label="Collected this month" :value="money($collectedMonth)" icon="chart" tone="brand" :hint="$billed > 0 ? round($collectedMonth / $billed * 100).'% of '.money($billed).' billed' : null" />
        <x-stat label="Still to collect" :value="money($outstanding)" icon="clock" tone="warning" />
        <x-stat label="Defaulters" :value="number_format($defaulters)" icon="alert" tone="danger" hint="students past the due date" />
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Latest payments</h2></div>
            <ul class="divide-y divide-line">
                @forelse ($recent as $p)
                    <li><a href="{{ route('school.fees.receipt', $p->receipt_no) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 hover:bg-canvas">
                        <div class="min-w-[10rem] flex-1"><p class="truncate font-medium text-ink">{{ $p->student?->name }}</p><p class="text-xs text-muted">{{ $p->receipt_no }} · {{ $p->paid_on->format('d M Y') }} · {{ \App\Models\FeePayment::METHODS[$p->method] ?? $p->method }}</p></div>
                        <p class="font-semibold text-success tabular">{{ money($p->amount) }}</p>
                    </a></li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-muted">No payments yet.</li>
                @endforelse
            </ul>
        </section>
        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">This month's vouchers</h2></div>
            <div class="card-body text-sm">
                <p class="text-body"><span class="font-display text-2xl text-ink">{{ $generated }}</span> of {{ $students }} students have a voucher for {{ $month->format('F') }}.</p>
                <a href="{{ route('school.fees.generate') }}" class="btn btn-primary mt-4 w-full">Make {{ $month->format('F') }} vouchers</a>
                <a href="{{ route('school.fees.print', ['month' => $month->format('Y-m')]) }}" target="_blank" class="btn btn-secondary mt-2 w-full"><x-icon name="printer" size="4" /> Print vouchers</a>
            </div>
        </section>
    </div>
</x-layouts.school>
