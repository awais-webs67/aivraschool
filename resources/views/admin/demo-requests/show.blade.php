<x-layouts.admin :title="$lead->school_name">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.demo-requests.index') }}" class="text-xs font-medium text-muted hover:text-ink">← Leads</a>
            <h1 class="page-title truncate">{{ $lead->school_name }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-muted">
                <span class="badge {{ $lead->statusBadge() }}">{{ \App\Models\DemoRequest::STATUS_LABELS[$lead->status] }}</span>
                Received {{ $lead->created_at->format('d M Y, h:i A') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($lead->school)
                <a href="{{ route('admin.schools.show', $lead->school) }}" class="btn btn-secondary"><x-icon name="building" size="4" /> View school</a>
            @elsecan('schools.manage')
                <a href="{{ route('admin.schools.create', ['lead' => $lead->id]) }}" class="btn btn-primary"><x-icon name="plus" size="4" /> Convert to school</a>
            @endif
            <form method="POST" action="{{ route('admin.demo-requests.destroy', $lead) }}" data-confirm="Delete this lead?">
                @csrf
                @method('DELETE')
                <button class="btn btn-ghost text-danger"><x-icon name="trash" size="4" /> Delete</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header"><h2 class="card-title">Pipeline</h2></div>
            <form method="POST" action="{{ route('admin.demo-requests.update', $lead) }}" class="card-body space-y-5">
                @csrf
                @method('PATCH')
                <fieldset>
                    <legend class="label">Status</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\DemoRequest::STATUS_LABELS as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" name="status" value="{{ $value }}" class="peer sr-only" @checked($lead->status === $value)>
                                <span class="inline-flex rounded-full border border-line-strong px-3.5 py-1.5 text-sm font-medium text-body transition peer-checked:border-brand peer-checked:bg-brand peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand/40 hover:border-ink/40">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <x-form.input name="follow_up_on" type="date" label="Next follow-up" :value="$lead->follow_up_on?->toDateString()" class="max-w-xs" hint="Shows in the bell menu on that day." />
                <x-form.textarea name="notes" label="Notes" rows="6" :value="$lead->notes" placeholder="Calls, demo feedback, price discussed…" />
                <div class="flex justify-end"><button class="btn btn-primary">Save</button></div>
            </form>
        </section>

        <section class="card self-start">
            <div class="card-header"><h2 class="card-title">Contact</h2></div>
            <dl class="card-body space-y-3 text-sm">
                @foreach (['Name' => $lead->name, 'Phone' => $lead->phone, 'Email' => $lead->email, 'City' => $lead->city, 'Students' => $lead->students ? number_format($lead->students) : null] as $label => $value)
                    <div class="grid grid-cols-[5.5rem_1fr] gap-2"><dt class="text-muted">{{ $label }}</dt><dd class="break-words text-ink">{{ $value ?: '—' }}</dd></div>
                @endforeach
            </dl>
            <div class="flex flex-wrap gap-2 border-t border-line px-5 py-4">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}" class="btn btn-secondary btn-sm"><x-icon name="phone" size="4" /> Call</a>
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><x-icon name="chat" size="4" /> WhatsApp</a>
                @if ($lead->email)<a href="mailto:{{ $lead->email }}" class="btn btn-secondary btn-sm"><x-icon name="mail" size="4" /> Email</a>@endif
            </div>
            @if ($lead->message)
                <div class="border-t border-line px-5 py-4 text-sm">
                    <p class="text-xs font-semibold text-muted uppercase">Their message</p>
                    <p class="mt-1 whitespace-pre-line text-body">{{ $lead->message }}</p>
                </div>
            @endif
        </section>
    </div>
</x-layouts.admin>
