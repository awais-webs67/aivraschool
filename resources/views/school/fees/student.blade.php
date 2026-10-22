<x-layouts.school :heading="'Fees · '.$student->name" :subheading="$student->admission_no.' · '.$student->classLabel().($student->father_name ? ' · s/o '.$student->father_name : '')">
    <x-slot:actions>
        <a href="{{ route('school.students.show', $student) }}" class="btn btn-ghost">Student profile</a>
        <a href="{{ route('school.fees.print', ['q' => $student->admission_no, 'status' => 'open']) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print open vouchers</a>
    </x-slot:actions>
    @include('school.fees._nav')

    @if ($receipt = session('receipt'))
        <div class="alert alert-success mb-4"><x-icon name="check-circle" class="mt-px" /><p>Receipt {{ $receipt }} is ready. <a href="{{ route('school.fees.receipt', $receipt) }}" target="_blank" class="font-semibold underline">Print receipt</a></p></div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Vouchers</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($invoices as $inv)
                        <li class="px-5 py-3">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                <div class="min-w-[10rem] flex-1">
                                    <p class="font-medium text-ink">{{ $inv->title }}</p>
                                    <p class="text-xs text-muted">{{ $inv->invoice_no }} · due {{ $inv->due_date->format('d M Y') }} · {{ $inv->items->pluck('description')->join(', ') }}{{ (float) $inv->discount > 0 ? ' · discount '.money($inv->discount) : '' }}{{ (float) $inv->fine > 0 ? ' · late fee '.money($inv->fine) : '' }}</p>
                                </div>
                                <p class="text-right text-sm tabular"><span class="font-semibold text-ink">{{ money($inv->total) }}</span>@if ($inv->balance() > 0 && (float) $inv->paid > 0)<br><span class="text-xs text-danger">{{ money($inv->balance()) }} left</span>@endif</p>
                                <span class="badge {{ $inv->statusBadge() }}">{{ \App\Models\FeeInvoice::STATUSES[$inv->status] }}</span>
                                @if ($inv->status === 'unpaid')
                                    <form method="POST" action="{{ route('school.fees.cancel', $inv) }}" data-confirm="Cancel voucher {{ $inv->invoice_no }}?">@csrf<button class="btn btn-ghost btn-sm" title="Cancel voucher"><x-icon name="x" size="4" /><span class="sr-only">Cancel</span></button></form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-muted">No vouchers yet.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Payments</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($payments as $no => $group)
                        <li><a href="{{ route('school.fees.receipt', $no) }}" target="_blank" class="flex flex-wrap items-center gap-x-4 px-5 py-3 hover:bg-canvas">
                            <div class="min-w-0 flex-1"><p class="font-medium text-ink">{{ $no }}</p><p class="text-xs text-muted">{{ $group->first()->paid_on->format('d M Y') }} · {{ \App\Models\FeePayment::METHODS[$group->first()->method] ?? '' }}</p></div>
                            <p class="font-semibold text-success tabular">{{ money($group->sum('amount')) }}</p>
                            <x-icon name="printer" size="4" class="text-muted" />
                        </a></li>
                    @empty
                        <li class="px-5 py-10 text-center text-sm text-muted">No payments yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('school.fees.collect', $student) }}" class="card">
                @csrf
                <div class="card-header">
                    <h2 class="card-title">Receive payment</h2>
                    <span class="font-display text-xl {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ money($balance) }}</span>
                </div>
                <div class="card-body space-y-4">
                    @if ($balance > 0)
                        <x-form.input name="amount" type="number" min="0.01" step="0.01" label="Amount" :value="$balance" required hint="Goes to the oldest voucher first." />
                        <x-form.select name="method" label="Paid by" :options="\App\Models\FeePayment::METHODS" value="cash" required />
                        <x-form.input name="reference" label="Reference (bank / transaction no.)" maxlength="80" />
                        <x-form.input name="paid_on" type="date" label="Date" :value="today()->toDateString()" required />
                        @if ($lateFee > 0)
                            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="waive_fine" value="1" class="checkbox"> Don't charge the late fee ({{ money($lateFee) }})</label>
                        @endif
                        <button class="btn btn-primary w-full"><x-icon name="banknotes" size="4" /> Save payment</button>
                    @else
                        <p class="text-sm text-muted">Nothing to pay. All vouchers are cleared.</p>
                    @endif
                </div>
            </form>

            @if ($siblings->isNotEmpty())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Siblings</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($siblings as [$sib, $owed])
                            <li><a href="{{ route('school.fees.student', $sib) }}" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-canvas">
                                <span class="min-w-0 flex-1 truncate"><span class="font-medium text-ink">{{ $sib->name }}</span> <span class="text-muted">· {{ $sib->classLabel() }}</span></span>
                                <span class="font-semibold tabular {{ $owed > 0 ? 'text-danger' : 'text-success' }}">{{ money($owed) }}</span>
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <details class="card group">
                <summary class="card-header cursor-pointer list-none"><h2 class="card-title">Make a one-off voucher</h2><x-icon name="plus" size="4" class="transition group-open:rotate-45" /></summary>
                <form method="POST" action="{{ route('school.fees.custom', $student) }}" class="card-body space-y-3">
                    @csrf
                    <x-form.input name="title" label="Title" value="Admission & charges" required maxlength="120" />
                    <x-form.input name="due_date" type="date" label="Due date" :value="today()->addDays(7)->toDateString()" required />
                    @foreach (range(0, 2) as $i)
                        <div class="grid grid-cols-[1fr_7rem] gap-2">
                            <input type="text" name="lines[{{ $i }}][description]" list="fee-heads" class="input text-sm" placeholder="e.g. Admission fee" aria-label="Item {{ $i + 1 }}">
                            <input type="number" name="lines[{{ $i }}][amount]" min="0" step="0.01" class="input text-sm" placeholder="Amount" aria-label="Amount {{ $i + 1 }}">
                        </div>
                    @endforeach
                    @error('lines')<p class="error">{{ $message }}</p>@enderror
                    <datalist id="fee-heads">@foreach ($heads as $h)<option value="{{ $h->name }}">@endforeach</datalist>
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="apply_discount" value="1" class="checkbox"> Take off the student's {{ (float) $student->fee_discount }}% discount</label>
                    <button class="btn btn-secondary w-full">Make voucher</button>
                </form>
            </details>
        </div>
    </div>
</x-layouts.school>
