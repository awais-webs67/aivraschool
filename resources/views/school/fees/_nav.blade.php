<nav class="mb-6 flex gap-1 overflow-x-auto border-b border-line" aria-label="Fees">
    @foreach ([
        'school.fees.index' => 'Overview',
        'school.fees.search' => 'Collect fee',
        'school.fees.invoices' => 'Vouchers',
        'school.fees.defaulters' => 'Defaulters',
        'school.fees.generate' => 'Make vouchers',
        'school.fees.setup' => 'Fee structure',
    ] as $route => $label)
        <a href="{{ route($route) }}" class="-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold whitespace-nowrap {{ request()->routeIs($route) || ($route === 'school.fees.search' && request()->routeIs('school.fees.student')) ? 'border-brand text-ink' : 'border-transparent text-muted hover:text-ink' }}">{{ $label }}</a>
    @endforeach
</nav>
