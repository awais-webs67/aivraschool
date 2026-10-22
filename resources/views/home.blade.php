<x-layouts.marketing title="All-in-One School Management System" description="Skooline is an all-in-one school management system for admissions, attendance, fees, exams, staff, timetable and parent communication. Every school gets its own private portal. Book a free demo.">
    @push('head')
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endpush

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div aria-hidden="true" class="bg-grid pointer-events-none absolute inset-0 [mask-image:radial-gradient(ellipse_at_top,#000_25%,transparent_70%)]"></div>
        <div aria-hidden="true" class="glow animate-float-slow -top-40 -right-40 size-[36rem] bg-brand/20"></div>
        <div aria-hidden="true" class="glow animate-float -bottom-40 -left-40 size-[28rem] bg-info/10"></div>

        <div class="container-x relative grid items-center gap-14 px-4 pt-12 pb-20 sm:px-6 md:pt-20 lg:grid-cols-[1.02fr_1fr] lg:gap-10 lg:px-8 lg:pb-28">
            <div>
                <p data-reveal class="inline-flex max-w-full items-center gap-2 rounded-full border border-brand/25 bg-white/80 py-1 pr-3 pl-1.5 text-xs font-semibold text-ink shadow-sm backdrop-blur">
                    <span class="rounded-full bg-brand px-2 py-0.5 text-[11px] text-white">New</span>
                    <span class="truncate">School management, made simple</span>
                </p>
                <h1 data-reveal style="--delay: 80ms" class="heading-xl mt-6 break-words">{{ $content['hero_title'] }} <span class="text-gradient">{{ $content['hero_highlight'] }}</span></h1>
                <p data-reveal style="--delay: 160ms" class="mt-6 max-w-xl text-lg leading-relaxed text-body">{{ $content['hero_subtitle'] }}</p>
                <div data-reveal style="--delay: 240ms" class="mt-8 flex flex-col gap-3 sm:flex-row">
                    @if (\App\Services\RegistrationService::enabled())
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Register your school <x-icon name="arrow-right" size="4" /></a>
                        <a href="#demo" class="btn btn-secondary btn-lg">Book a free demo</a>
                    @else
                        <a href="#demo" class="btn btn-primary btn-lg">Book a free demo <x-icon name="arrow-right" size="4" /></a>
                        <a href="#pricing" class="btn btn-secondary btn-lg">See pricing</a>
                    @endif
                </div>
                <ul data-reveal style="--delay: 320ms" class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-body">
                    @foreach ($content['hero_points'] as $point)
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="4" class="shrink-0 text-success" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Product preview: an HTML mock of the school dashboard --}}
            <div data-reveal style="--delay: 200ms" class="relative mx-auto w-full max-w-xl lg:max-w-none" role="img" aria-label="Preview of the Skooline school dashboard">
                <div class="relative rounded-xl border border-line bg-white/90 p-2 shadow-pop backdrop-blur">
                    <div class="flex items-center gap-1.5 px-2 pt-1 pb-2.5">
                        <span class="size-2.5 rounded-full bg-[#FF5F57]"></span><span class="size-2.5 rounded-full bg-[#FEBC2E]"></span><span class="size-2.5 rounded-full bg-[#28C840]"></span>
                        <span class="ml-3 flex min-w-0 flex-1 items-center gap-1.5 truncate rounded-full bg-canvas px-3 py-1 text-[10px] text-muted"><x-icon name="lock" size="3" class="shrink-0 text-success" /><span class="truncate">{{ $appHost }}/greenfield</span></span>
                    </div>
                    <div class="flex overflow-hidden rounded-lg border border-line">
                        <div class="hidden w-36 shrink-0 flex-col gap-1 bg-ink p-3 sm:flex">
                            <div class="mb-3 flex items-center gap-1.5"><x-logo-mark class="size-5" /><span class="font-display text-sm text-white">Skoo<span class="text-brand">line</span></span></div>
                            @foreach (['home' => 'Dashboard', 'users' => 'Students', 'check-circle' => 'Attendance', 'banknotes' => 'Fees', 'academic-cap' => 'Exams', 'chat' => 'Messages'] as $icon => $label)
                                <div class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[10.5px] {{ $loop->first ? 'bg-white/10 text-white' : 'text-white/55' }}"><x-icon :name="$icon" size="3" class="shrink-0" /> {{ $label }}</div>
                            @endforeach
                        </div>
                        <div class="min-w-0 flex-1 bg-canvas p-3 sm:p-4">
                            <div class="flex items-center justify-between gap-2">
                                <div class="min-w-0"><p class="truncate text-[10px] text-muted">Greenfield Grammar School</p><p class="font-display truncate text-sm text-ink sm:text-base">Good morning, Principal</p></div>
                                <span class="shrink-0 rounded-md bg-brand px-2 py-1 text-[10px] font-semibold text-white">+ Admission</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                @foreach ([['Students', '1,248', 'text-ink'], ['Present today', '96%', 'text-success'], ['Fees collected', '82%', 'text-brand-text']] as [$label, $value, $tone])
                                    <div class="rounded-md border border-line bg-white p-2"><p class="truncate text-[9px] text-muted">{{ $label }}</p><p class="font-display tabular text-base leading-tight sm:text-xl {{ $tone }}">{{ $value }}</p></div>
                                @endforeach
                            </div>
                            <div class="mt-2 grid gap-2 sm:grid-cols-[1.4fr_1fr]">
                                <div class="rounded-md border border-line bg-white p-2">
                                    <p class="text-[9px] font-semibold text-ink">Fee collection</p>
                                    <div class="mt-2 flex h-20 items-end gap-1.5">
                                        @foreach ([45, 60, 52, 70, 66, 82, 74, 92] as $h)
                                            <span class="animate-grow flex-1 origin-bottom rounded-t {{ $loop->last ? 'bg-gradient-to-t from-brand to-brand-light' : 'bg-brand/20' }}" style="height: {{ $h }}%; animation-delay: {{ 400 + $loop->index * 70 }}ms"></span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="hidden rounded-md border border-line bg-white p-2 sm:block">
                                    <p class="text-[9px] font-semibold text-ink">Today</p>
                                    <ul class="mt-1.5 space-y-1.5 text-[9px] text-body">
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 shrink-0 rounded-full bg-success"></span><span class="truncate">Attendance: 38/40 classes</span></li>
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 shrink-0 rounded-full bg-brand"></span><span class="truncate">126 fee reminders sent</span></li>
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 shrink-0 rounded-full bg-info"></span><span class="truncate">Date sheet published</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="animate-float absolute -bottom-6 -left-4 hidden items-center gap-3 rounded-lg border border-line bg-white px-4 py-3 shadow-pop sm:flex">
                    <span class="grid size-9 place-items-center rounded-full bg-success-tint text-success"><x-icon name="banknotes" size="4" /></span>
                    <div><p class="text-xs text-muted">Fee received</p><p class="text-sm font-semibold text-ink">Grade 7-B · Ayesha K.</p></div>
                </div>
                <div class="animate-float-slow absolute -top-5 -right-3 hidden items-center gap-2.5 rounded-lg border border-line bg-white px-3.5 py-2.5 shadow-pop md:flex">
                    <span class="animate-pulse-dot size-2.5 rounded-full bg-brand"></span>
                    <p class="text-xs font-semibold text-ink">Attendance marked</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Numbers strip --}}
    <section class="border-y border-line bg-canvas">
        <div class="container-x grid grid-cols-2 gap-8 px-4 py-10 sm:px-6 md:grid-cols-4 lg:px-8">
            @foreach ([[count($modules), '+', 'modules in one system'], [1, '', 'private portal per school'], [100, '%', 'separate data per school'], [24, '/7', 'access from any device']] as $i => [$value, $suffix, $label])
                <div data-reveal style="--delay: {{ $i * 80 }}ms" class="text-center md:text-left">
                    <p class="font-display tabular text-4xl text-ink sm:text-5xl"><span data-count="{{ $value }}">{{ $value }}</span><span class="text-brand">{{ $suffix }}</span></p>
                    <p class="mt-1 text-sm text-body">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Who it's for --}}
    <section id="features" class="section">
        <div class="container-x">
            <div data-reveal class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Built for everyone in your school</p>
                <h2 class="heading-lg mt-3">One system. The right screen for every role.</h2>
                <p class="mt-4 text-lg text-body">Each person signs in and sees only what they need, with permissions you control.</p>
            </div>
            {{-- Phones: a swipeable row; larger screens: a grid --}}
            <div class="-mx-4 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:mt-14 sm:grid sm:grid-cols-2 sm:gap-5 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4">
                @foreach ([
                    ['building', 'Principal & admin', 'Live dashboards for enrolment, attendance, collections and results across every class and campus.'],
                    ['academic-cap', 'Teachers', 'Mark attendance in seconds, post homework, enter marks and message parents from any device.'],
                    ['calculator', 'Accounts & office', 'Generate fee invoices in bulk, record payments, issue receipts and follow up on dues.'],
                    ['users', 'Parents & students', 'Attendance, homework, results, invoices and school notices, always up to date.'],
                ] as $i => [$icon, $title, $text])
                    <div data-reveal style="--delay: {{ $i * 90 }}ms" class="group card card-hover relative w-[78%] shrink-0 snap-start overflow-hidden p-6 sm:w-auto">
                        <div aria-hidden="true" class="absolute -top-12 -right-12 size-32 rounded-full bg-brand/0 blur-2xl transition duration-500 group-hover:bg-brand/15"></div>
                        <span class="relative grid size-12 place-items-center rounded-lg bg-ink text-white transition duration-300 group-hover:rotate-[-6deg] group-hover:bg-brand"><x-icon :name="$icon" size="6" /></span>
                        <h3 class="relative mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                        <p class="relative mt-2 text-sm leading-relaxed text-body">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Feature spotlights --}}
    <section class="section bg-canvas">
        <div class="container-x space-y-14 lg:space-y-24">
            @foreach ([
                [
                    'eyebrow' => 'Fees & finance', 'title' => 'Collect fees faster. Chase dues less.',
                    'text' => 'Invoices for the whole school in one click, with sibling discounts, scholarships, late fees and instant receipts, in your own currency.',
                    'points' => ['Bulk monthly invoices and family statements', 'Late fees, discounts and instalments applied automatically', 'Payment reminders to parents', 'Daily collection and outstanding reports'],
                    'icon' => 'banknotes',
                ],
                [
                    'eyebrow' => 'Academics', 'title' => 'From attendance to report card, without spreadsheets.',
                    'text' => 'Teachers enter attendance and marks once. Skooline calculates grades, positions and report cards in your school\'s own format.',
                    'points' => ['Daily and period-wise attendance with absence alerts', 'Exams, grading, GPA and positions', 'Printable report cards, admit cards and date sheets', 'Homework, diary and timetable for every class'],
                    'icon' => 'academic-cap',
                ],
                [
                    'eyebrow' => 'Communication', 'title' => 'Keep every parent in the loop.',
                    'text' => 'Send notices, fee reminders, results and absence alerts to one class or the whole school, in a few clicks.',
                    'points' => ['Bulk SMS, WhatsApp and email messages', 'Notice board and circulars', 'Automatic alerts for absence, fees and results', 'Parent portal on any phone'],
                    'icon' => 'chat',
                ],
            ] as $spot)
                <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div data-reveal class="{{ $loop->even ? 'lg:order-2' : '' }}">
                        <p class="eyebrow">{{ $spot['eyebrow'] }}</p>
                        <h3 class="heading-lg mt-3">{{ $spot['title'] }}</h3>
                        <p class="mt-4 text-lg leading-relaxed text-body">{{ $spot['text'] }}</p>
                        <ul class="mt-7 space-y-3.5">
                            @foreach ($spot['points'] as $point)
                                <li class="flex items-start gap-3 text-ink"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-brand text-white"><x-icon name="check" size="3" /></span>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div data-reveal style="--delay: 120ms" class="hidden lg:block {{ $loop->even ? 'lg:order-1' : '' }}">
                        <div class="relative overflow-hidden rounded-xl bg-ink p-5 sm:p-10">
                            <div aria-hidden="true" class="bg-grid-dark absolute inset-0"></div>
                            <div aria-hidden="true" class="glow animate-float-slow -top-20 -right-16 size-72 bg-brand/30"></div>
                            <div class="relative rounded-lg bg-white p-4 shadow-pop transition duration-500 hover:-rotate-1 sm:p-5">
                                <div class="flex items-center gap-3 border-b border-line pb-4">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-tint text-brand-text"><x-icon :name="$spot['icon']" /></span>
                                    <div class="min-w-0"><p class="truncate font-semibold text-ink">{{ $spot['eyebrow'] }}</p><p class="truncate text-xs text-muted">Greenfield Grammar School</p></div>
                                </div>
                                <ul class="mt-4 space-y-2.5">
                                    @foreach ($spot['points'] as $i => $point)
                                        <li class="flex items-center justify-between gap-3 rounded-md border border-line px-3 py-2.5 text-sm transition hover:border-brand/30 hover:bg-brand-tint/40">
                                            <span class="truncate text-body">{{ $point }}</span>
                                            <span class="badge shrink-0 {{ ['badge-success', 'badge-brand', 'badge-info', 'badge-neutral'][$i % 4] }}">{{ ['Done', 'Auto', 'Live', 'Report'][$i % 4] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Each school's own address --}}
    <section class="section">
        <div class="container-x grid items-center gap-12 lg:grid-cols-2">
            <div data-reveal>
                <p class="eyebrow">Your own school portal</p>
                <h2 class="heading-lg mt-3">Every school gets its own web address.</h2>
                <p class="mt-4 text-lg leading-relaxed text-body">The moment your school joins, it gets a private portal at its own link, with your name and logo on the login page. Staff, parents and students sign in there, and nobody else's data is ever mixed with yours.</p>
                <ul class="mt-7 space-y-3.5">
                    @foreach (['Your school name and logo on the login page', 'Separate logins for staff, parents and students', 'Data kept completely apart from every other school'] as $point)
                        <li class="flex items-start gap-3 text-ink"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-success text-white"><x-icon name="check" size="3" /></span>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
            <div data-reveal style="--delay: 120ms" class="hidden space-y-3 sm:block">
                @foreach (['greenfield' => ['Greenfield Grammar School', 'G', 'bg-ink'], 'riverside' => ['Riverside Academy', 'R', 'bg-info'], 'yourschool' => ['Your School', '+', 'bg-brand']] as $slug => [$name, $letter, $bg])
                    <div class="card card-hover flex items-center gap-4 p-4 {{ $loop->last ? 'border-dashed border-brand/50 bg-brand-tint/30' : '' }}" style="margin-left: {{ $loop->index * 6 }}%">
                        <span class="font-display grid size-11 shrink-0 place-items-center rounded-lg text-lg text-white {{ $bg }}">{{ $letter }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ $name }}</p>
                            <p class="flex items-center gap-1.5 truncate text-sm text-muted"><x-icon name="lock" size="3" class="shrink-0 text-success" /><span class="truncate">{{ $appHost }}/<span class="font-semibold text-ink">{{ $slug }}</span></span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- All modules --}}
    <section id="modules" class="section bg-canvas">
        <div class="container-x">
            <div data-reveal class="flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="eyebrow">Everything a school needs</p>
                    <h2 class="heading-lg mt-3">{{ count($modules) }} modules. One login.</h2>
                    <p class="mt-4 text-lg text-body">Start with the essentials and switch on more modules as your school grows.</p>
                </div>
                <a href="#demo" class="btn btn-secondary">Get the full feature list</a>
            </div>
            <div id="all-modules" class="mt-10 grid gap-3 sm:mt-12 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                @foreach (array_values($modules) as $i => $module)
                    <div data-reveal style="--delay: {{ ($i % 4) * 70 }}ms" class="group card card-hover flex gap-4 p-4 sm:p-5 {{ $i >= 6 ? 'module-extra' : '' }}">
                        <span class="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-tint text-brand-text transition duration-300 group-hover:bg-brand group-hover:text-white"><x-icon :name="$module['icon']" /></span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-ink">{{ $module['label'] }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $module['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-secondary mt-4 w-full sm:hidden" data-expand="#all-modules" data-expanded-label="Show fewer modules">Show all {{ count($modules) }} modules</button>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section relative overflow-hidden bg-ink text-white">
        <div aria-hidden="true" class="bg-grid-dark absolute inset-0 [mask-image:linear-gradient(to_bottom,#000,transparent)]"></div>
        <div aria-hidden="true" class="glow animate-float-slow -right-40 -bottom-40 size-[30rem] bg-brand/20"></div>
        <div class="container-x relative">
            <div data-reveal class="max-w-2xl">
                <p class="eyebrow text-brand">Go live in days, not months</p>
                <h2 class="font-display mt-3 text-3xl leading-tight sm:text-4xl lg:text-[2.75rem]">How schools join Skooline</h2>
            </div>
            <ol class="-mx-4 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-2 md:mx-0 md:mt-12 md:grid md:grid-cols-3 md:gap-5 md:overflow-visible md:px-0 md:pb-0">
                @foreach ([
                    ['Book a demo', 'See Skooline with your own workflow and choose the plan that fits your school.'],
                    ['We set up your portal', 'Your school\'s private portal is created and we help import your students, staff and fee data.'],
                    ['Start managing', 'Your admin signs in, adds staff and classes, and the whole school runs on one system.'],
                ] as $i => [$title, $text])
                    <li data-reveal style="--delay: {{ $i * 110 }}ms" class="group relative w-[78%] shrink-0 snap-start rounded-lg border border-white/10 bg-white/[.04] p-6 transition duration-300 hover:-translate-y-1 hover:border-brand/40 hover:bg-white/[.07] md:w-auto">
                        <span class="font-display text-5xl text-brand">0{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/65">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="section bg-canvas">
        <div class="container-x">
            <div data-reveal class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Simple, flexible pricing</p>
                <h2 class="heading-lg mt-3">Pay the way your school prefers</h2>
                <p class="mt-4 text-lg text-body">Monthly, yearly, lifetime or per student. Every plan starts with a free trial.</p>
            </div>

            @if ($plans->isEmpty())
                <p class="mt-10 text-center text-body">Pricing is tailored to each school. <a href="#demo" class="font-semibold text-brand-text underline">Contact us</a> for a quote.</p>
            @else
                @if (count($currencies) > 1)
                    <script type="application/json" id="currency-data">{!! json_encode(['base' => $currencies[0]['code'], 'currencies' => $currencies, 'default' => \App\Models\Setting::get('website_default_currency', 'auto')], JSON_HEX_TAG) !!}</script>
                    <div class="mt-8 flex flex-col items-center gap-2">
                        <div class="relative">
                            <button type="button" class="btn btn-secondary" data-menu="#currency-menu" aria-haspopup="true" aria-expanded="false">
                                <x-icon name="globe" size="4" /> Prices in <span class="font-bold" data-currency-label>{{ $currencies[0]['code'] }}</span> <x-icon name="chevron-down" size="4" />
                            </button>
                            <div id="currency-menu" hidden class="menu left-1/2 -translate-x-1/2" role="menu" aria-label="Currency">
                                @foreach ($currencies as $currency)
                                    <button type="button" class="menu-item" role="menuitemradio" data-currency="{{ $currency['code'] }}" aria-checked="{{ $loop->first ? 'true' : 'false' }}">
                                        <span class="truncate">{{ $currency['name'] }}</span><span class="font-semibold">{{ $currency['code'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <p data-currency-note hidden class="text-xs text-muted">Prices shown in your currency.</p>
                    </div>
                @endif

                <div class="-mx-4 mt-10 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pt-4 pb-4 md:mx-auto md:mt-12 md:grid md:max-w-6xl md:grid-cols-2 md:gap-6 md:overflow-visible md:px-0 {{ $plans->count() >= 3 ? 'lg:grid-cols-3' : '' }}">
                    @foreach ($plans as $plan)
                        @php
                            $amount = $plan->headlinePrice();
                            // Where the button goes: the registration form, or the demo form for "contact" plans.
                            $ctaUrl = $plan->cta_mode !== 'contact' && \App\Services\RegistrationService::enabled() ? route('register', ['plan' => $plan->slug]) : '#demo';
                        @endphp
                        <article data-reveal style="--delay: {{ $loop->index * 90 }}ms" class="relative flex w-[85%] shrink-0 snap-center flex-col rounded-xl border bg-white p-6 transition duration-300 hover:-translate-y-1 sm:p-8 md:w-auto {{ $plan->is_featured ? 'border-brand shadow-pop ring-4 ring-brand/10' : 'border-line shadow-card hover:shadow-lift' }}">
                            @if ($plan->is_featured)
                                <span class="absolute -top-3 left-6 rounded-full bg-gradient-to-r from-brand to-brand-light px-3 py-1 text-xs font-semibold text-white shadow-brand">Most popular</span>
                            @endif
                            <h3 class="font-display text-2xl text-ink">{{ $plan->name }}</h3>
                            <p class="mt-2 min-h-10 text-sm text-body">{{ $plan->tagline }}</p>
                            <p class="mt-6 flex flex-wrap items-baseline gap-x-1.5 gap-y-1">
                                <span class="font-display tabular text-3xl text-ink sm:text-4xl" data-price="{{ (float) $amount }}" @foreach ($plan->prices ?? [] as $code => $fixed) @if ($fixed !== null && $fixed !== '') data-price-{{ strtolower($code) }}="{{ (float) $fixed }}" @endif @endforeach>{{ money($amount) }}</span>
                                <span class="text-sm text-muted">{{ $plan->priceUnit() }}</span>
                            </p>
                            <p class="mt-2 text-xs text-muted">
                                {{ $plan->max_students ? 'Up to '.number_format($plan->max_students).' students' : 'Unlimited students' }}
                                · {{ $plan->max_branches }} {{ \Illuminate\Support\Str::plural('campus', $plan->max_branches) }}
                            </p>
                            <ul class="mt-6 flex-1 space-y-2.5 border-t border-line pt-6 text-sm">
                                @foreach (collect($plan->modules)->take(8) as $key)
                                    @isset($modules[$key])
                                        <li class="flex items-start gap-2.5 text-ink"><x-icon name="check" size="4" class="mt-0.5 shrink-0 text-success" />{{ $modules[$key]['label'] }}</li>
                                    @endisset
                                @endforeach
                                @if (count($plan->modules) > 8)
                                    <li class="pl-6.5 text-muted">+ {{ count($plan->modules) - 8 }} more modules</li>
                                @endif
                            </ul>
                            <a href="{{ $ctaUrl }}" class="btn mt-8 w-full {{ $plan->is_featured ? 'btn-primary' : 'btn-dark' }}">{{ $plan->ctaLabel() }}</a>
                        </article>
                    @endforeach
                </div>
            @endif

            <p data-reveal class="mx-auto mt-10 max-w-2xl text-center text-sm text-body">Large school, college or group of campuses? <a href="#demo" class="font-semibold text-brand-text underline underline-offset-2">Ask for a custom plan</a>, including lifetime licences.</p>
        </div>
    </section>

    {{-- Security --}}
    <section id="security" class="section">
        <div class="container-x grid items-center gap-12 lg:grid-cols-2">
            <div data-reveal>
                <p class="eyebrow">Security & privacy</p>
                <h2 class="heading-lg mt-3">Your school's data stays your school's data.</h2>
                <p class="mt-4 text-lg leading-relaxed text-body">Every record in Skooline belongs to exactly one school. Other schools can never see your students, staff or fee records, and every page checks who is signed in.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4">
                @foreach ([
                    ['shield', 'Fully separated', 'Each school can only ever see its own records.'],
                    ['lock', 'Secure sign-in', 'HTTPS, hashed passwords and login protection.'],
                    ['users', 'Role-based access', 'Staff only see the pages and classes you allow.'],
                    ['refresh', 'Regular backups', 'Your records are backed up so they are never lost.'],
                ] as $i => [$icon, $title, $text])
                    <div data-reveal style="--delay: {{ $i * 80 }}ms" class="card card-hover p-4 sm:p-5">
                        <span class="grid size-10 place-items-center rounded-lg bg-success-tint text-success"><x-icon :name="$icon" /></span>
                        <h3 class="mt-3 text-sm font-semibold text-ink sm:mt-4 sm:text-base">{{ $title }}</h3>
                        <p class="mt-1 hidden text-sm text-body sm:block">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($content['testimonials'])
        {{-- Testimonials --}}
        <section class="section" aria-labelledby="testimonials-title">
            <div class="container-x">
                <div data-reveal class="mx-auto max-w-2xl text-center">
                    <p class="eyebrow">Schools on Skooline</p>
                    <h2 id="testimonials-title" class="heading-lg mt-3">What schools say</h2>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($content['testimonials'] as $i => $t)
                        <figure data-reveal style="--delay: {{ $i * 80 }}ms" class="card flex flex-col p-6">
                            <blockquote class="flex-1 text-body"><p class="leading-relaxed break-words">“{{ $t['quote'] }}”</p></blockquote>
                            <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-tint font-bold text-brand-text">{{ mb_strtoupper(mb_substr($t['name'], 0, 1)) }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-ink">{{ $t['name'] }}</span>
                                    <span class="block truncate text-xs text-muted">{{ collect([$t['role'] ?? null, $t['school'] ?? null])->filter()->join(', ') }}</span>
                                </span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    <section id="faq" class="section bg-canvas">
        <div class="container-x grid gap-10 lg:grid-cols-[1fr_1.6fr]">
            <div data-reveal>
                <p class="eyebrow">FAQ</p>
                <h2 class="heading-lg mt-3">Questions schools ask us</h2>
                <p class="mt-4 text-body">Can't find your answer? <a href="#demo" class="font-semibold text-brand-text underline underline-offset-2">Talk to our team</a>.</p>
            </div>
            <div data-reveal style="--delay: 100ms" class="divide-y divide-line overflow-hidden rounded-lg border border-line bg-white shadow-card">
                @foreach ($faqs as [$q, $a])
                    <details class="group [&_summary::-webkit-details-marker]:hidden" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer items-center justify-between gap-4 p-5 font-semibold text-ink transition hover:bg-canvas">
                            {{ $q }}
                            <span class="grid size-7 shrink-0 place-items-center rounded-full bg-chip text-ink transition duration-300 group-open:rotate-45 group-open:bg-brand group-open:text-white"><x-icon name="plus" size="4" /></span>
                        </summary>
                        <p class="px-5 pb-5 text-sm leading-relaxed text-body">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Demo request --}}
    <section id="demo" class="section">
        <div class="container-x">
            <div data-reveal class="grid overflow-hidden rounded-xl bg-ink shadow-pop lg:grid-cols-2">
                <div class="relative overflow-hidden p-8 text-white sm:p-12">
                    <div aria-hidden="true" class="bg-grid-dark absolute inset-0"></div>
                    <div aria-hidden="true" class="glow animate-float-slow -bottom-24 -left-24 size-80 bg-brand/30"></div>
                    <div class="relative">
                        <p class="eyebrow text-brand">Free demo</p>
                        <h2 class="font-display mt-3 text-3xl leading-tight sm:text-4xl lg:text-5xl">See Skooline running your school.</h2>
                        <p class="mt-5 text-white/70">Tell us about your school and our team will contact you to set up a personal demo and free trial.</p>
                        <ul class="mt-8 space-y-3 text-sm text-white/85">
                            @foreach (['Personal walkthrough for your staff', 'Help importing your existing data', 'A plan that fits your school size'] as $point)
                                <li class="flex items-center gap-3"><span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand text-white"><x-icon name="check" size="3" /></span>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="bg-white p-6 sm:p-10">
                    @if (session('demo_success'))
                        <div class="flex h-full flex-col items-center justify-center py-10 text-center" role="status">
                            <span class="grid size-16 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="8" /></span>
                            <p class="font-display mt-5 text-3xl text-ink">Request received</p>
                            <p class="mt-2 max-w-sm text-body">{{ session('demo_success') }}</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('demo-request') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <x-form.input name="name" label="Your name" required autocomplete="name" />
                            <x-form.input name="school_name" label="School name" required autocomplete="organization" />
                            <x-form.input name="phone" type="tel" label="Phone / WhatsApp" required autocomplete="tel" placeholder="Include country code" />
                            <x-form.input name="email" type="email" label="Email" autocomplete="email" />
                            <x-form.input name="city" label="City & country" autocomplete="address-level2" />
                            <x-form.input name="students" type="number" min="1" label="Number of students" />
                            <x-form.textarea name="message" label="Anything we should know?" class="sm:col-span-2" rows="3" />
                            <button class="btn btn-primary btn-lg sm:col-span-2">Request my free demo <x-icon name="arrow-right" size="4" /></button>
                            <p class="text-xs text-muted sm:col-span-2">We only use your details to contact you about Skooline.</p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.marketing>
