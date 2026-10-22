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
            @stack('student-summary')

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
                <form method="POST" action="{{ route('school.students.destroy', $student) }}" data-confirm="Delete {{ $student->name }} for good? To keep the record, set the status to 'Left' instead." class="flex justify-end">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger-outline btn-sm"><x-icon name="trash" size="4" /> Delete record</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.school>
