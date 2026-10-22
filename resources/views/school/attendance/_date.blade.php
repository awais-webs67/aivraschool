{{-- Day picker with previous / next buttons. $date, $route --}}
<form method="GET" action="{{ route($route) }}" class="flex flex-wrap items-center gap-2">
    <a href="{{ route($route, ['date' => $date->copy()->subDay()->toDateString()]) }}" class="btn btn-secondary btn-sm" aria-label="Previous day"><x-icon name="arrow-left" size="4" /></a>
    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="input w-auto" aria-label="Date" onchange="this.form.submit()">
    @if ($date->lt(today()))
        <a href="{{ route($route, ['date' => $date->copy()->addDay()->toDateString()]) }}" class="btn btn-secondary btn-sm" aria-label="Next day"><x-icon name="arrow-right" size="4" /></a>
        <a href="{{ route($route) }}" class="btn btn-ghost btn-sm">Today</a>
    @endif
    <span class="text-sm font-medium text-ink">{{ $date->format('l, d M Y') }}</span>
</form>
