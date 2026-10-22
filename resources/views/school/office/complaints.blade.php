<x-layouts.school heading="Front office" subheading="Complaints and suggestions from parents and visitors.">
    @include('school.office._nav')
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <nav class="flex gap-2">
                @foreach (['open' => 'Open ('.$openCount.')', 'resolved' => 'Resolved', 'all' => 'All'] as $k => $l)
                    <a href="{{ route('school.visitors.complaints', ['status' => $k]) }}" class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-secondary' }}">{{ $l }}</a>
                @endforeach
            </nav>
            <section class="card">
                <ul class="divide-y divide-line">
                    @forelse ($complaints as $c)
                        <li class="px-5 py-4">
                            <p class="flex flex-wrap items-center gap-2"><span class="font-semibold text-ink">{{ $c->from_name }}</span><span class="badge {{ $c->kind === 'suggestion' ? 'badge-info' : 'badge-neutral' }}">{{ ucfirst($c->kind) }}</span><span class="badge {{ $c->statusBadge() }}">{{ \App\Models\Complaint::STATUSES[$c->status] }}</span></p>
                            <p class="text-xs text-muted">{{ $c->created_at->format('d M Y') }}{{ $c->category ? ' · '.$c->category : '' }}{{ $c->phone ? ' · '.$c->phone : '' }}</p>
                            <p class="mt-1 text-sm break-words whitespace-pre-line text-body">{{ $c->details }}</p>
                            <form method="POST" action="{{ route('school.visitors.complaints.update', $c) }}" class="mt-3 grid gap-2 sm:grid-cols-[1fr_10rem_auto]">
                                @csrf
                                @method('PUT')
                                <input type="text" name="response" value="{{ $c->response }}" maxlength="3000" class="input py-1.5 text-sm" placeholder="Action taken / reply" aria-label="Response">
                                <select name="status" class="input py-1.5 text-sm" aria-label="Status">@foreach (\App\Models\Complaint::STATUSES as $v => $l)<option value="{{ $v }}" @selected($c->status === $v)>{{ $l }}</option>@endforeach</select>
                                <button class="btn btn-secondary btn-sm">Save</button>
                            </form>
                        </li>
                    @empty
                        <li class="px-5 py-12 text-center text-sm text-muted">Nothing here.</li>
                    @endforelse
                </ul>
                @if ($complaints->hasPages())<div class="border-t border-line px-4 py-3">{{ $complaints->links() }}</div>@endif
            </section>
        </div>
        <form method="POST" action="{{ route('school.visitors.complaints.store') }}" class="card self-start">
            @csrf
            <div class="card-header"><h2 class="card-title">Record new</h2></div>
            <div class="card-body space-y-3">
                <x-form.select name="kind" label="Type" :options="['complaint' => 'Complaint', 'suggestion' => 'Suggestion']" value="complaint" />
                <x-form.input name="from_name" label="From" required maxlength="120" />
                <x-form.input name="phone" type="tel" label="Phone" maxlength="30" />
                <x-form.input name="category" label="About" list="complaint-cats" maxlength="40" />
                <datalist id="complaint-cats">@foreach (\App\Models\Complaint::CATEGORIES as $cat)<option value="{{ $cat }}">@endforeach</datalist>
                <x-form.textarea name="details" label="Details" rows="4" required />
                <button class="btn btn-primary w-full">Save</button>
            </div>
        </form>
    </div>
</x-layouts.school>
