<x-layouts.school :heading="$report[0]" :subheading="$report[1]">
    <x-slot:actions>
        <a href="{{ route('school.reports.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Reports</a>
        <a data-no-progress href="{{ route('school.reports.show', [$key, 'month' => $month->format('Y-m'), 'export' => 1]) }}" class="btn btn-secondary"><x-icon name="download" size="4" /> CSV</a>
        <button type="button" onclick="window.print()" class="btn btn-secondary"><x-icon name="printer" size="4" /> Print</button>
    </x-slot:actions>

    @if ($key !== 'strength')
        <form method="GET" class="mb-4 flex items-center gap-2">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" class="input w-auto" aria-label="Month" onchange="this.form.submit()">
            <span class="text-sm text-muted">{{ $month->format('F Y') }}</span>
        </form>
    @endif

    <div class="card overflow-x-auto">
        @if ($rows->isEmpty())
            <p class="px-5 py-14 text-center text-sm text-muted">No data for this period.</p>
        @else
            <table class="w-full min-w-[32rem] text-sm">
                <thead><tr class="border-b border-line bg-canvas text-left text-xs text-muted">@foreach ($columns as $i => $c)<th class="px-4 py-2.5 font-semibold {{ $i ? 'text-right' : '' }}">{{ $c }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-b border-line {{ $loop->last && $row[0] === 'Total' ? 'bg-canvas font-semibold' : '' }}">
                            @foreach ($row as $i => $cell)<td class="px-4 py-2 {{ $i ? 'text-right tabular' : 'font-medium text-ink' }}">{{ is_float($cell) ? number_format($cell, $key === 'fees' ? 0 : 1) : $cell }}</td>@endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-layouts.school>
