@php
    $editing = $member->exists;
    $hasLogin = (bool) $member->user?->is_active;
@endphp
<x-layouts.school :heading="$editing ? 'Edit '.$member->name : 'Add staff'" subheading="Only the name is required. Add the rest any time.">
    <x-slot:actions>
        <a href="{{ $editing ? route('school.staff.show', $member) : route('school.staff.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('school.staff.update', $member) : route('school.staff.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <section class="card">
            <div class="card-header"><h2 class="card-title">Personal details</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="name" label="Full name" :value="$member->name" required maxlength="120" />
                <x-form.input name="father_name" label="Father / husband name" :value="$member->father_name" maxlength="120" />
                <x-form.select name="gender" label="Gender" :options="\App\Models\Staff::GENDERS" placeholder="—" :value="$member->gender" />
                <x-form.input name="date_of_birth" type="date" label="Date of birth" :value="$member->date_of_birth?->toDateString()" />
                <x-form.input name="national_id" label="National ID / CNIC" :value="$member->national_id" maxlength="40" />
                <x-form.input name="phone" type="tel" label="Phone" :value="$member->phone" maxlength="30" />
                <x-form.input name="email" type="email" label="Email" :value="$member->email" hint="Needed for a portal login." />
                <x-form.input name="address" label="Address" :value="$member->address" maxlength="255" />
                <x-photo-input name="photo" label="Photo" hint="JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2"
                    :current="$member->photo_path ? route('school.staff.photo', $member) : null" />
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2 class="card-title">Job</h2></div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="employee_no" label="Employee no." :value="$member->employee_no" maxlength="30" hint="Filled in for you; change it if you use your own numbers." />
                <x-form.input name="designation" label="Designation" :value="$member->designation" list="designations" maxlength="80" placeholder="e.g. Teacher" />
                <x-form.input name="department" label="Department" :value="$member->department" list="departments" maxlength="80" placeholder="e.g. Teaching" />
                <x-form.input name="qualification" label="Qualification" :value="$member->qualification" maxlength="150" placeholder="e.g. M.Sc Physics, B.Ed" />
                <x-form.input name="joining_date" type="date" label="Joining date" :value="$member->joining_date?->toDateString()" />
                <x-form.input name="salary" type="number" min="0" step="0.01" label="Monthly salary ({{ current_school()->currencyCode() }})" :value="$member->salary !== null ? (float) $member->salary : null" />
                <x-form.select name="status" label="Status" required :options="['active' => 'Working here', 'left' => 'Left the school']" :value="$member->status" />
                <div data-show-when="status=left">
                    <x-form.input name="left_on" type="date" label="Left on" :value="$member->left_on?->toDateString()" />
                </div>
                <x-form.textarea name="notes" label="Notes" rows="2" :value="$member->notes" class="sm:col-span-2" />
            </div>
            <datalist id="designations">@foreach (\App\Models\Staff::DESIGNATIONS as $d)<option value="{{ $d }}">@endforeach</datalist>
            <datalist id="departments">@foreach (\App\Models\Staff::DEPARTMENTS as $d)<option value="{{ $d }}">@endforeach</datalist>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Portal login</h2>
                    <p class="mt-0.5 text-sm text-muted">Let this person sign in at {{ current_school()->url('login') }}</p>
                </div>
            </div>
            <div class="card-body space-y-5">
                <label class="flex items-start gap-3">
                    <input type="hidden" name="login" value="0">
                    <input type="checkbox" name="login" value="1" class="checkbox mt-0.5" @checked(old('login', $hasLogin))>
                    <span><span class="block text-sm font-semibold text-ink">Can sign in to the portal</span><span class="block text-sm text-muted">Untick to stop their access. Their record stays.</span></span>
                </label>
                <div data-show-when="login=1" class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <x-form.select name="role" label="Role" required :options="$roles" :value="$member->user?->role ?? 'teacher'" />
                        <ul class="mt-2 space-y-1 text-xs text-muted">
                            @foreach ($roles as $key => $label)<li><span class="font-semibold text-body">{{ $label }}:</span> {{ \App\Models\User::ROLE_HINTS[$key] }}</li>@endforeach
                        </ul>
                    </div>
                    <x-form.input name="password" type="password" :label="$member->user ? 'New password' : 'Password'" :required="! $member->user" autocomplete="new-password"
                        :hint="$member->user ? 'Leave blank to keep the current password.' : 'At least 8 characters with letters and numbers. Share it with them privately.'" />
                </div>
            </div>
        </section>

        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ $editing ? route('school.staff.show', $member) : route('school.staff.index') }}" class="btn btn-ghost">Cancel</a>
            <button class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add staff member' }}</button>
        </div>
    </form>
</x-layouts.school>
