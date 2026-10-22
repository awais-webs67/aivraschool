<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => 'ID cards · '.$section->label(), 'index' => false])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .id-card { break-inside: avoid; box-shadow: none !important; } @page { size: A4; margin: 8mm; } }</style>
</head>
<body class="bg-canvas p-4">
    <div class="no-print mx-auto mb-4 flex max-w-4xl items-center justify-between gap-2">
        <p class="text-sm text-body">{{ $students->count() }} ID cards · {{ $section->label() }}</p>
        <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" size="4" /> Print</button>
    </div>
    <div class="mx-auto grid max-w-4xl grid-cols-1 gap-4 sm:grid-cols-2">
        @forelse ($students as $s)
            <div class="id-card overflow-hidden rounded-xl border border-line bg-white text-ink shadow-card" style="aspect-ratio: 85.6 / 54;">
                <div class="flex items-center gap-2 bg-ink px-3 py-2 text-white">
                    <x-school-badge :school="$school" size="sm" />
                    <p class="min-w-0 truncate text-xs font-bold">{{ $school->name }}</p>
                    <span class="ml-auto shrink-0 rounded bg-brand px-1.5 py-0.5 text-[9px] font-bold tracking-wider">STUDENT</span>
                </div>
                <div class="flex gap-3 p-3">
                    @if ($s->photo_path)
                        <img src="{{ route('school.students.photo', $s) }}" alt="" class="h-24 w-20 shrink-0 rounded object-cover">
                    @else
                        <span class="grid h-24 w-20 shrink-0 place-items-center rounded bg-chip text-xl font-bold text-muted">{{ $s->initials() }}</span>
                    @endif
                    <dl class="min-w-0 flex-1 space-y-0.5 text-[11px] leading-tight">
                        <dd class="truncate text-sm font-bold">{{ $s->name }}</dd>
                        <div class="flex gap-1"><dt class="text-muted">Class</dt><dd class="truncate font-semibold">{{ $s->classLabel() }}</dd></div>
                        <div class="flex gap-1"><dt class="text-muted">Adm.</dt><dd class="font-mono">{{ $s->admission_no }}</dd></div>
                        <div class="flex gap-1"><dt class="text-muted">Father</dt><dd class="truncate">{{ $s->father_name ?? '—' }}</dd></div>
                        <div class="flex gap-1"><dt class="text-muted">Phone</dt><dd>{{ $s->parentPhone() ?? '—' }}</dd></div>
                        <div class="flex gap-1"><dt class="text-muted">Valid till</dt><dd>{{ $validTill }}</dd></div>
                    </dl>
                    <div class="size-16 shrink-0 self-end" data-qr="{{ $school->url().'?student='.$s->admission_no }}" aria-hidden="true"></div>
                </div>
            </div>
        @empty
            <p class="text-sm text-muted">No students in this section.</p>
        @endforelse
    </div>
</body>
</html>
