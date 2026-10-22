@php $editing = $student->exists; @endphp
<x-layouts.school :heading="$editing ? 'Edit '.$student->name : 'Admit a student'" subheading="Name, class and one parent phone number are required. Add the rest any time.">
    <x-slot:actions>
        <a href="{{ $editing ? route('school.students.show', $student) : route('school.students.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('school.students.update', $student) : route('school.students.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif
        @if ($enquiry)<input type="hidden" name="enquiry_id" value="{{ $enquiry->id }}">@endif

        <section class="card">
            <div class="card-header"><h2 class="card-title">Student</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Full name" :value="$student->name" required maxlength="120" />
                @include('school.partials.section-select', ['name' => 'section_id', 'label' => 'Class & section', 'groups' => $groups, 'value' => $student->section_id, 'required' => true, 'hint' => $groups ? null : 'Add classes first under School setup.'])
                <x-form.input name="admission_no" label="Admission no." :value="$student->admission_no" maxlength="30" hint="Filled in for you; change it if you use your own numbers." />
                <x-form.input name="roll_no" label="Roll no." :value="$student->roll_no" maxlength="20" />
                <x-form.select name="gender" label="Gender" :options="\App\Models\Student::GENDERS" placeholder="—" :value="$student->gender" />
                <x-form.input name="date_of_birth" type="date" label="Date of birth" :value="$student->date_of_birth?->toDateString()" />
                <x-form.input name="national_id" label="B-Form / birth certificate no." :value="$student->national_id" maxlength="40" />
                <x-form.input name="admission_date" type="date" label="Admission date" :value="$student->admission_date?->toDateString()" />
                <x-form.input name="religion" label="Religion" :value="$student->religion" maxlength="40" />
                <x-form.select name="blood_group" label="Blood group" :options="array_combine(\App\Models\Student::BLOOD_GROUPS, \App\Models\Student::BLOOD_GROUPS)" placeholder="—" :value="$student->blood_group" />
                <x-photo-input name="photo" label="Photo" hint="JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2" :current="$student->photo_path ? route('school.students.photo', $student) : null" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Parents & guardian</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="father_name" label="Father's name" :value="$student->father_name" maxlength="120" />
                <x-form.input name="father_phone" type="tel" label="Father's phone" :value="$student->father_phone" maxlength="30" hint="Brothers and sisters are linked by this number." />
                <x-form.input name="father_national_id" label="Father's CNIC / ID" :value="$student->father_national_id" maxlength="40" />
                <x-form.input name="father_occupation" label="Father's occupation" :value="$student->father_occupation" maxlength="80" />
                <x-form.input name="mother_name" label="Mother's name" :value="$student->mother_name" maxlength="120" />
                <x-form.input name="mother_phone" type="tel" label="Mother's phone" :value="$student->mother_phone" maxlength="30" />
                <x-form.input name="guardian_name" label="Guardian (if not parents)" :value="$student->guardian_name" maxlength="120" />
                <div class="grid grid-cols-2 gap-3">
                    <x-form.input name="guardian_relation" label="Relation" :value="$student->guardian_relation" maxlength="40" placeholder="Uncle" />
                    <x-form.input name="guardian_phone" type="tel" label="Phone" :value="$student->guardian_phone" maxlength="30" />
                </div>
                <x-form.input name="email" type="email" label="Parent email" :value="$student->email" />
                <x-form.input name="city" label="City" :value="$student->city" maxlength="80" />
                <x-form.input name="address" label="Home address" :value="$student->address" maxlength="255" class="sm:col-span-2" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">More</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="category" label="Category" :value="$student->category" list="categories" maxlength="30" placeholder="Regular" />
                <x-form.input name="fee_discount" type="number" min="0" max="100" step="0.01" label="Fee discount (%)" :value="(float) $student->fee_discount ?: null" hint="Sibling, staff child or scholarship." />
                <x-form.input name="previous_school" label="Previous school" :value="$student->previous_school" maxlength="150" />
                <x-form.textarea name="medical_notes" label="Medical notes / allergies" rows="2" :value="$student->medical_notes" />
                @if ($editing)
                    <x-form.select name="status" label="Status" required :options="\App\Models\Student::STATUSES" :value="$student->status" />
                    <div data-show-when="status=left|graduated" class="grid grid-cols-2 gap-3">
                        <x-form.input name="left_on" type="date" label="Date" :value="$student->left_on?->toDateString()" />
                        <x-form.input name="left_reason" label="Reason" :value="$student->left_reason" maxlength="255" />
                    </div>
                @endif
                <x-form.textarea name="notes" label="Notes" rows="2" :value="$student->notes" class="sm:col-span-2" />
            </div>
            <datalist id="categories">@foreach (\App\Models\Student::CATEGORIES as $c)<option value="{{ $c }}">@endforeach</datalist>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ $editing ? route('school.students.show', $student) : route('school.students.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Admit student' }}</button>
        </div>
    </form>
</x-layouts.school>
