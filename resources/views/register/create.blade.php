<x-layouts.marketing title="Register Your School" description="Register your school on Skooline in a few minutes. Choose a plan or start a free trial and get your own school portal for admissions, attendance, fees and exams.">
    <section class="relative overflow-hidden border-b border-line bg-canvas">
        <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,#000_20%,transparent_70%)]"></div>
        <div class="container-x relative px-4 py-10 sm:px-6 md:py-14 lg:px-8">
            <p class="eyebrow">Get started</p>
            <h1 class="heading-lg mt-2">Register your school</h1>
            <p class="mt-3 max-w-2xl text-body">Tell us about your school and principal, choose a plan, and get your own Skooline portal.</p>
        </div>
    </section>

    <section class="px-4 py-10 sm:px-6 lg:px-8">
        <div class="container-x">
            @unless ($enabled)
                <div class="card mx-auto max-w-xl p-8 text-center">
                    <span class="mx-auto grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="clock" size="7" /></span>
                    <h2 class="font-display mt-4 text-2xl text-ink">Registration is closed for now</h2>
                    <p class="mt-2 text-body">Book a free demo and our team will set up your school for you.</p>
                    <a href="{{ route('home') }}#demo" class="btn btn-primary mt-6">Book a free demo</a>
                </div>
            @else
                <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data" class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start" novalidate>
                    @csrf
                    {{-- Spam trap: hidden from people, bots fill it --}}
                    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <div class="space-y-6">
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert"><x-icon name="alert" class="mt-px" /><p>Please fix the highlighted fields below.</p></div>
                        @endif

                        <section class="card">
                            <div class="card-header"><h2 class="card-title"><span class="mr-2 text-brand">1</span>School</h2></div>
                            <div class="card-body grid gap-5 sm:grid-cols-2">
                                <x-form.input name="school_name" id="school_name" label="School name" required class="sm:col-span-2" placeholder="e.g. Greenfield Grammar School" />
                                <div class="sm:col-span-2">
                                    <label for="slug" class="label">Your portal address <span class="text-brand" aria-hidden="true">*</span></label>
                                    <div class="flex">
                                        <span class="inline-flex max-w-[50%] items-center truncate rounded-l border border-r-0 border-line-strong bg-canvas px-3 text-sm text-muted">{{ $appHost }}/</span>
                                        <input id="slug" name="slug" value="{{ old('slug') }}" required maxlength="40" data-slug-from="#school_name" data-address-check="{{ route('register.check') }}"
                                            class="input min-w-0 rounded-l-none {{ $errors->has('slug') ? 'input-error' : '' }}" placeholder="yourschool" autocomplete="off" autocapitalize="none" spellcheck="false">
                                    </div>
                                    @error('slug')<p class="error">{{ $message }}</p>@else<p class="hint" data-address-status>Lowercase letters, numbers and hyphens.</p>@enderror
                                </div>
                                <x-form.input name="school_email" type="email" label="School email" autocomplete="email" />
                                <x-form.input name="school_phone" type="tel" label="School phone" placeholder="Include country code" />
                                <x-form.input name="school_address" label="Address" class="sm:col-span-2" autocomplete="street-address" />
                                <x-form.input name="city" label="City" autocomplete="address-level2" />
                                <x-form.input name="country" label="Country" autocomplete="country-name" />
                                <x-form.select name="currency" label="Fee currency" required :value="old('currency', 'USD')" :options="collect(config('currencies'))->mapWithKeys(fn ($c, $code) => [$code => $code.' — '.$c[0]])->all()" />
                                <x-form.input name="students" type="number" min="1" label="Number of students" />
                                <x-photo-input name="school_photo" label="School photo" hint="Building or logo. JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2" />
                            </div>
                        </section>

                        <section class="card">
                            <div class="card-header"><h2 class="card-title"><span class="mr-2 text-brand">2</span>Principal</h2></div>
                            <div class="card-body grid gap-5 sm:grid-cols-2">
                                <x-form.input name="principal_name" label="Full name" required autocomplete="name" />
                                <x-form.input name="principal_phone" type="tel" label="Mobile / WhatsApp" required placeholder="Include country code" autocomplete="tel" />
                                <x-form.input name="principal_national_id" label="National ID / CNIC" placeholder="e.g. 35202-1234567-1" />
                                <x-form.input name="principal_qualification" label="Qualification" placeholder="e.g. M.Ed, M.A. English" />
                                <x-form.input name="principal_address" label="Address" class="sm:col-span-2" />
                                <x-form.textarea name="principal_bio" label="Short bio" rows="3" class="sm:col-span-2" placeholder="Experience, years in education…" />
                                <x-photo-input name="principal_photo" label="Principal photo" hint="A clear face photo. JPG, PNG or WebP, up to 2 MB." class="sm:col-span-2" />
                            </div>
                        </section>

                        <section class="card">
                            <div class="card-header">
                                <div>
                                    <h2 class="card-title"><span class="mr-2 text-brand">3</span>Login</h2>
                                    <p class="mt-0.5 text-sm text-muted">The principal signs in to the school portal with this email and password.</p>
                                </div>
                            </div>
                            <div class="card-body grid gap-5 sm:grid-cols-2">
                                <x-form.input name="principal_email" type="email" label="Email (username)" required class="sm:col-span-2" autocomplete="email" />
                                <x-form.input name="password" type="password" label="Password" required autocomplete="new-password" hint="At least 8 characters." />
                                <x-form.input name="password_confirmation" type="password" label="Repeat password" required autocomplete="new-password" />
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-4 lg:sticky lg:top-24">
                        <section class="card">
                            <div class="card-header"><h2 class="card-title"><span class="mr-2 text-brand">4</span>Plan</h2></div>
                            <fieldset class="card-body space-y-3">
                                <legend class="sr-only">Choose a plan</legend>
                                @forelse ($plans as $plan)
                                    @php $checked = (int) old('plan_id', $selected) === $plan->id; @endphp
                                    <label class="relative block cursor-pointer">
                                        <input type="radio" name="plan_id" value="{{ $plan->id }}" class="peer sr-only" @checked($checked)>
                                        <span class="block rounded-lg border border-line-strong p-4 transition peer-checked:border-brand peer-checked:bg-brand-tint/50 peer-checked:ring-2 peer-checked:ring-brand/20 peer-focus-visible:ring-2 peer-focus-visible:ring-brand/40 hover:border-ink/40">
                                            <span class="flex items-start justify-between gap-2">
                                                <span class="font-semibold text-ink">{{ $plan->name }}</span>
                                                @if ($plan->cta_mode === 'trial' && $plan->trial_days > 0)
                                                    <span class="badge badge-success shrink-0">{{ $plan->trial_days }}-day free trial</span>
                                                @elseif ($plan->cta_mode === 'stripe' && $stripeOn)
                                                    <span class="badge badge-info shrink-0">Pay online</span>
                                                @endif
                                            </span>
                                            <span class="mt-1 block text-sm text-body">
                                                <span class="font-semibold text-ink" data-price="{{ $plan->headlinePrice() }}" @foreach ($plan->prices ?? [] as $code => $fixed) data-price-{{ strtolower($code) }}="{{ $fixed }}" @endforeach>{{ money($plan->headlinePrice()) }}</span>
                                                {{ $plan->priceUnit() }}
                                            </span>
                                            @if ($plan->tagline)<span class="mt-1 block text-xs text-muted">{{ $plan->tagline }}</span>@endif
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-muted">No plans are open for online sign-up yet. <a href="{{ route('home') }}#demo" class="font-semibold text-brand-text underline">Contact us</a>.</p>
                                @endforelse
                                @error('plan_id')<p class="error">{{ $message }}</p>@enderror
                            </fieldset>
                        </section>

                        <section class="card p-5">
                            <label class="flex items-start gap-2 text-sm text-body">
                                <input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" @checked(old('terms'))>
                                <span>I confirm these details are correct and agree to be contacted about Skooline.</span>
                            </label>
                            @error('terms')<p class="error">{{ $message }}</p>@enderror
                            <button class="btn btn-primary btn-lg mt-4 w-full" @disabled($plans->isEmpty())>Register school <x-icon name="arrow-right" size="4" /></button>
                            <p class="mt-3 text-center text-xs text-muted">Already registered? <a href="{{ route('home') }}" class="font-semibold text-brand-text underline">Find your school's login</a></p>
                        </section>
                    </aside>
                </form>
            @endunless
        </div>
    </section>

    @if (count($currencies) > 1)
        <script type="application/json" id="currency-data">{!! json_encode(['base' => $currencies[0]['code'], 'currencies' => $currencies, 'default' => \App\Models\Setting::get('website_default_currency', 'auto')], JSON_HEX_TAG) !!}</script>
    @endif
</x-layouts.marketing>
