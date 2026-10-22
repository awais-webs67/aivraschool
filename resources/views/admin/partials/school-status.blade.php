{{-- Status badge + access hint for a school --}}
@php $days = $school->daysLeft(); @endphp
<span class="badge {{ $school->status->badge() }}">{{ $school->status->label() }}</span>
@if ($school->inGracePeriod())
    <span class="badge badge-warning">Grace period</span>
@elseif ($days !== null && in_array($school->status->value, ['trial', 'active'], true) && $days >= 0 && $days <= 15)
    <span class="badge badge-neutral">{{ $days === 0 ? 'Ends today' : $days.'d left' }}</span>
@endif
