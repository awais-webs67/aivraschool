<x-layouts.admin heading="Demo requests" subheading="Schools that asked for a demo from the Skooline website.">
    <div class="card">
        @if ($requests->isEmpty())
            <div class="flex flex-col items-center px-4 py-16 text-center">
                <span class="grid size-14 place-items-center rounded-full bg-info-tint text-info"><x-icon name="inbox" size="7" /></span>
                <p class="mt-4 text-lg font-semibold text-ink">No demo requests yet</p>
                <p class="mt-1 text-sm text-muted">Requests from the website form appear here.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Contact</th><th>School</th><th>Message</th><th>Received</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($requests as $lead)
                            <tr>
                                <td>
                                    <p class="font-medium text-ink">{{ $lead->name }}</p>
                                    <p class="text-xs text-muted">{{ $lead->phone }}{{ $lead->email ? ' · '.$lead->email : '' }}</p>
                                </td>
                                <td>
                                    <p class="text-ink">{{ $lead->school_name }}</p>
                                    <p class="text-xs text-muted">{{ collect([$lead->city, $lead->students ? number_format($lead->students).' students' : null])->filter()->join(' · ') }}</p>
                                </td>
                                <td class="max-w-xs"><p class="line-clamp-2 text-sm">{{ $lead->message ?? '—' }}</p></td>
                                <td class="tabular text-xs">{{ $lead->created_at->format('d M Y, h:i A') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.demo-requests.update', $lead) }}">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" class="input w-32 py-1.5 text-xs" onchange="this.form.submit()" aria-label="Lead status">
                                            @foreach (\App\Models\DemoRequest::STATUSES as $status)
                                                <option value="{{ $status }}" @selected($lead->status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                        <noscript><button class="btn btn-secondary btn-sm mt-1">Save</button></noscript>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($requests->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $requests->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
