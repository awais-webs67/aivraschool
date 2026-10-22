@props(['homeUrl' => null])
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head')
</head>
<body class="min-h-full">
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        <aside class="relative hidden overflow-hidden bg-ink p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
            <div aria-hidden="true" class="bg-grid-dark pointer-events-none absolute inset-0 [mask-image:linear-gradient(to_bottom,#000,transparent)]"></div>
            <div aria-hidden="true" class="glow animate-float-slow -right-24 -bottom-24 size-96 bg-brand/25"></div>
            <div aria-hidden="true" class="glow animate-float -top-24 -left-24 size-72 bg-info/10"></div>
            <a href="{{ $homeUrl ?? '#' }}" class="relative"><x-logo dark tagline /></a>
            <div data-reveal class="relative max-w-md">
                {{ $aside }}
            </div>
            <p class="relative text-xs text-white/40">&copy; {{ date('Y') }} AivraSol. All rights reserved.</p>
        </aside>

        <main class="flex flex-col justify-center px-4 py-10 sm:px-8">
            <div data-reveal class="mx-auto w-full max-w-sm">
                <div class="mb-8 lg:hidden"><x-logo tagline /></div>
                <div class="mb-6 space-y-3 empty:hidden"><x-flash /></div>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
