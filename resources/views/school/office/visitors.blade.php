<x-layouts.school heading="Front office" subheading="Who came to the school, why, and when they left.">
    @include('school.office._nav')
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="card xl:col-span-2">
            <div class="card-header flex-wrap gap-2">
                <h2 class="card-title">Visitors · {{ $date->format('d M Y') }}</h2>
                <form method="GET"><input type="date" name="date" value="{{ $date->toDateString() }}" class="input w-auto py-1.5 text-sm" aria-label="Date" onchange="this.form.submit()"></form>
            </div>
            <ul class="divide-y divide-line">
                @forelse ($visitors as $v)
                    <li class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
                        <div class="min-w-[11rem] flex-1">
                            <p class="font-medium text-ink">{{ $v->name }}{{ $v->people > 1 ? ' +'.($v->people - 1) : '' }}</p>
                            <p class="text-xs text-muted">{{ $v->purpose }}{{ $v->to_meet ? ' · to meet '.$v->to_meet : '' }}{{ $v->phone ? ' · '.$v->phone : '' }}</p>
                        </div>
                        <p class="text-xs text-muted tabular">{{ $v->in_at->format('h:i A') }} – {{ $v->out_at?->format('h:i A') ?? '…' }}</p>
                        @unless ($v->out_at)
                            <form method="POST" action="{{ route('school.visitors.out', $v) }}">@csrf<button class="btn btn-secondary btn-sm">Sign out</button></form>
                        @endunless
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-muted">No visitors on this day.</li>
                @endforelse
            </ul>
        </section>
        <form method="POST" action="{{ route('school.visitors.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Sign a visitor in</h2>@if ($inside)<span class="badge badge-warning">{{ $inside }} inside</span>@endif</div>
            <div class="card-body space-y-3">
                <x-form.input name="name" label="Name" required maxlength="120" />
                <x-form.input name="phone" type="tel" label="Phone" maxlength="30" />
                <x-form.input name="national_id" label="CNIC / ID" maxlength="40" />
                <x-form.input name="purpose" label="Purpose" required maxlength="150" placeholder="e.g. Fee query, meet teacher" />
                <x-form.input name="to_meet" label="To meet" maxlength="120" />
                <x-form.input name="people" type="number" min="1" max="50" label="People" value="1" />
                <button class="btn btn-primary w-full">Sign in</button>
            </div>
        </form>
    </div>
</x-layouts.school>
