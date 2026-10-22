<x-layouts.school heading="Admissions" subheading="Enquiries from parents: follow up, then admit with one click.">
    <x-slot:actions>
        <button type="button" class="btn btn-secondary" data-copy="{{ $applyUrl }}" title="Parents can apply online at this link"><x-icon name="copy" size="4" /> Copy online form link</button>
        <button type="button" class="btn btn-primary" data-toggle="#new-enquiry"><x-icon name="plus" size="4" /> New enquiry</button>
    </x-slot:actions>

    <form id="new-enquiry" method="POST" action="{{ route('school.enquiries.store') }}" class="card mb-6" @unless ($errors->any()) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">New enquiry</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <x-form.input name="child_name" label="Child's name" required maxlength="120" />
            <x-form.select name="class_id" label="Class wanted" :options="$classes" placeholder="—" />
            <x-form.input name="date_of_birth" type="date" label="Date of birth" />
            <x-form.input name="parent_name" label="Parent's name" maxlength="120" />
            <x-form.input name="phone" type="tel" label="Phone" required maxlength="30" />
            <x-form.select name="source" label="How they found us" :options="\App\Models\Enquiry::SOURCES" value="walk_in" />
            <x-form.input name="follow_up_on" type="date" label="Follow up on" :value="now()->addDays(2)->toDateString()" />
            <x-form.input name="previous_school" label="Previous school" maxlength="150" />
            <x-form.input name="notes" label="Notes" maxlength="2000" />
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <button type="button" class="btn btn-ghost" data-toggle="#new-enquiry">Cancel</button>
            <button class="btn btn-primary">Save enquiry</button>
        </div>
    </form>

    @if ($dueToday)
        <div class="alert alert-warning mb-4"><x-icon name="clock" class="mt-px" /><p>{{ $dueToday }} {{ \Illuminate\Support\Str::plural('enquiry', $dueToday) }} to follow up today or overdue.</p></div>
    @endif

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-line" aria-label="Status">
        @foreach (['open' => 'Open', ...\App\Models\Enquiry::STATUSES, 'all' => 'All'] as $key => $label)
            <a href="{{ route('school.enquiries.index', ['status' => $key]) }}" class="-mb-px shrink-0 border-b-2 px-3 py-2 text-sm font-semibold whitespace-nowrap {{ $status === $key ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">
                {{ $label }} @if (isset($counts[$key]))<span class="text-xs text-muted">{{ $counts[$key] }}</span>@endif
            </a>
        @endforeach
    </nav>

    <div class="card">
        @if ($enquiries->isEmpty())
            <div class="flex flex-col items-center px-4 py-14 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="clipboard" size="7" /></span>
                <p class="mt-4 font-semibold text-ink">No enquiries here</p>
                <p class="mt-1 max-w-sm text-sm text-muted">Note every parent who calls or visits. Share the online form link so parents can apply from home.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($enquiries as $e)
                    <li class="px-5 py-4">
                        <div class="flex flex-wrap items-start gap-x-4 gap-y-2">
                            <div class="min-w-[12rem] flex-1">
                                <p class="flex flex-wrap items-center gap-2"><span class="font-semibold text-ink">{{ $e->child_name }}</span><span class="badge {{ $e->statusBadge() }}">{{ \App\Models\Enquiry::STATUSES[$e->status] }}</span></p>
                                <p class="mt-0.5 text-sm text-body">{{ $e->schoolClass?->name ?? 'Class not chosen' }} · {{ $e->parent_name ?: 'Parent' }} · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $e->phone) }}" class="text-brand-text hover:underline">{{ $e->phone }}</a></p>
                                <p class="text-xs text-muted">{{ \App\Models\Enquiry::SOURCES[$e->source] ?? $e->source }} · {{ $e->created_at->format('d M Y') }}
                                    @if ($e->follow_up_on && $e->isOpen()) · <span class="{{ $e->follow_up_on->isPast() && ! $e->follow_up_on->isToday() ? 'font-semibold text-danger' : '' }}">follow up {{ $e->follow_up_on->format('d M') }}</span>@endif</p>
                                @if ($e->notes)<p class="mt-1 text-sm break-words whitespace-pre-line text-body">{{ $e->notes }}</p>@endif
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @if ($e->student_id)
                                    <a href="{{ route('school.students.show', $e->student_id) }}" class="btn btn-ghost btn-sm">View student</a>
                                @elseif ($e->isOpen() && auth('school')->user()->allows('students.manage'))
                                    <a href="{{ route('school.students.create', ['enquiry' => $e->id]) }}" class="btn btn-primary btn-sm">Admit</a>
                                @endif
                                <button type="button" class="btn btn-secondary btn-sm" data-toggle="#enq-{{ $e->id }}">Update</button>
                            </div>
                        </div>
                        <form id="enq-{{ $e->id }}" method="POST" action="{{ route('school.enquiries.update', $e) }}" class="mt-3 grid gap-3 rounded-lg border border-line bg-canvas/60 p-4 sm:grid-cols-2 lg:grid-cols-4" hidden>
                            @csrf
                            @method('PUT')
                            @foreach (['child_name', 'parent_name', 'phone', 'class_id', 'source', 'previous_school', 'email', 'address'] as $keep)
                                <input type="hidden" name="{{ $keep }}" value="{{ $e->{$keep} }}">
                            @endforeach
                            <x-form.select name="status" :id="'st-'.$e->id" label="Status" :options="\App\Models\Enquiry::STATUSES" :value="$e->status" />
                            <x-form.input name="follow_up_on" :id="'fu-'.$e->id" type="date" label="Next follow-up" :value="$e->follow_up_on?->toDateString()" />
                            <x-form.input name="notes" :id="'nt-'.$e->id" label="Notes" :value="$e->notes" class="lg:col-span-2" />
                            <div class="flex flex-wrap justify-between gap-2 sm:col-span-2 lg:col-span-4">
                                <button form="del-{{ $e->id }}" class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" size="4" /> Delete</button>
                                <button class="btn btn-primary btn-sm">Save</button>
                            </div>
                        </form>
                        <form id="del-{{ $e->id }}" method="POST" action="{{ route('school.enquiries.destroy', $e) }}" data-confirm="Delete this enquiry?" hidden>@csrf @method('DELETE')</form>
                    </li>
                @endforeach
            </ul>
            @if ($enquiries->hasPages())<div class="border-t border-line px-4 py-3">{{ $enquiries->links() }}</div>@endif
        @endif
    </div>
</x-layouts.school>
