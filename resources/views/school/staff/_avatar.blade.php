{{-- Staff photo or initials. $m = staff member, $size = Tailwind size class --}}
@if ($m->photo_path)
    <img src="{{ route('school.staff.photo', $m) }}" alt="" class="{{ $size ?? 'size-10' }} shrink-0 rounded-full object-cover" loading="lazy">
@else
    <span class="{{ $size ?? 'size-10' }} grid shrink-0 place-items-center rounded-full bg-brand-tint text-sm font-bold text-brand-text" aria-hidden="true">{{ $m->initials() }}</span>
@endif
