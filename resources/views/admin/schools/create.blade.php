@php $base = preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')).'/'; @endphp
<x-layouts.admin heading="Add a school" subheading="Skooline creates the school's portal, admin login and subscription in one step.">
    <x-slot:actions>
        <a href="{{ route('admin.schools.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back to schools</a>
    </x-slot:actions>

    @if ($lead)
        <div class="alert alert-info mb-6"><x-icon name="info" class="mt-px" /><p>Converting the lead from <span class="font-semibold">{{ $lead->name }}</span>. Check the details, then create the school.</p></div>
    @endif

    <form method="POST" action="{{ route('admin.schools.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($lead)<input type="hidden" name="lead_id" value="{{ $lead->id }}">@endif
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">1. School details</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" id="name" label="School name" required class="sm:col-span-2" placeholder="e.g. Greenfield Grammar School" />
                    <div class="sm:col-span-2">
                        <label for="slug" class="label">Portal address <span class="text-brand" aria-hidden="true">*</span></label>
                        <div class="flex rounded focus-within:ring-2 focus-within:ring-brand/20">
                            <span class="inline-flex max-w-[45%] items-center truncate rounded-l border border-r-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $base }}</span>
                            <input id="slug" name="slug" value="{{ old('slug') }}" required data-slug-from="#name" maxlength="40"
                                class="input min-w-0 rounded-l-none {{ $errors->has('slug') ? 'input-error' : '' }}" placeholder="greenfield" autocomplete="off" spellcheck="false">
                        </div>
                        @error('slug')
                            <p class="error">{{ $message }}</p>
                        @else
                            <p class="hint">School will sign in at <span class="font-medium text-ink">{{ $base }}<span data-preview-of="#slug" data-fallback="your-school"></span></span>. Lowercase letters, numbers and hyphens only.</p>
                        @enderror
                    </div>
                    <x-form.input name="principal_name" label="Principal / owner name" />
                    <x-form.input name="email" type="email" label="School email" />
                    <x-form.input name="phone" type="tel" label="Phone" placeholder="Include country code" />
                    <x-form.input name="city" label="City" />
                    <x-form.input name="country" label="Country" />
                    <x-form.select name="currency" label="Fee currency" :value="\App\Models\Setting::currency()" :options="collect(config('currencies'))->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()" hint="The school can change this later in its profile." />
                    <x-form.input name="address" label="Address" class="sm:col-span-2" />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">2. School admin login</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.input name="admin_name" label="Admin full name" required />
                    <x-form.input name="admin_email" type="email" label="Admin email (username)" required autocomplete="off" />
                    <x-form.input name="admin_password" type="password" label="Password" class="sm:col-span-2" autocomplete="new-password"
                        hint="Leave blank to generate a secure password. It will be shown once after saving." />
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">3. Plan & billing</h2></div>
                <div class="card-body space-y-5">
                    <x-form.select name="plan_id" label="Plan" required :options="$plans->mapWithKeys(fn ($p) => [$p->id => $p->name.' — '.$p->billingLabel()])" placeholder="Choose a plan" />

                    <fieldset>
                        <legend class="label">Start with</legend>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (['trial' => 'Free trial', 'paid' => 'Paid subscription'] as $value => $label)
                                <label class="flex cursor-pointer items-center gap-2 rounded border border-line-strong px-3 py-2.5 text-sm has-[:checked]:border-brand has-[:checked]:bg-brand-tint">
                                    <input type="radio" name="billing_mode" value="{{ $value }}" class="accent-brand" @checked(old('billing_mode', 'trial') === $value)> {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <div data-show-when="billing_mode=trial">
                        <x-form.input name="trial_days" type="number" label="Trial length (days)" :value="config('skooline.default_trial_days')" min="1" max="365" />
                    </div>

                    <div data-show-when="billing_mode=paid" class="space-y-5">
                        <x-form.input name="starts_at" type="date" label="Start date" :value="now()->toDateString()" />
                        <x-form.input name="student_count" type="number" label="Number of students" min="0" hint="Used for per-student plans." />
                        <x-form.input name="amount" type="number" step="0.01" min="0" label="Amount ({{ \App\Models\Setting::currency() }})" hint="Leave blank to use the plan price." />
                        <x-form.select name="payment_status" label="Payment" :options="['unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partially paid', 'waived' => 'Waived']" value="unpaid" />
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-body">
                    <x-form.textarea name="notes" label="Internal notes" hint="Only visible to AivraSol staff." />
                </div>
            </section>

            <div class="card bg-canvas p-5 text-sm text-body">
                <p class="flex items-center gap-2 font-semibold text-ink"><x-icon name="shield" size="4" class="text-success" /> What happens next</p>
                <ol class="mt-2 list-decimal space-y-1 pl-5">
                    <li>The school's private workspace is created.</li>
                    <li>The portal goes live on its own address.</li>
                    <li>You get the admin login to share with the school.</li>
                </ol>
            </div>

            <button class="btn btn-primary btn-lg w-full">Create school <x-icon name="arrow-right" size="4" /></button>
        </div>
    </form>
</x-layouts.admin>
