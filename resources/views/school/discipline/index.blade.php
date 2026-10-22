<x-layouts.school heading="Discipline" subheading="Behaviour record: concerns and merits for each student.">
    <x-slot:actions><button type="button" class="btn btn-primary" data-toggle="#new-incident"><x-icon name="plus" size="4" /> Add record</button></x-slot:actions>

    <form id="new-incident" method="POST" action="{{ route('school.discipline.store') }}" class="card mb-6" @unless ($errors->any() || $student) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">New record</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form.input name="admission_no" label="Student admission no." required :value="$student?->admission_no" maxlength="30" />
            <x-form.select name="kind" label="Type" :options="['concern' => 'Concern', 'merit' => 'Merit / achievement']" value="concern" required />
            <x-form.input name="category" label="What happened" list="incident-categories" required maxlength="40" placeholder="e.g. Late coming" />
            <div data-show-when="kind=concern"><x-form.select name="severity" label="How serious" :options="\App\Models\Incident::SEVERITIES" value="low" /></div>
            <x-form.input name="date" type="date" label="Date" :value="today()->toDateString()" required />
            <x-form.input name="action_taken" label="Action taken" list="incident-actions" maxlength="40" />
            <x-form.textarea name="description" label="Details" rows="2" required class="sm:col-span-2 lg:col-span-3" />
            <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="parent_informed" value="1" class="checkbox"> Parent informed</label>
        </div>
        <datalist id="incident-categories">@foreach ([...\App\Models\Incident::CONCERNS, ...\App\Models\Incident::MERITS] as $c)<option value="{{ $c }}">@endforeach</datalist>
        <datalist id="incident-actions">@foreach (\App\Models\Incident::ACTIONS as $a)<option value="{{ $a }}">@endforeach</datalist>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4"><button type="button" class="btn btn-ghost" data-toggle="#new-incident">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3">
        <x-stat label="Concerns this month" :value="number_format($thisMonth['concern'] ?? 0)" icon="alert" tone="danger" />
        <x-stat label="Merits this month" :value="number_format($thisMonth['merit'] ?? 0)" icon="check-circle" tone="success" />
        <x-stat label="3+ concerns in 30 days" :value="number_format($repeat->count())" icon="users" tone="warning" :hint="$repeat->take(3)->map(fn ($r) => $r->student?->name)->join(', ')" />
    </div>

    <nav class="mt-6 mb-4 flex gap-1 overflow-x-auto border-b border-line" aria-label="Type">
        @foreach ([null => 'All', 'concern' => 'Concerns', 'merit' => 'Merits'] as $k => $label)
            <a href="{{ route('school.discipline.index', array_filter(['kind' => $k, 'student' => $student?->id])) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold {{ $kind === ($k ?: null) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </nav>
    @if ($student)<p class="mb-3 text-sm text-body">Showing {{ $student->name }} only. <a href="{{ route('school.discipline.index') }}" class="font-semibold text-brand-text underline">Show all</a></p>@endif

    <div class="card">
        <ul class="divide-y divide-line">
            @forelse ($incidents as $i)
                <li class="flex flex-wrap items-start gap-x-4 gap-y-2 px-5 py-3">
                    <div class="min-w-[12rem] flex-1">
                        <p class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('school.students.show', $i->student_id) }}" class="font-semibold text-ink hover:text-brand-text">{{ $i->student?->name }}</a>
                            <span class="text-xs text-muted">{{ $i->student?->classLabel() }}</span>
                            <span class="badge {{ $i->badge() }}">{{ $i->kind === 'merit' ? 'Merit' : \App\Models\Incident::SEVERITIES[$i->severity] }}</span>
                        </p>
                        <p class="mt-0.5 text-sm text-body"><span class="font-medium text-ink">{{ $i->category }}:</span> {{ $i->description }}</p>
                        <p class="text-xs text-muted">{{ $i->date->format('d M Y') }} · {{ $i->reporter?->name }}{{ $i->action_taken ? ' · '.$i->action_taken : '' }}{{ $i->parent_informed ? ' · parent informed' : '' }}</p>
                    </div>
                    @if ($i->reported_by === auth('school')->id() || auth('school')->user()->allows('approvals'))
                        <form method="POST" action="{{ route('school.discipline.destroy', $i) }}" data-confirm="Delete this record?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger" aria-label="Delete"><x-icon name="trash" size="4" /></button></form>
                    @endif
                </li>
            @empty
                <li class="px-5 py-14 text-center text-sm text-muted">No records yet.</li>
            @endforelse
        </ul>
        @if ($incidents->hasPages())<div class="border-t border-line px-4 py-3">{{ $incidents->links() }}</div>@endif
    </div>
</x-layouts.school>
