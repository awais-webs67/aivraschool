<x-layouts.admin heading="Plans & Pricing" subheading="Build any subscription: monthly, yearly, lifetime or per student. Plans control which modules a school gets.">
    <x-slot:actions>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary"><x-icon name="plus" size="4" /> New plan</a>
    </x-slot:actions>

    @if ($plans->isEmpty())
        <div class="card flex flex-col items-center px-4 py-16 text-center">
            <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="layers" size="7" /></span>
            <p class="mt-4 text-lg font-semibold text-ink">No plans yet</p>
            <p class="mt-1 text-sm text-muted">Create a plan before adding schools.</p>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary mt-5"><x-icon name="plus" size="4" /> New plan</a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @foreach ($plans as $plan)
                <article class="card flex flex-col {{ $plan->is_featured ? 'border-brand/50' : '' }}">
                    <div class="card-body flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="font-display text-xl text-ink">{{ $plan->name }}</h2>
                                <p class="mt-1 text-sm text-muted">{{ $plan->tagline }}</p>
                            </div>
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($plan->is_featured)<span class="badge badge-brand">Featured</span>@endif
                                <span class="badge {{ $plan->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                                @unless ($plan->show_on_website)<span class="badge badge-neutral">Hidden</span>@endunless
                            </div>
                        </div>
                        <p class="mt-4 flex items-baseline gap-1">
                            @if ($plan->isPerStudent())
                                <span class="font-display tabular text-3xl text-ink">{{ money($plan->price_per_student) }}</span>
                                <span class="text-sm text-muted">{{ $plan->priceUnit() }}{{ (float) $plan->price ? ' + '.money($plan->price).' base' : '' }}</span>
                            @else
                                <span class="font-display tabular text-3xl text-ink">{{ money($plan->price) }}</span>
                                <span class="text-sm text-muted">{{ $plan->priceUnit() }}</span>
                            @endif
                        </p>
                        <dl class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div><dt class="text-xs text-muted">Students</dt><dd class="text-ink">{{ $plan->max_students ? number_format($plan->max_students) : 'Unlimited' }}</dd></div>
                            <div><dt class="text-xs text-muted">Staff</dt><dd class="text-ink">{{ $plan->max_staff ? number_format($plan->max_staff) : 'Unlimited' }}</dd></div>
                            <div><dt class="text-xs text-muted">Branches</dt><dd class="text-ink">{{ $plan->max_branches }}</dd></div>
                            <div><dt class="text-xs text-muted">Storage</dt><dd class="text-ink">{{ $plan->storage_gb }} GB</dd></div>
                        </dl>
                        <p class="mt-4 text-xs text-muted">{{ count($plan->modules ?? []) }} of {{ count(config('skooline.modules')) }} modules · {{ $plan->trial_days }}-day trial</p>
                    </div>
                    <div class="flex items-center justify-between border-t border-line px-5 py-3">
                        <span class="text-sm text-body"><span class="font-semibold text-ink">{{ $plan->schools_count }}</span> {{ \Illuminate\Support\Str::plural('school', $plan->schools_count) }}</span>
                        <div class="flex gap-1">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-secondary btn-sm"><x-icon name="pencil" size="4" /> Edit</a>
                            @if ($plan->schools_count === 0)
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" data-confirm="Delete the {{ $plan->name }} plan?">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
