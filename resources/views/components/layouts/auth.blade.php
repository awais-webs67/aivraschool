@props(['homeUrl' => null])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <aside class="relative hidden overflow-hidden bg-ink p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-[.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -right-24 -bottom-24 size-96 rounded-full bg-brand/20 blur-3xl"></div>
            <a href="{{ $homeUrl ?? '#' }}" class="relative"><x-logo dark tagline /></a>
            <div class="relative max-w-md">
                {{ $aside }}
            </div>
            <p class="relative text-xs text-white/40">&copy; {{ date('Y') }} AivraSol. All rights reserved.</p>
        </aside>

        <main class="flex flex-col justify-center px-4 py-10 sm:px-8">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8 lg:hidden"><x-logo tagline /></div>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
