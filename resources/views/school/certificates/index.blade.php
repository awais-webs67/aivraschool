<x-layouts.school heading="Certificates & ID cards" subheading="Issue leaving, character and bonafide certificates, and print student ID cards.">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <form method="GET" class="card flex flex-wrap gap-2 p-4" role="search">
                <input type="search" name="q" value="{{ $q }}" class="input min-w-0 flex-1" placeholder="Find a student by name or admission no." aria-label="Find student">
                <button class="btn btn-primary">Find</button>
            </form>

            @foreach ($found as $s)
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">{{ $s->name }}</h2><p class="text-xs text-muted">{{ $s->admission_no }} · {{ $s->classLabel() }} · {{ \App\Models\Student::STATUSES[$s->status] }}</p></div></div>
                    <form method="POST" action="{{ route('school.certificates.issue', $s) }}" class="card-body grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-form.select name="type" label="Certificate" :options="\App\Models\Certificate::TYPES" required :id="'type-'.$s->id" />
                        <x-form.input name="conduct" label="Conduct" value="Good" maxlength="40" :id="'conduct-'.$s->id" />
                        <div data-show-when="type=leaving" class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                            <x-form.input name="reason" label="Reason for leaving" maxlength="150" :id="'reason-'.$s->id" placeholder="e.g. Family moved" />
                            <x-form.input name="last_class" label="Last class attended" :value="$s->classLabel()" maxlength="60" :id="'last-'.$s->id" />
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="mark_left" value="1" class="checkbox" checked> Mark the student as left</label>
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ignore_dues" value="1" class="checkbox"> Issue anyway if fees are due</label>
                        </div>
                        <x-form.input name="remarks" label="Remarks" maxlength="255" :id="'remarks-'.$s->id" class="sm:col-span-2" />
                        <div class="sm:col-span-2"><button class="btn btn-primary">Issue & print</button></div>
                    </form>
                </section>
            @endforeach
            @if ($q !== '' && $found->isEmpty())<div class="card px-4 py-10 text-center text-sm text-muted">No student found.</div>@endif

            <section class="card">
                <div class="card-header"><h2 class="card-title">Recently issued</h2></div>
                <ul class="divide-y divide-line">
                    @forelse ($issued as $c)
                        <li><a href="{{ route('school.certificates.print', $c) }}" target="_blank" class="flex flex-wrap items-center gap-x-4 px-5 py-3 text-sm hover:bg-canvas">
                            <span class="font-mono text-xs text-muted">{{ $c->serial_no }}</span>
                            <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $c->student?->name }}</span>
                            <span class="text-muted">{{ \App\Models\Certificate::TYPES[$c->type] }}</span>
                            <span class="text-xs text-muted">{{ $c->issued_on->format('d M Y') }}</span>
                        </a></li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-muted">None issued yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <form method="GET" action="{{ route('school.certificates.id-cards') }}" target="_blank" class="card self-start">
            <div class="card-header"><h2 class="card-title">Student ID cards</h2></div>
            <div class="card-body space-y-4">
                @include('school.partials.section-select', ['name' => 'section', 'label' => 'Class', 'groups' => $groups, 'required' => true])
                <x-form.input name="valid" label="Valid till" :value="today()->addYear()->format('M Y')" maxlength="20" />
                <button class="btn btn-primary w-full"><x-icon name="identification" size="4" /> Print ID cards</button>
                <p class="text-xs text-muted">8 cards per A4 page. Add student photos for the best result.</p>
            </div>
        </form>
    </div>
</x-layouts.school>
