{{-- Student photo or initials. $s = student, $size = Tailwind size classes --}}
@if ($s->photo_path)
    <img src="{{ route('school.students.photo', $s) }}" alt="" class="{{ $size ?? 'size-10' }} shrink-0 rounded-full object-cover" loading="lazy">
@else
    <span class="{{ $size ?? 'size-10' }} grid shrink-0 place-items-center rounded-full {{ $s->gender === 'female' ? 'bg-danger-tint text-danger' : 'bg-info-tint text-info' }} text-sm font-bold" aria-hidden="true">{{ $s->initials() }}</span>
@endif
