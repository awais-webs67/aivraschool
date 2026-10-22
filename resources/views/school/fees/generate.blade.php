<x-layouts.school heading="Fees" subheading="Make the vouchers for a month in one click. Students who already have one are skipped.">
    @include('school.fees._nav')

    <form method="POST" action="{{ route('school.fees.generate.store') }}" class="card max-w-2xl">
        @csrf
        <div class="card-header"><h2 class="card-title">Make monthly vouchers</h2></div>
        <div class="card-body grid gap-5 sm:grid-cols-2">
            <x-form.input name="month" type="month" label="Month" :value="today()->format('Y-m')" required />
            <x-form.input name="due_date" type="date" label="Due date" :value="today()->startOfMonth()->addDays($dueDay - 1)->max(today())->toDateString()" required />
            @include('school.partials.section-select', ['name' => 'section_id', 'label' => 'Students', 'groups' => $groups, 'placeholder' => 'Whole school', 'class' => 'sm:col-span-2'])
            <fieldset class="sm:col-span-2">
                <legend class="label">Include</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($heads as $head)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="heads[]" value="{{ $head->id }}" class="checkbox" @checked($head->frequency === 'monthly')> {{ $head->name }} <span class="text-xs text-muted">({{ $head->frequency }})</span></label>
                    @endforeach
                </div>
                @error('heads')<p class="error">{{ $message }}</p>@enderror
                <p class="hint">Each student's fee discount is taken off automatically.</p>
            </fieldset>
        </div>
        <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Make vouchers</button></div>
    </form>
</x-layouts.school>
