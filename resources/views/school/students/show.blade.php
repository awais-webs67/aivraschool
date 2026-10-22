@php $canManage = auth('school')->user()->allows('students.manage'); @endphp
<x-layouts.school :heading="$student->name" :subheading="$student->admission_no.' · '.$student->classLabel().($student->roll_no ? ' · Roll '.$student->roll_no : '')">
    <x-slot:actions>
        <a href="{{ route('school.students.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Students</a>
        @if ($canManage)
            <a href="{{ route('school.students.create', ['sibling_of' => $student->id]) }}" class="btn btn-secondary"><x-icon name="plus" size="4" /> Add sibling</a>
            <a href="{{ route('school.students.edit', $student) }}" class="btn btn-primary"><x-icon name="pencil" size="4" /> Edit</a>
        @endif
    </x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 self-start">
            <section class="card">
                <div class="card-body flex flex-col items-center text-center">
                    @include('school.students._avatar', ['s' => $student, 'size' => 'size-24 text-2xl'])
                    <p class="mt-4 text-lg font-semibold break-words text-ink">{{ $student->name }}</p>
                    <p class="text-sm text-muted">{{ $student->classLabel() }}</p>
                    <div class="mt-3 flex flex-wrap justify-center gap-1">
                        <span class="badge {{ $student->status === 'active' ? 'badge-success' : 'badge-neutral' }}">{{ \App\Models\Student::STATUSES[$student->status] }}</span>
                        @if ($student->category)<span class="badge badge-brand">{{ $student->category }}</span>@endif
                        @if ((float) $student->fee_discount > 0)<span class="badge badge-warning">{{ (float) $student->fee_discount }}% fee discount</span>@endif
                    </div>
                </div>
                <dl class="divide-y divide-line border-t border-line text-sm">
                    @foreach ([
                        'Admission no.' => $student->admission_no,
                        'Admitted' => $student->admission_date?->format('d M Y'),
                        'Date of birth' => $student->date_of_birth ? $student->date_of_birth->format('d M Y').' ('.$student->age().' years)' : null,
                        'Gender' => \App\Models\Student::GENDERS[$student->gender] ?? null,
                        'Class teacher' => $student->section?->classTeacher?->name,
                    ] as $label => $value)
                        <div class="flex gap-3 px-5 py-3"><dt class="w-28 shrink-0 text-muted">{{ $label }}</dt><dd class="min-w-0 break-words text-ink">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            @if ($siblings->isNotEmpty())
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Siblings</h2></div>
                    <ul class="divide-y divide-line">
                        @foreach ($siblings as $sib)
                            <li><a href="{{ route('school.students.show', $sib) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-canvas">
                                @include('school.students._avatar', ['s' => $sib, 'size' => 'size-8'])
                                <span class="min-w-0"><span class="block truncate text-sm font-semibold text-ink">{{ $sib->name }}</span><span class="block text-xs text-muted">{{ $sib->classLabel() }}</span></span>
                            </a></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Attendance</h2>
                    <span class="text-xs text-muted">since {{ $attendance['since']->format('d M Y') }}</span>
                </div>
                <div class="card-body grid grid-cols-2 gap-4 text-center sm:grid-cols-4">
                    <div><p class="font-display text-2xl {{ $attendance['percent'] !== null && $attendance['percent'] < 75 ? 'text-danger' : 'text-ink' }}">{{ $attendance['percent'] !== null ? $attendance['percent'].'%' : '—' }}</p><p class="text-xs text-muted">Attendance</p></div>
                    <div><p class="font-display text-2xl text-success">{{ $attendance['present'] }}</p><p class="text-xs text-muted">Present</p></div>
                    <div><p class="font-display text-2xl text-danger">{{ $attendance['absent'] }}</p><p class="text-xs text-muted">Absent</p></div>
                    <div><p class="font-display text-2xl text-info">{{ $attendance['leave'] }}</p><p class="text-xs text-muted">Leave</p></div>
                </div>
                @if ($attendance['recentAbsences']->isNotEmpty())
                    <p class="border-t border-line px-5 py-3 text-xs text-muted">Last absent: {{ $attendance['recentAbsences']->map(fn ($r) => $r->date->format('d M'))->join(', ') }}</p>
                @endif
            </section>
            @includeIf('school.students._more', ['student' => $student])

            <section class="card">
                <div class="card-header"><h2 class="card-title">Parents & guardian</h2></div>
                <dl class="card-body grid gap-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        'Father' => $student->father_name,
                        'Father\'s phone' => $student->father_phone,
                        'Father\'s CNIC / ID' => $student->father_national_id,
                        'Occupation' => $student->father_occupation,
                        'Mother' => $student->mother_name,
                        'Mother\'s phone' => $student->mother_phone,
                        'Guardian' => $student->guardian_name ? $student->guardian_name.($student->guardian_relation ? ' ('.$student->guardian_relation.')' : '') : null,
                        'Guardian\'s phone' => $student->guardian_phone,
                        'Email' => $student->email,
                        'Address' => collect([$student->address, $student->city])->filter()->join(', '),
                    ] as $label => $value)
                        <div><dt class="text-xs text-muted">{{ $label }}</dt><dd class="break-words text-ink">
                            @if ($value && str_contains($label, 'phone'))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $value) }}" class="text-brand-text hover:underline">{{ $value }}</a>@else{{ $value ?: '—' }}@endif
                        </dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Other details</h2></div>
                <dl class="card-body grid gap-4 text-sm sm:grid-cols-2">
                    @foreach ([
                        'B-Form / certificate no.' => $student->national_id,
                        'Religion' => $student->religion,
                        'Blood group' => $student->blood_group,
                        'Previous school' => $student->previous_school,
                        'Medical notes' => $student->medical_notes,
                        'Left / passed out' => $student->left_on ? $student->left_on->format('d M Y').($student->left_reason ? ' · '.$student->left_reason : '') : null,
                    ] as $label => $value)
                        <div><dt class="text-xs text-muted">{{ $label }}</dt><dd class="break-words whitespace-pre-line text-ink">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
                @if ($student->notes)<p class="border-t border-line px-5 py-4 text-sm whitespace-pre-line text-body">{{ $student->notes }}</p>@endif
            </section>

            @if ($canManage)
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">Parent login</h2><p class="mt-0.5 text-sm text-muted">Parents sign in with their phone to see attendance, fees, results and homework.</p></div></div>
                    @if ($parents->isNotEmpty())
                        <ul class="divide-y divide-line border-b border-line">
                            @foreach ($parents as $p)
                                <li class="flex flex-wrap items-center gap-3 px-5 py-3 text-sm">
                                    <span class="min-w-0 flex-1"><span class="font-medium text-ink">{{ $p->name }}</span> <span class="text-muted">· phone {{ $p->phone }}{{ $p->email ? ' · '.$p->email : '' }}</span></span>
                                    <span class="text-xs text-muted">last sign-in {{ $p->last_login_at?->diffForHumans() ?? 'never' }}</span>
                                    <form method="POST" action="{{ route('school.students.parent.destroy', [$student, $p]) }}" data-confirm="Remove {{ $p->name }}'s access to {{ $student->name }}?">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm text-danger">Remove</button></form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <form method="POST" action="{{ route('school.students.parent.store', $student) }}" class="card-body grid gap-4 sm:grid-cols-2">
                        @csrf
                        <x-form.input name="name" id="parent-name" label="Parent's name" :value="$student->father_name ?: $student->guardian_name" required maxlength="120" />
                        <x-form.input name="phone" id="parent-phone" type="tel" label="Phone (used to sign in)" :value="$student->parentPhone()" required maxlength="30" />
                        <x-form.input name="email" id="parent-email" type="email" label="Email (optional)" :value="$student->email" />
                        <x-form.input name="password" id="parent-password" type="password" label="Password" autocomplete="new-password" :hint="$parents->isEmpty() ? 'At least 6 characters. Share it with the parent.' : 'Leave blank to keep the current password.'" />
                        <div class="sm:col-span-2"><button class="btn btn-secondary">{{ $parents->isEmpty() ? 'Create parent login' : 'Update / add parent' }}</button> <span class="text-xs text-muted">Brothers and sisters are linked too.</span></div>
                    </form>
                </section>

                <form method="POST" action="{{ route('school.students.destroy', $student) }}" data-confirm="Delete {{ $student->name }} for good? To keep the record, set the status to 'Left' instead." class="flex justify-end">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger-outline btn-sm"><x-icon name="trash" size="4" /> Delete record</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.school>
