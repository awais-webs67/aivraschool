@props(['title' => null])
<!DOCTYPE html>
<html lang="en" class="h-full scroll-pt-20">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="description" content="Skooline by AivraSol is a complete, cloud-based school management system: admissions, attendance, fees, exams, HR, transport and more, with a secure portal for every school.">
    <meta property="og:title" content="Skooline — The Complete School ERP by AivraSol">
    <meta property="og:description" content="Run your whole school from one place. Every school gets its own secure portal.">
    <meta property="og:type" content="website">
</head>
<body class="bg-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    <header class="sticky top-0 z-40 border-b border-line/80 bg-white/90 backdrop-blur">
        <div class="container-x flex h-16 items-center gap-6 px-4 sm:px-6 lg:h-20 lg:px-8">
            <a href="{{ route('home') }}" aria-label="Skooline home"><x-logo tagline /></a>

            <nav class="ml-6 hidden items-center gap-1 lg:flex" aria-label="Main">
                @foreach (['#features' => 'Features', '#modules' => 'Modules', '#pricing' => 'Pricing', '#security' => 'Security', '#faq' => 'FAQ'] as $href => $label)
                    <a href="{{ $href }}" class="rounded px-3 py-2 text-sm font-medium text-body hover:bg-canvas hover:text-ink">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-2 lg:flex">
                <button type="button" class="btn btn-ghost" data-toggle="#school-finder" aria-expanded="false">School login</button>
                <a href="#demo" class="btn btn-primary">Book a free demo</a>
            </div>

            <button type="button" class="btn-ghost ml-auto rounded p-2 lg:hidden" data-toggle="#mobile-menu" data-lock-scroll aria-label="Open menu" aria-expanded="false">
                <x-icon name="menu" size="6" />
            </button>
        </div>

        {{-- School login finder: schools sign in on their own address --}}
        <div id="school-finder" hidden class="border-t border-line bg-canvas">
            <form class="container-x flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:px-6 lg:px-8" data-school-finder data-domain="{{ config('skooline.school_domain') }}" data-base="{{ config('app.url') }}">
                <label for="finder-subdomain" class="text-sm font-medium text-ink">Your school's address</label>
                <div class="flex flex-1 sm:max-w-md">
                    <input id="finder-subdomain" class="input rounded-r-none" placeholder="yourschool" autocomplete="off" spellcheck="false" required>
                    <span class="inline-flex items-center rounded-r border border-l-0 border-line-strong bg-white px-3 text-sm text-muted">.{{ config('skooline.school_domain') }}</span>
                </div>
                <button class="btn btn-dark">Go to login <x-icon name="arrow-right" size="4" /></button>
            </form>
        </div>
    </header>

    <div id="mobile-menu" data-drawer hidden class="fixed inset-0 z-50 lg:hidden">
        <button type="button" class="absolute inset-0 bg-ink/60" data-toggle="#mobile-menu" aria-label="Close menu"></button>
        <div class="absolute inset-y-0 right-0 flex w-80 max-w-[85%] flex-col bg-white shadow-pop">
            <div class="flex h-16 items-center justify-between border-b border-line px-4">
                <x-logo />
                <button type="button" class="btn-ghost rounded p-2" data-toggle="#mobile-menu" aria-label="Close menu"><x-icon name="x" size="6" /></button>
            </div>
            <nav class="flex-1 space-y-1 overflow-y-auto p-4" aria-label="Mobile">
                @foreach (['#features' => 'Features', '#modules' => 'Modules', '#pricing' => 'Pricing', '#security' => 'Security', '#faq' => 'FAQ', '#demo' => 'Contact'] as $href => $label)
                    <a href="{{ $href }}" class="block rounded px-3 py-3 text-base font-medium text-ink hover:bg-canvas">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="space-y-2 border-t border-line p-4">
                <a href="#school-finder" class="btn btn-secondary w-full" onclick="document.getElementById('school-finder').hidden = false">School login</a>
                <a href="#demo" class="btn btn-primary w-full">Book a free demo</a>
            </div>
        </div>
    </div>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="bg-ink text-white">
        <div class="container-x grid gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
            <div class="lg:col-span-2">
                <x-logo dark tagline />
                <p class="mt-4 max-w-sm text-sm text-white/60">The complete school management system. Admissions to report cards, fees to payroll, all in one place for every school.</p>
                <p class="mt-6 text-sm text-white/60">A product of <a href="{{ config('skooline.company_url') }}" class="font-semibold text-white hover:text-brand" target="_blank" rel="noopener">AivraSol</a>, software development company.</p>
            </div>
            <div>
                <p class="text-sm font-semibold">Product</p>
                <ul class="mt-4 space-y-2 text-sm text-white/60">
                    @foreach (['#features' => 'Features', '#modules' => 'All modules', '#pricing' => 'Pricing', '#security' => 'Security', '#faq' => 'FAQ'] as $href => $label)
                        <li><a href="{{ $href }}" class="hover:text-white">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Contact</p>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    <li class="flex items-center gap-2"><x-icon name="mail" size="4" /><a href="mailto:{{ config('skooline.sales_email') }}" class="hover:text-white">{{ config('skooline.sales_email') }}</a></li>
                    <li class="flex items-center gap-2"><x-icon name="chat" size="4" /><a href="mailto:{{ config('skooline.support_email') }}" class="hover:text-white">{{ config('skooline.support_email') }}</a></li>
                    <li class="flex items-center gap-2"><x-icon name="globe" size="4" /><a href="{{ config('skooline.company_url') }}" class="hover:text-white" target="_blank" rel="noopener">aivrasol.com</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="container-x flex flex-col gap-2 px-4 py-6 text-xs text-white/40 sm:flex-row sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} AivraSol. All rights reserved.</p>
                <p>Skooline is a trademark of AivraSol.</p>
            </div>
        </div>
    </footer>
</body>
</html>
