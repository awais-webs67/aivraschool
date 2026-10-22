{{-- Principal's profile, shared by "Add school" and "Edit school". $school is null when adding. --}}
<x-form.input name="principal_name" label="Principal name" :value="$school?->principal_name" />
<x-form.input name="principal_phone" type="tel" label="Mobile / WhatsApp" :value="$school?->principal_phone" placeholder="Include country code" />
<x-form.input name="principal_email" type="email" label="Principal email" :value="$school?->principal_email" />
<x-form.input name="principal_national_id" label="National ID / CNIC" :value="$school?->principal_national_id" placeholder="e.g. 35202-1234567-1" />
<x-form.input name="principal_qualification" label="Qualification" :value="$school?->principal_qualification" />
<x-form.input name="principal_address" label="Address" :value="$school?->principal_address" />
<x-form.textarea name="principal_bio" label="Short bio" rows="3" :value="$school?->principal_bio" class="sm:col-span-2" />
<x-photo-input name="principal_photo" label="Principal photo" hint="JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2"
    :current="$school?->principal_photo_path ? route('admin.schools.photo', [$school, 'principal']) : null" />
