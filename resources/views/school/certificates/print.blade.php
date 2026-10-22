@php $s = $c->student; $d = $c->details ?? []; $he = $s->gender === 'female' ? ['She', 'her', 'daughter'] : ['He', 'his', 'son']; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => \App\Models\Certificate::TYPES[$c->type].' · '.$s->name, 'index' => false])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .sheet { box-shadow: none !important; } @page { size: A4; margin: 12mm; } }</style>
</head>
<body class="bg-canvas p-4">
    <div class="no-print mx-auto mb-4 flex max-w-3xl justify-between gap-2">
        <a href="{{ route('school.certificates.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" size="4" /> Back</a>
        <button type="button" onclick="window.print()" class="btn btn-primary"><x-icon name="printer" size="4" /> Print</button>
    </div>
    <main class="sheet mx-auto max-w-3xl border-8 border-double border-ink bg-white p-8 text-ink shadow-card sm:p-12">
        <header class="text-center">
            <x-school-badge :school="$school" size="lg" class="mx-auto" />
            <h1 class="mt-3 text-2xl font-bold break-words">{{ $school->name }}</h1>
            <p class="text-sm text-muted">{{ collect([$school->address, $school->city, $school->phone])->filter()->join(' · ') }}</p>
            <p class="font-display mt-6 inline-block border-b-2 border-ink pb-1 text-2xl tracking-wide">{{ \App\Models\Certificate::TYPES[$c->type] }}</p>
        </header>
        <div class="mt-4 flex justify-between text-sm"><span>Serial no. <span class="font-mono font-semibold">{{ $c->serial_no }}</span></span><span>Date: {{ $c->issued_on->format('d M Y') }}</span></div>

        <div class="mt-8 space-y-5 text-base leading-loose">
            @if ($c->type === 'leaving')
                <p>This is to certify that <strong>{{ $s->name }}</strong>, {{ $he[2] }} of <strong>{{ $s->father_name ?? '—' }}</strong>, admission no. <strong>{{ $s->admission_no }}</strong>, was a student of this school from <strong>{{ $s->admission_date?->format('d M Y') ?? '—' }}</strong> to <strong>{{ ($s->left_on ?? $c->issued_on)->format('d M Y') }}</strong>.</p>
                <p>{{ $he[0] }} last studied in <strong>{{ $d['last_class'] ?? $s->classLabel() }}</strong>. {{ $he[1] === 'her' ? 'Her' : 'His' }} date of birth as per school record is <strong>{{ $s->date_of_birth?->format('d M Y') ?? '—' }}</strong>.</p>
                <p>Reason for leaving: <strong>{{ $d['reason'] ?? '—' }}</strong>. Conduct: <strong>{{ $d['conduct'] ?? 'Good' }}</strong>. All school dues have been cleared.</p>
            @elseif ($c->type === 'character')
                <p>This is to certify that <strong>{{ $s->name }}</strong>, {{ $he[2] }} of <strong>{{ $s->father_name ?? '—' }}</strong>, admission no. <strong>{{ $s->admission_no }}</strong>, has been a student of this school in <strong>{{ $d['last_class'] ?? $s->classLabel() }}</strong>.</p>
                <p>To the best of our knowledge {{ strtolower($he[0]) }} bears a <strong>{{ strtolower($d['conduct'] ?? 'good') }}</strong> moral character. We wish {{ $he[1] === 'her' ? 'her' : 'him' }} every success in life.</p>
            @else
                <p>This is to certify that <strong>{{ $s->name }}</strong>, {{ $he[2] }} of <strong>{{ $s->father_name ?? '—' }}</strong>, admission no. <strong>{{ $s->admission_no }}</strong>, is a bonafide student of this school, studying in <strong>{{ $s->classLabel() }}</strong> in the current session.</p>
                <p>{{ $he[1] === 'her' ? 'Her' : 'His' }} date of birth as per school record is <strong>{{ $s->date_of_birth?->format('d M Y') ?? '—' }}</strong>. {{ $he[0] }} bears a {{ strtolower($d['conduct'] ?? 'good') }} character.</p>
            @endif
            @if (! empty($d['remarks']))<p>Remarks: {{ $d['remarks'] }}</p>@endif
        </div>

        <footer class="mt-24 grid grid-cols-2 gap-10 text-center text-sm">
            <p class="border-t border-ink pt-1">Prepared by</p>
            <p class="border-t border-ink pt-1">Principal (signature & stamp)</p>
        </footer>
    </main>
</body>
</html>
