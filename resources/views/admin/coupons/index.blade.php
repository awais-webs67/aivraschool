<x-layouts.admin heading="Discount codes" subheading="Codes schools enter when registering or renewing. Tax is worked out after the discount.">
    <x-slot:actions>
        <button type="button" class="btn btn-primary" data-toggle="#coupon-new"><x-icon name="plus" size="4" /> New code</button>
    </x-slot:actions>

    <form id="coupon-new" method="POST" action="{{ route('admin.coupons.store') }}" class="card mb-6" @unless ($errors->any() && ! old('_coupon')) hidden @endunless>
        @csrf
        <div class="card-header"><h2 class="card-title">New discount code</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @include('admin.coupons._fields', ['c' => null, 'p' => 'new'])
        </div>
        <div class="flex justify-end gap-2 border-t border-line px-5 py-4">
            <button type="button" class="btn btn-ghost" data-toggle="#coupon-new">Cancel</button>
            <button class="btn btn-primary">Create code</button>
        </div>
    </form>

    <div class="card">
        @if ($coupons->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="tag" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No discount codes yet</p>
                <p class="mt-1 max-w-sm text-sm text-muted">Create a code like WELCOME20 for 20% off a school's first invoice.</p>
            </div>
        @else
            <ul class="divide-y divide-line">
                @foreach ($coupons as $c)
                    @php $problem = $c->problemFor(null); @endphp
                    <li class="px-5 py-4">
                        <div class="flex flex-wrap items-start gap-4">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-tint text-brand-text"><x-icon name="tag" size="5" /></span>
                            <div class="min-w-[12rem] flex-1">
                                <p class="flex flex-wrap items-center gap-2">
                                    <code class="font-mono font-semibold break-all text-ink">{{ $c->code }}</code>
                                    <span class="badge badge-brand">{{ $c->label() }}</span>
                                    <span class="badge {{ $problem ? 'badge-neutral' : 'badge-success' }}">{{ $problem ? 'Not usable' : 'Active' }}</span>
                                </p>
                                <p class="mt-1 text-xs text-muted">
                                    {{ $c->plan?->name ?? 'Every plan' }}
                                    · used {{ $c->used_count }}{{ $c->max_uses ? ' of '.$c->max_uses : '' }}
                                    · {{ $c->expires_at ? 'expires '.$c->expires_at->format('d M Y') : 'no expiry' }}
                                </p>
                                @if ($problem)<p class="mt-1 text-xs text-warning">{{ $problem }}</p>@endif
                                @if ($c->description)<p class="mt-1 text-sm text-body">{{ $c->description }}</p>@endif
                            </div>
                            <div class="flex gap-1">
                                <button type="button" class="btn btn-ghost btn-sm" data-toggle="#coupon-{{ $c->id }}" title="Edit"><x-icon name="pencil" size="4" /><span class="sr-only">Edit</span></button>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $c) }}" data-confirm="Delete code {{ $c->code }}? Invoices that used it keep their discount.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-ghost btn-sm text-danger" title="Delete"><x-icon name="trash" size="4" /><span class="sr-only">Delete</span></button>
                                </form>
                            </div>
                        </div>
                        <form id="coupon-{{ $c->id }}" method="POST" action="{{ route('admin.coupons.update', $c) }}" class="mt-4 grid gap-4 rounded-lg border border-line bg-canvas/60 p-4 sm:grid-cols-2 lg:grid-cols-3" @unless ((int) old('_coupon') === $c->id) hidden @endunless>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_coupon" value="{{ $c->id }}">
                            @include('admin.coupons._fields', ['c' => $c, 'p' => 'c'.$c->id])
                            <div class="flex justify-end gap-2 sm:col-span-2 lg:col-span-3">
                                <button type="button" class="btn btn-ghost" data-toggle="#coupon-{{ $c->id }}">Cancel</button>
                                <button class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </li>
                @endforeach
            </ul>
            @if ($coupons->hasPages())<div class="border-t border-line px-4 py-3">{{ $coupons->links() }}</div>@endif
        @endif
    </div>
</x-layouts.admin>
