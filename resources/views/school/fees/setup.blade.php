<x-layouts.school heading="Fees" subheading="Set the fee of each class once. Monthly vouchers are made from this.">
    @include('school.fees._nav')

    @if ($classes->isEmpty())
        <div class="card px-4 py-14 text-center text-sm text-muted">Add classes first. <a href="{{ route('school.setup.classes') }}" class="font-semibold text-brand-text underline">Add classes</a></div>
    @else
        <form method="POST" action="{{ route('school.fees.setup.save') }}" class="space-y-6">
            @csrf
            @method('PUT')
            <section class="card">
                <div class="card-header"><div><h2 class="card-title">Fee per class ({{ current_school()->currencyCode() }})</h2><p class="mt-0.5 text-sm text-muted">Leave a box empty if that fee does not apply to the class.</p></div></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-sm">
                        <thead>
                            <tr class="border-b border-line bg-canvas text-left text-xs text-muted">
                                <th class="sticky left-0 bg-canvas px-4 py-2.5 font-semibold">Class</th>
                                @foreach ($heads as $head)
                                    <th class="px-2 py-2.5 font-semibold"><span class="block text-ink">{{ $head->name }}</span><span class="font-normal">{{ ['monthly' => 'monthly', 'yearly' => 'yearly', 'once' => 'one time'][$head->frequency] }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($classes as $class)
                                <tr class="border-b border-line">
                                    <td class="sticky left-0 bg-white px-4 py-2 font-medium whitespace-nowrap text-ink">{{ $class->name }}</td>
                                    @foreach ($heads as $head)
                                        <td class="px-2 py-1.5"><input type="number" min="0" step="0.01" name="amount[{{ $class->id }}][{{ $head->id }}]" value="{{ $amounts[$class->id.'-'.$head->id] ?? '' }}" class="input w-28 py-1.5 text-sm tabular" aria-label="{{ $head->name }} for {{ $class->name }}"></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Due date & late fee</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.input name="due_day" type="number" min="1" max="28" label="Fee is due on day" :value="$dueDay" required hint="of every month, e.g. 10" />
                    <x-form.input name="late_fee" type="number" min="0" step="0.01" label="Late fee ({{ current_school()->currencyCode() }})" :value="$lateFee ?: null" hint="Added once when an overdue voucher is paid. Empty = no late fee." />
                </div>
            </section>
            <div class="flex justify-end"><button class="btn btn-primary">Save fee structure</button></div>
        </form>
    @endif

    <section class="card mt-6">
        <div class="card-header"><h2 class="card-title">Fee types</h2></div>
        <ul class="divide-y divide-line">
            @foreach ($heads as $head)
                <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                    <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $head->name }}</span>
                    <span class="text-muted">{{ \App\Models\FeeHead::FREQUENCIES[$head->frequency] }}</span>
                    <form method="POST" action="{{ route('school.fees.heads.destroy', $head) }}" data-confirm="Remove {{ $head->name }}?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Remove {{ $head->name }}"><x-icon name="trash" size="4" /></button></form>
                </li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('school.fees.heads.store') }}" class="grid gap-3 border-t border-line p-5 sm:grid-cols-[1fr_auto_auto] sm:items-end">
            @csrf
            <x-form.input name="name" label="New fee type" placeholder="e.g. Computer lab fee" maxlength="80" required />
            <x-form.select name="frequency" label="Charged" :options="\App\Models\FeeHead::FREQUENCIES" value="monthly" />
            <button class="btn btn-secondary">Add</button>
        </form>
    </section>
</x-layouts.school>
