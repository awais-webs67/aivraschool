<x-layouts.admin heading="Subscriptions" subheading="Every billing period across all schools.">
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['' => 'All', 'unpaid' => 'Unpaid', 'paid' => 'Paid', 'partial' => 'Partial', 'waived' => 'Waived'] as $value => $label)
            <a href="{{ route('admin.subscriptions.index', array_filter(['payment_status' => $value])) }}"
                class="btn btn-sm {{ (string) $status === (string) $value ? 'btn-dark' : 'btn-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        @if ($subscriptions->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No subscriptions found.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>School</th><th>Plan</th><th>Period</th><th>Amount</th><th>Payment</th></tr></thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr>
                                <td>
                                    @if ($subscription->school)
                                        <a href="{{ route('admin.schools.show', $subscription->school) }}" class="font-medium text-ink hover:text-brand-text">{{ $subscription->school->name }}</a>
                                    @else
                                        <span class="text-muted">Deleted school</span>
                                    @endif
                                </td>
                                <td>{{ $subscription->plan_name }}</td>
                                <td class="tabular">{{ $subscription->starts_at->format('d M Y') }} – {{ $subscription->ends_at?->format('d M Y') ?? 'Lifetime' }}</td>
                                <td class="tabular font-medium text-ink">{{ money($subscription->amount) }}</td>
                                <td><span class="badge {{ $subscription->payment_status === 'paid' ? 'badge-success' : ($subscription->payment_status === 'unpaid' ? 'badge-danger' : 'badge-neutral') }}">{{ ucfirst($subscription->payment_status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($subscriptions->hasPages())
                <div class="border-t border-line px-4 py-3">{{ $subscriptions->links() }}</div>
            @endif
        @endif
    </div>
</x-layouts.admin>
