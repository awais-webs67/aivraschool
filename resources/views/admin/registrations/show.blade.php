<x-layouts.admin :title="$r->school_name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="flex min-w-0 items-start gap-4">
            @if ($r->school_photo_path)
                <a href="{{ route('admin.registrations.photo', [$r, 'school']) }}" target="_blank"><img src="{{ route('admin.registrations.photo', [$r, 'school']) }}" alt="School photo" class="size-16 shrink-0 rounded-lg object-cover ring-1 ring-line"></a>
            @endif
            <div class="min-w-0">
                <a href="{{ route('admin.registrations.index') }}" class="text-xs font-medium text-muted hover:text-ink">← Registrations</a>
                <h1 class="page-title">{{ $r->school_name }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
                    <span class="font-mono text-xs">{{ $r->number() }}</span>
                    <span class="badge {{ $r->statusBadge() }}">{{ $r->statusLabel() }}</span>
                    @if ($r->isPaid())<span class="badge badge-success">Paid {{ $r->amount_currency }} {{ number_format((float) $r->amount, 2) }}</span>@endif
                    Received {{ $r->created_at->format('d M Y, h:i A') }}
                </p>
            </div>
        </div>
        @if ($r->school)
            <a href="{{ route('admin.schools.show', $r->school) }}" class="btn btn-secondary"><x-icon name="building" size="4" /> View school</a>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="card">
                <div class="card-header"><h2 class="card-title">School</h2></div>
                <dl class="card-body grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    @foreach (['Requested address' => $r->slug, 'Email' => $r->school_email, 'Phone' => $r->school_phone, 'Address' => $r->school_address, 'City / country' => collect([$r->city, $r->country])->filter()->join(', '), 'Students' => $r->students ? number_format($r->students) : null, 'Fee currency' => $r->currency] as $label => $value)
                        <div><dt class="text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value ?: '—' }}</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="card">
                <div class="card-header"><h2 class="card-title">Principal</h2></div>
                <div class="card-body flex flex-col gap-5 sm:flex-row">
                    @if ($r->principal_photo_path)
                        <a href="{{ route('admin.registrations.photo', [$r, 'principal']) }}" target="_blank" class="shrink-0"><img src="{{ route('admin.registrations.photo', [$r, 'principal']) }}" alt="Principal photo" class="size-28 rounded-lg object-cover ring-1 ring-line"></a>
                    @endif
                    <dl class="grid flex-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        @foreach (['Name' => $r->principal_name, 'Email (login)' => $r->principal_email, 'Phone' => $r->principal_phone, 'National ID / CNIC' => $r->principal_national_id, 'Qualification' => $r->principal_qualification, 'Address' => $r->principal_address] as $label => $value)
                            <div><dt class="text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value ?: '—' }}</dd></div>
                        @endforeach
                        @if ($r->principal_bio)
                            <div class="sm:col-span-2"><dt class="text-muted">Bio</dt><dd class="whitespace-pre-line text-ink">{{ $r->principal_bio }}</dd></div>
                        @endif
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2 border-t border-line px-5 py-4">
                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $r->principal_phone) }}" class="btn btn-secondary btn-sm"><x-icon name="phone" size="4" /> Call</a>
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $r->principal_phone) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><x-icon name="chat" size="4" /> WhatsApp</a>
                    <a href="mailto:{{ $r->principal_email }}" class="btn btn-secondary btn-sm"><x-icon name="mail" size="4" /> Email</a>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            @if ($r->isPending())
                @unless ($addressFree)
                    <div class="alert alert-warning"><x-icon name="alert" class="mt-px" /><p>The address <span class="font-semibold">{{ $r->slug }}</span> is now used by another school. Change it below before approving.</p></div>
                @endunless

                <form method="POST" action="{{ route('admin.registrations.approve', $r) }}" class="card border-success/40">
                    @csrf
                    <div class="card-header"><h2 class="card-title">Approve</h2></div>
                    <div class="card-body space-y-4">
                        @if ($r->awaitingPayment())
                            <p class="text-sm text-warning">The school chose to pay online but has not paid yet.</p>
                        @endif
                        <x-form.select name="mode" label="Start the school on" :value="$r->isPaid() ? 'paid' : ($r->billing_choice === 'trial' ? 'trial' : 'unpaid')"
                            :options="['trial' => 'Free trial', 'paid' => 'Paid subscription (payment received)', 'unpaid' => 'Subscription with an unpaid invoice']" />
                        <div data-show-when="mode=trial">
                            <x-form.input name="trial_days" type="number" min="1" max="365" label="Trial days" :value="$r->plan?->trial_days ?: config('skooline.default_trial_days')" />
                        </div>
                        <button class="btn btn-primary w-full" @disabled(! $addressFree)><x-icon name="check" size="4" /> Approve and create school</button>
                        <p class="text-xs text-muted">The principal signs in with the email and password they chose and gets an email with the link.</p>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.registrations.update', $r) }}" class="card">
                    @csrf
                    @method('PATCH')
                    <div class="card-header"><h2 class="card-title">Edit before approving</h2></div>
                    <div class="card-body space-y-4">
                        <x-form.input name="slug" label="Portal address" :value="$r->slug" required />
                        <x-form.select name="plan_id" label="Plan" :value="$r->plan_id" :options="$plans" required />
                        <x-form.textarea name="admin_notes" label="Internal notes" :value="$r->admin_notes" rows="3" />
                        <button class="btn btn-secondary w-full">Save</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.registrations.reject', $r) }}" class="card border-danger/30" data-confirm="Reject this registration? The principal will be emailed.">
                    @csrf
                    <div class="card-header"><h2 class="card-title">Reject</h2></div>
                    <div class="card-body space-y-3">
                        <x-form.input name="reason" label="Reason (sent to the principal)" placeholder="Optional" />
                        <button class="btn btn-danger-outline w-full">Reject registration</button>
                    </div>
                </form>
            @else
                <section class="card">
                    <div class="card-header"><h2 class="card-title">Review</h2></div>
                    <dl class="card-body space-y-3 text-sm">
                        <div><dt class="text-muted">Decision</dt><dd class="text-ink">{{ $r->statusLabel() }}{{ $r->reviewed_at ? ' · '.$r->reviewed_at->format('d M Y, h:i A') : '' }}</dd></div>
                        <div><dt class="text-muted">By</dt><dd class="text-ink">{{ $r->reviewer?->name ?? 'Automatic' }}</dd></div>
                        @if ($r->rejection_reason)<div><dt class="text-muted">Reason</dt><dd class="text-ink">{{ $r->rejection_reason }}</dd></div>@endif
                    </dl>
                </section>
            @endif

            <section class="card">
                <div class="card-header"><h2 class="card-title">Plan & payment</h2></div>
                <dl class="card-body space-y-3 text-sm">
                    <div><dt class="text-muted">Plan</dt><dd class="text-ink">{{ $r->plan?->name ?? '—' }}</dd></div>
                    <div><dt class="text-muted">Chose</dt><dd class="text-ink">{{ \App\Models\SchoolRegistration::CHOICES[$r->billing_choice] ?? $r->billing_choice }}</dd></div>
                    @if ($r->amount)<div><dt class="text-muted">Amount</dt><dd class="text-ink">{{ $r->amount_currency }} {{ number_format((float) $r->amount, 2) }} · {{ $r->isPaid() ? 'paid '.$r->paid_at?->format('d M Y') : 'not paid' }}</dd></div>@endif
                    @if ($r->stripe_session_id)<div><dt class="text-muted">Stripe reference</dt><dd class="font-mono text-xs break-all text-ink">{{ $r->stripe_session_id }}</dd></div>@endif
                    @if ($r->admin_notes)<div><dt class="text-muted">Notes</dt><dd class="whitespace-pre-line text-ink">{{ $r->admin_notes }}</dd></div>@endif
                    <div><dt class="text-muted">IP address</dt><dd class="font-mono text-xs text-ink">{{ $r->ip_address ?? '—' }}</dd></div>
                </dl>
            </section>
        </div>
    </div>
</x-layouts.admin>
