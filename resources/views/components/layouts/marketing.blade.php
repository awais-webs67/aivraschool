@props(['title' => null, 'description' => null])
@php
    $links = ['#features' => 'Features', '#modules' => 'Modules', '#pricing' => 'Pricing', '#security' => 'Security', '#faq' => 'FAQ'];
    $appHost = preg_replace('#^https?://#', '', rtrim(config('app.url'), '/'));
@endphp
<!DOCTYPE html>
<html lang="en" class="scroll-pt-20">
<head>
    @include('partials.head', ['title' => $title, 'description' => $description, 'index' => true])
    @stack('head')
</head>
<body class="overflow-x-hidden bg-white">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    <header data-scroll-header class="sticky top-0 z-40 border-b border-transparent bg-white/80 backdrop-blur-xl transition duration-300 data-scrolled:border-line data-scrolled:shadow-[0_6px_24px_-12px_rgba(17,22,27,.18)]">
        <div class="container-x flex h-16 items-center gap-4 px-4 sm:px-6 lg:h-[4.5rem] lg:px-8">
            <a href="{{ route('home') }}" aria-label="Skooline home"><x-logo tagline /></a>

            <nav class="ml-8 hidden items-center gap-1 lg:flex" aria-label="Main">
                @foreach ($links as $href => $label)
                    <a href="{{ $href }}" class="group relative rounded px-3 py-2 text-sm font-medium text-body transition hover:text-ink">
                        {{ $label }}
                        <span aria-hidden="true" class="absolute inset-x-3 -bottom-0.5 h-0.5 origin-left scale-x-0 rounded-full bg-brand transition duration-300 group-hover:scale-x-100"></span>
                    </a>
                @endforeach
            </nav>

            <div class="ml-auto hidden items-center gap-2 lg:flex">
                <button type="button" class="btn btn-ghost" data-toggle="#school-finder" aria-expanded="false" aria-controls="school-finder">
                    <x-icon name="key" size="4" /> School login
                </button>
                <a href="#demo" class="btn btn-primary">Book a free demo</a>
            </div>

            <button type="button" class="hamburger ml-auto lg:hidden" data-drawer-open="#mobile-menu" aria-controls="mobile-menu" aria-expanded="false" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>

        {{-- Desktop: find your school's login page --}}
        <div id="school-finder" hidden class="border-t border-line bg-canvas/95">
            <form class="container-x flex flex-wrap items-center gap-3 px-4 py-4 sm:px-6 lg:px-8" data-school-finder data-base="{{ rtrim(config('app.url'), '/') }}">
                <label for="finder-slug" class="text-sm font-semibold text-ink">Go to your school's login</label>
                <div class="flex min-w-0 flex-1 sm:max-w-md">
                    <span class="inline-flex max-w-[55%] items-center truncate rounded-l border border-r-0 border-line-strong bg-white px-3 text-sm text-muted">{{ $appHost }}/</span>
                    <input id="finder-slug" class="input min-w-0 rounded-l-none" placeholder="yourschool" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                </div>
                <button class="btn btn-dark">Continue <x-icon name="arrow-right" size="4" /></button>
            </form>
        </div>
    </header>

    {{-- Mobile menu --}}
    <div id="mobile-menu" class="drawer drawer-right lg:hidden" role="dialog" aria-modal="true" aria-label="Menu">
        <div class="drawer-backdrop" data-drawer-close></div>
        <div class="drawer-panel bg-white">
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-line px-4">
                <x-logo />
                <button type="button" class="drawer-close text-ink hover:bg-chip" data-drawer-close aria-label="Close menu"><x-icon name="x" size="6" /></button>
            </div>
            <nav class="flex-1 overflow-y-auto p-3" aria-label="Mobile">
                @foreach ($links + ['#demo' => 'Contact'] as $href => $label)
                    <a href="{{ $href }}" class="flex items-center justify-between rounded-md px-3 py-3.5 text-base font-semibold text-ink transition hover:bg-canvas">
                        {{ $label }} <x-icon name="chevron-right" size="4" class="text-muted" />
                    </a>
                @endforeach

                <form class="mt-4 rounded-md border border-line bg-canvas p-4" data-school-finder data-base="{{ rtrim(config('app.url'), '/') }}">
                    <label for="finder-slug-m" class="text-sm font-semibold text-ink">School login</label>
                    <p class="mt-0.5 text-xs text-muted">Enter your school's address.</p>
                    <div class="mt-3 flex">
                        <span class="inline-flex max-w-[55%] items-center truncate rounded-l border border-r-0 border-line-strong bg-white px-2.5 text-xs text-muted">{{ $appHost }}/</span>
                        <input id="finder-slug-m" class="input min-w-0 rounded-l-none" placeholder="yourschool" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                    </div>
                    <button class="btn btn-dark mt-3 w-full">Go to login <x-icon name="arrow-right" size="4" /></button>
                </form>
            </nav>
            <div class="shrink-0 border-t border-line p-4">
                <a href="#demo" class="btn btn-primary btn-lg w-full">Book a free demo</a>
            </div>
        </div>
    </div>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="relative overflow-hidden bg-ink text-white">
        <div aria-hidden="true" class="glow -top-40 left-1/2 size-[30rem] -translate-x-1/2 bg-brand/15"></div>
        <div class="container-x relative grid gap-10 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-[1.6fr_1fr_1fr] lg:px-8">
            <div class="sm:col-span-2 lg:col-span-1">
                <x-logo dark tagline />
                <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/60">The all-in-one school management system. From admissions to report cards, fees to payroll, every school runs on one simple platform.</p>
                <p class="mt-6 text-sm text-white/60">A product of <a href="{{ config('skooline.company_url') }}" class="font-semibold text-white transition hover:text-brand" target="_blank" rel="noopener">AivraSol</a>.</p>
            </div>
            <div>
                <p class="text-sm font-semibold">Product</p>
                <ul class="mt-4 space-y-2.5 text-sm text-white/60">
                    @foreach ($links as $href => $label)
                        <li><a href="{{ $href }}" class="transition hover:text-white">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold">Contact</p>
                <ul class="mt-4 space-y-3 text-sm text-white/60">
                    <li class="flex items-center gap-2"><x-icon name="mail" size="4" class="shrink-0" /><a href="mailto:{{ config('skooline.sales_email') }}" class="truncate transition hover:text-white">{{ config('skooline.sales_email') }}</a></li>
                    <li class="flex items-center gap-2"><x-icon name="chat" size="4" class="shrink-0" /><a href="mailto:{{ config('skooline.support_email') }}" class="truncate transition hover:text-white">{{ config('skooline.support_email') }}</a></li>
                    <li class="flex items-center gap-2"><x-icon name="globe" size="4" class="shrink-0" /><a href="{{ config('skooline.company_url') }}" class="transition hover:text-white" target="_blank" rel="noopener">aivrasol.com</a></li>
                </ul>
            </div>
        </div>
        <div class="relative border-t border-white/10">
            <div class="container-x flex flex-col gap-2 px-4 py-6 text-xs text-white/45 sm:flex-row sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ date('Y') }} AivraSol. All rights reserved.</p>
                <p>Skooline is a trademark of AivraSol.</p>
            </div>
        </div>
    </footer>
</body>
</html>
