<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => 'Apply for admission · '.$school->name, 'description' => 'Apply online for admission to '.$school->name.'.'])
</head>
<body class="min-h-full bg-canvas px-4 py-10">
    <main class="mx-auto w-full max-w-2xl">
        <div class="flex items-center gap-3">
            <x-school-badge :school="$school" />
            <div class="min-w-0">
                <p class="font-semibold break-words text-ink">{{ $school->name }}</p>
                <p class="text-xs text-muted">Online admission form</p>
            </div>
        </div>

        <div class="mt-6"><x-flash /></div>

        <form method="POST" action="{{ route('school.apply.submit') }}" class="card mt-4">
            @csrf
            <div class="card-header">
                <div>
                    <h1 class="card-title">Apply for admission</h1>
                    <p class="mt-0.5 text-sm text-muted">Send your details and the school office will call you.</p>
                </div>
            </div>
            <div class="card-body grid gap-5 sm:grid-cols-2">
                <x-form.input name="child_name" label="Child's full name" required maxlength="120" />
                <x-form.select name="class_id" label="Class" :options="$classes" placeholder="Choose class" />
                <x-form.select name="gender" label="Gender" :options="['male' => 'Boy', 'female' => 'Girl']" placeholder="—" />
                <x-form.input name="date_of_birth" type="date" label="Date of birth" />
                <x-form.input name="parent_name" label="Parent's name" required maxlength="120" />
                <x-form.input name="phone" type="tel" label="Phone / WhatsApp" required maxlength="30" />
                <x-form.input name="email" type="email" label="Email (optional)" />
                <x-form.input name="previous_school" label="Current / previous school" maxlength="150" />
                <x-form.input name="address" label="Address" maxlength="255" class="sm:col-span-2" />
                <x-form.textarea name="notes" label="Anything else?" rows="2" class="sm:col-span-2" />
                {{-- Spam trap: hidden from people --}}
                <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            </div>
            <div class="flex justify-end border-t border-line px-5 py-4"><button class="btn btn-primary">Send application</button></div>
        </form>
        <p class="mt-6 text-center text-xs text-muted">Powered by Skooline</p>
    </main>
</body>
</html>
