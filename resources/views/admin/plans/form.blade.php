@php
    $editing = $plan->exists;
    $selectedModules = old('modules', $plan->modules ?? []);
    $billingOptions = collect(config('skooline.billing_models'))->map(fn ($m) => $m['label']);
@endphp
<x-layouts.admin :heading="$editing ? 'Edit plan' : 'New plan'" :subheading="$editing ? $plan->name : 'Set the price, billing cycle, limits and modules.'">
    <x-slot:actions>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back to plans</a>
    </x-slot:actions>

    <form method="POST" action="{{ $editing ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Plan</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.input name="name" id="plan-name" label="Plan name" :value="$plan->name" required placeholder="e.g. Standard" />
                    <x-form.input name="slug" label="Slug" :value="$plan->slug" data-slug-from="#plan-name" hint="Used in links. Auto-filled from the name." />
                    <x-form.input name="tagline" label="Tagline" :value="$plan->tagline" class="sm:col-span-2" placeholder="Shown under the plan name on the website" />
                    <x-form.textarea name="description" label="Description" :value="$plan->description" class="sm:col-span-2" />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Pricing</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.select name="billing_model" label="Billing model" :options="$billingOptions" :value="$plan->billing_model" required class="sm:col-span-2" />
                    <x-form.input name="price" type="number" step="0.01" min="0" label="Price ({{ \App\Models\Setting::currency() }})" :value="$plan->price ?? 0" required
                        hint="Per cycle. For per-student plans this is an optional base fee; for lifetime it's the one-time price." />
                    <div data-show-when="billing_model=per_student">
                        <x-form.input name="price_per_student" type="number" step="0.01" min="0" label="Price per student / month" :value="$plan->price_per_student" />
                    </div>
                    <x-form.input name="setup_fee" type="number" step="0.01" min="0" label="One-time setup fee" :value="$plan->setup_fee" />
                    <x-form.input name="trial_days" type="number" min="0" max="365" label="Free trial (days)" :value="$plan->trial_days" required />
                </div>
                @php $others = collect(\App\Models\Setting::websiteCurrencies())->pluck('code')->push(\App\Models\Setting::get('stripe_currency', 'USD'))->push('USD')->unique()->reject(fn ($c) => $c === \App\Models\Setting::currency()); @endphp
                <div class="border-t border-line px-5 py-4">
                    <p class="text-sm font-semibold text-ink">Prices in other currencies <span class="font-normal text-muted">(optional)</span></p>
                    <p class="mt-0.5 text-xs text-muted">Set an exact price, e.g. USD 29. Left blank, the website converts with your rates (Settings → Currencies).</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        @foreach ($others as $code)
                            <x-form.input :name="'prices['.$code.']'" :id="'price-'.$code" type="number" step="0.01" min="0" :label="$code.' price'" :value="old('prices.'.$code, $plan->prices[$code] ?? null)" />
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Website button</h2></div>
                <div class="card-body grid gap-5 sm:grid-cols-2">
                    <x-form.select name="cta_mode" label="When a visitor clicks the plan's button" :options="\App\Models\Plan::CTA_MODES" :value="$plan->cta_mode ?? 'trial'" class="sm:col-span-2"
                        hint="Stripe needs Settings → Payments to be set up; otherwise the school registers and you send the invoice." />
                    <x-form.input name="cta_label" label="Button text (optional)" :value="$plan->cta_label" maxlength="60" placeholder="e.g. Start free trial, Buy now" class="sm:col-span-2" />
                </div>
            </section>

            <section class="card">
                <div class="card-header">
                    <h2 class="card-title">Modules included</h2>
                    <span class="text-xs text-muted">Schools only see the modules ticked here.</span>
                </div>
                <div class="card-body grid gap-2 sm:grid-cols-2">
                    @foreach (config('skooline.modules') as $key => $module)
                        <label class="flex cursor-pointer items-start gap-3 rounded border border-line p-3 transition-colors hover:border-line-strong has-[:checked]:border-brand/60 has-[:checked]:bg-brand-tint/50">
                            <input type="checkbox" name="modules[]" value="{{ $key }}" class="checkbox mt-0.5" @checked(in_array($key, $selectedModules, true))>
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $module['label'] }}</span>
                                <span class="block text-xs text-muted">{{ $module['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                    @error('modules')<p class="error sm:col-span-2">{{ $message }}</p>@enderror
                </div>
            </section>
        </div>

        <div class="space-y-6">
            <section class="card">
                <div class="card-header"><h2 class="card-title">Limits</h2></div>
                <div class="card-body space-y-5">
                    <x-form.input name="max_students" type="number" min="1" label="Max students" :value="$plan->max_students" hint="Blank = unlimited" />
                    <x-form.input name="max_staff" type="number" min="1" label="Max staff" :value="$plan->max_staff" hint="Blank = unlimited" />
                    <x-form.input name="max_branches" type="number" min="1" label="Branches / campuses" :value="$plan->max_branches" required />
                    <x-form.input name="storage_gb" type="number" min="1" label="Storage (GB)" :value="$plan->storage_gb" required />
                    <x-form.input name="sms_credits" type="number" min="0" label="SMS credits / cycle" :value="$plan->sms_credits" />
                    <x-form.input name="ai_requests_monthly" type="number" min="0" label="AI questions / month" :value="$plan->ai_requests_monthly ?? 0" hint="Needs the AI Assistant module. 0 = no AI." />
                </div>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Visibility</h2></div>
                <div class="card-body space-y-4">
                    @foreach (['is_active' => ['Active', 'Can be assigned to schools'], 'show_on_website' => ['Show on website', 'Listed in the pricing section'], 'is_featured' => ['Featured', 'Highlighted as "Most popular"']] as $field => [$label, $help])
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="hidden" name="{{ $field }}" value="0">
                            <input type="checkbox" name="{{ $field }}" value="1" class="checkbox mt-0.5" @checked(old($field, $plan->{$field}))>
                            <span><span class="block text-sm font-medium text-ink">{{ $label }}</span><span class="block text-xs text-muted">{{ $help }}</span></span>
                        </label>
                    @endforeach
                    <x-form.input name="sort_order" type="number" min="0" label="Display order" :value="$plan->sort_order ?? 0" />
                </div>
            </section>

            <button class="btn btn-primary btn-lg w-full">{{ $editing ? 'Save plan' : 'Create plan' }}</button>
        </div>
    </form>
</x-layouts.admin>
