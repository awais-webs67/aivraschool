<x-layouts.marketing title="The Complete School ERP">

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-white">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 opacity-60" style="background-image: radial-gradient(circle at 1px 1px, #E1E3E5 1px, transparent 0); background-size: 28px 28px; mask-image: linear-gradient(to bottom, #000 40%, transparent);"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -top-32 -right-32 size-[34rem] rounded-full bg-brand/10 blur-3xl"></div>

        <div class="container-x relative grid items-center gap-12 px-4 pt-12 pb-16 sm:px-6 md:pt-20 lg:grid-cols-[1.05fr_1fr] lg:gap-10 lg:px-8 lg:pb-24">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-brand/30 bg-brand-tint px-3 py-1 text-xs font-semibold text-brand-text">
                    <span class="size-1.5 rounded-full bg-brand"></span> School management system by AivraSol
                </p>
                <h1 class="heading-xl mt-6">Your whole school.<br>One <span class="relative inline-block text-brand">line<span aria-hidden="true" class="absolute right-0 -bottom-1 left-0 h-1.5 rounded-full bg-brand/25"></span></span> to run it.</h1>
                <p class="mt-6 max-w-xl text-lg text-body">
                    Skooline brings admissions, attendance, fees, exams, staff, transport and parent communication into one secure, cloud-based system that works on every device.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="#demo" class="btn btn-primary btn-lg">Book a free demo <x-icon name="arrow-right" size="4" /></a>
                    <a href="#pricing" class="btn btn-secondary btn-lg">See pricing</a>
                </div>
                <ul class="mt-8 grid gap-3 text-sm text-body sm:grid-cols-3">
                    @foreach (['Own secure database', 'Works on any device', 'Flexible subscriptions'] as $point)
                        <li class="flex items-center gap-2"><x-icon name="check-circle" size="4" class="text-success" /> {{ $point }}</li>
                    @endforeach
                </ul>
            </div>

            {{-- Product preview (pure HTML/CSS mock of the school dashboard) --}}
            <div class="relative" aria-label="Preview of the Skooline school dashboard" role="img">
                <div class="rounded-lg border border-line bg-white p-2 shadow-pop">
                    <div class="flex overflow-hidden rounded-md border border-line">
                        <div class="hidden w-40 shrink-0 flex-col gap-1.5 bg-steel p-3 sm:flex">
                            <div class="mb-2 flex items-center gap-2"><span class="font-display grid size-6 place-items-center rounded bg-white text-sm text-ink">S</span><span class="font-display text-lg text-white">Skoo<span class="text-brand">line</span></span></div>
                            @foreach (['home' => 'Dashboard', 'users' => 'Students', 'check-circle' => 'Attendance', 'banknotes' => 'Fees', 'academic-cap' => 'Exams', 'chat' => 'Messages'] as $icon => $label)
                                <div class="flex items-center gap-2 rounded px-2 py-1.5 text-[11px] {{ $loop->first ? 'bg-white/10 text-white' : 'text-white/60' }}"><x-icon :name="$icon" size="3" /> {{ $label }}</div>
                            @endforeach
                        </div>
                        <div class="min-w-0 flex-1 bg-canvas p-3 sm:p-4">
                            <div class="flex items-center justify-between">
                                <div><p class="text-[10px] text-muted">Greenfield Grammar School</p><p class="font-display text-xl leading-none text-ink">Good morning, Principal</p></div>
                                <span class="rounded bg-brand px-2 py-1 text-[10px] font-semibold text-white">+ Admission</span>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-2">
                                @foreach ([['Students', '1,248', 'text-ink'], ['Present today', '96%', 'text-success'], ['Fee collected', '82%', 'text-brand-text']] as [$label, $value, $tone])
                                    <div class="rounded border border-line bg-white p-2"><p class="text-[9px] text-muted">{{ $label }}</p><p class="font-display text-2xl leading-none {{ $tone }}">{{ $value }}</p></div>
                                @endforeach
                            </div>
                            <div class="mt-2 grid gap-2 sm:grid-cols-[1.4fr_1fr]">
                                <div class="rounded border border-line bg-white p-2">
                                    <p class="text-[9px] font-semibold text-ink">Monthly fee collection</p>
                                    <div class="mt-2 flex h-20 items-end gap-1.5">
                                        @foreach ([45, 60, 52, 70, 66, 82, 74, 90] as $h)
                                            <span class="flex-1 rounded-t-sm {{ $loop->last ? 'bg-brand' : 'bg-brand/25' }}" style="height: {{ $h }}%"></span>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="rounded border border-line bg-white p-2">
                                    <p class="text-[9px] font-semibold text-ink">Today</p>
                                    <ul class="mt-1.5 space-y-1.5 text-[9px] text-body">
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-success"></span>Attendance marked: 38/40 classes</li>
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-brand"></span>126 fee reminders sent</li>
                                        <li class="flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-info"></span>Mid-term date sheet published</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="absolute -bottom-5 -left-3 hidden items-center gap-3 rounded-md border border-line bg-white px-4 py-3 shadow-pop sm:flex">
                    <span class="grid size-9 place-items-center rounded-full bg-success-tint text-success"><x-icon name="banknotes" size="4" /></span>
                    <div><p class="text-xs text-muted">Fee received</p><p class="text-sm font-semibold text-ink">Class 7-B · Ayesha K.</p></div>
                </div>
            </div>
        </div>
    </section>

    {{-- Highlights strip --}}
    <section class="border-y border-line bg-canvas">
        <div class="container-x grid grid-cols-2 gap-6 px-4 py-10 sm:px-6 md:grid-cols-4 lg:px-8">
            @foreach ([[count($modules).'+', 'modules in one system'], ['1', 'portal per school'], ['100%', 'separate data per school'], ['24/7', 'cloud access, any device']] as [$value, $label])
                <div class="text-center md:text-left">
                    <p class="font-display text-5xl leading-none text-ink">{{ $value }}</p>
                    <p class="mt-1 text-sm text-body">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Who it's for --}}
    <section id="features" class="section">
        <div class="container-x">
            <div class="max-w-2xl">
                <p class="eyebrow">Built for everyone in your school</p>
                <h2 class="heading-lg mt-3">One system. The right screen for every role.</h2>
                <p class="mt-4 text-body">Each person signs in and sees only what they need, with permissions you control.</p>
            </div>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['building', 'Principal & admin', 'Live dashboards for strength, attendance, collections and results across every class and campus.'],
                    ['academic-cap', 'Teachers', 'Mark attendance in seconds, post homework, enter marks and message parents from phone or laptop.'],
                    ['calculator', 'Accounts & office', 'Generate fee challans in bulk, collect at the counter or online, and track every defaulter.'],
                    ['users', 'Parents & students', 'Attendance, homework, results, fee vouchers and school notices, always up to date.'],
                ] as [$icon, $title, $text])
                    <div class="group rounded-md border border-line bg-white p-6 transition hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-card">
                        <span class="grid size-12 place-items-center rounded-md bg-ink text-white transition group-hover:bg-brand"><x-icon :name="$icon" size="6" /></span>
                        <h3 class="mt-5 text-lg font-semibold text-ink">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-body">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Feature spotlights --}}
    <section class="section bg-canvas">
        <div class="container-x space-y-20">
            @foreach ([
                [
                    'eyebrow' => 'Fees & finance', 'title' => 'Collect fees faster, chase defaulters less.',
                    'text' => 'Bank-ready challans for the whole school in one click, with sibling discounts, scholarships, late fines and instant receipts.',
                    'points' => ['Bulk monthly vouchers and family statements', 'Late fines, discounts and installments applied automatically', 'Online payments and automatic reminders to parents', 'Daily collection, outstanding and cashier reports'],
                    'icon' => 'banknotes',
                ],
                [
                    'eyebrow' => 'Academics', 'title' => 'From attendance to report card, without spreadsheets.',
                    'text' => 'Teachers mark attendance and marks once, and Skooline calculates grades, positions and report cards in your school\'s own format.',
                    'points' => ['Daily and period-wise attendance with absence alerts', 'Exams, grading, GPA and positions', 'Printable report cards, admit cards and date sheets', 'Homework, diary and timetable for every class'],
                    'icon' => 'academic-cap',
                ],
                [
                    'eyebrow' => 'Communication', 'title' => 'Keep every parent in the loop.',
                    'text' => 'Send notices, fee reminders, results and absence alerts by SMS, WhatsApp and app notification, to one class or the whole school.',
                    'points' => ['Bulk SMS and WhatsApp messages', 'Notice board and circulars', 'Automatic alerts for absence, fees and results', 'Parent and teacher apps'],
                    'icon' => 'chat',
                ],
            ] as $spot)
                <div class="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
                    <div class="{{ $loop->even ? 'lg:order-2' : '' }}">
                        <p class="eyebrow">{{ $spot['eyebrow'] }}</p>
                        <h3 class="heading-lg mt-3">{{ $spot['title'] }}</h3>
                        <p class="mt-4 text-body">{{ $spot['text'] }}</p>
                        <ul class="mt-6 space-y-3">
                            @foreach ($spot['points'] as $point)
                                <li class="flex items-start gap-3 text-sm text-ink"><span class="mt-0.5 grid size-5 shrink-0 place-items-center rounded-full bg-brand text-white"><x-icon name="check" size="3" /></span>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="{{ $loop->even ? 'lg:order-1' : '' }}">
                        <div class="relative overflow-hidden rounded-lg bg-ink p-6 sm:p-10">
                            <div aria-hidden="true" class="absolute -top-16 -right-16 size-64 rounded-full bg-brand/25 blur-3xl"></div>
                            <div class="relative rounded-md bg-white p-5 shadow-pop">
                                <div class="flex items-center gap-3 border-b border-line pb-4">
                                    <span class="grid size-10 place-items-center rounded-md bg-brand-tint text-brand-text"><x-icon :name="$spot['icon']" /></span>
                                    <div><p class="font-semibold text-ink">{{ $spot['eyebrow'] }}</p><p class="text-xs text-muted">Greenfield Grammar School</p></div>
                                </div>
                                <ul class="mt-4 space-y-3">
                                    @foreach ($spot['points'] as $i => $point)
                                        <li class="flex items-center justify-between gap-3 rounded border border-line px-3 py-2.5 text-sm">
                                            <span class="truncate text-body">{{ $point }}</span>
                                            <span class="badge {{ ['badge-success', 'badge-brand', 'badge-info', 'badge-neutral'][$i % 4] }}">{{ ['Done', 'Auto', 'Live', 'Report'][$i % 4] }}</span>
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

    {{-- All modules --}}
    <section id="modules" class="section">
        <div class="container-x">
            <div class="flex flex-wrap items-end justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="eyebrow">Everything a school needs</p>
                    <h2 class="heading-lg mt-3">{{ count($modules) }} modules. One login.</h2>
                    <p class="mt-4 text-body">Start with the essentials and switch on more modules as your school grows.</p>
                </div>
                <a href="#demo" class="btn btn-secondary">Get the full feature list</a>
            </div>
            <div class="mt-12 grid gap-px overflow-hidden rounded-md border border-line bg-line sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($modules as $module)
                    <div class="flex gap-4 bg-white p-5 transition-colors hover:bg-brand-tint/40">
                        <span class="grid size-10 shrink-0 place-items-center rounded-md bg-canvas text-steel"><x-icon :name="$module['icon']" /></span>
                        <div>
                            <h3 class="text-sm font-semibold text-ink">{{ $module['label'] }}</h3>
                            <p class="mt-1 text-xs leading-relaxed text-muted">{{ $module['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="section bg-ink text-white">
        <div class="container-x">
            <div class="max-w-2xl">
                <p class="eyebrow text-brand">Go live in days, not months</p>
                <h2 class="font-display mt-3 text-4xl leading-none sm:text-5xl">How schools join Skooline</h2>
            </div>
            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['Book a demo', 'See Skooline with your own workflow and choose the plan that fits your school.'],
                    ['We set up your school', 'Your private portal and database are created, and we help import your students, staff and fee data.'],
                    ['Start managing', 'Your admin signs in, adds staff and teachers, and the whole school is on one system.'],
                ] as $i => [$title, $text])
                    <li class="relative rounded-md border border-white/10 bg-white/5 p-6">
                        <span class="font-display text-6xl leading-none text-brand">0{{ $i + 1 }}</span>
                        <h3 class="mt-4 text-lg font-semibold">{{ $title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/70">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="section bg-canvas">
        <div class="container-x">
            <div class="mx-auto max-w-2xl text-center">
                <p class="eyebrow">Simple, flexible pricing</p>
                <h2 class="heading-lg mt-3">Pay the way your school prefers</h2>
                <p class="mt-4 text-body">Monthly, yearly, lifetime or per student. Every plan starts with a free trial.</p>
            </div>

            @if ($plans->isEmpty())
                <p class="mt-10 text-center text-body">Pricing is tailored to each school. <a href="#demo" class="font-semibold text-brand-text underline">Contact us</a> for a quote.</p>
            @else
                <div class="mx-auto mt-12 grid max-w-6xl gap-6 md:grid-cols-2 {{ $plans->count() >= 3 ? 'lg:grid-cols-3' : '' }}">
                    @foreach ($plans as $plan)
                        <article class="relative flex flex-col rounded-lg border bg-white p-6 sm:p-8 {{ $plan->is_featured ? 'border-brand shadow-pop lg:-translate-y-2' : 'border-line shadow-card' }}">
                            @if ($plan->is_featured)
                                <span class="absolute -top-3 left-6 rounded-full bg-brand px-3 py-1 text-xs font-semibold text-white">Most popular</span>
                            @endif
                            <h3 class="font-display text-3xl leading-none text-ink">{{ $plan->name }}</h3>
                            <p class="mt-2 min-h-10 text-sm text-body">{{ $plan->tagline }}</p>
                            <p class="mt-6 flex items-baseline gap-1.5">
                                @if ($plan->isPerStudent())
                                    <span class="font-display tabular text-5xl leading-none text-ink">{{ money($plan->price_per_student) }}</span>
                                    <span class="text-sm text-muted">{{ $plan->priceUnit() }}</span>
                                @else
                                    <span class="font-display tabular text-5xl leading-none text-ink">{{ money($plan->price) }}</span>
                                    <span class="text-sm text-muted">{{ $plan->priceUnit() }}</span>
                                @endif
                            </p>
                            <p class="mt-2 text-xs text-muted">
                                {{ $plan->max_students ? 'Up to '.number_format($plan->max_students).' students' : 'Unlimited students' }}
                                · {{ $plan->max_branches }} {{ \Illuminate\Support\Str::plural('campus', $plan->max_branches) }}
                            </p>
                            <ul class="mt-6 flex-1 space-y-2.5 border-t border-line pt-6 text-sm">
                                @foreach (collect($plan->modules)->take(8) as $key)
                                    @isset($modules[$key])
                                        <li class="flex items-start gap-2.5 text-ink"><x-icon name="check" size="4" class="mt-0.5 text-success" />{{ $modules[$key]['label'] }}</li>
                                    @endisset
                                @endforeach
                                @if (count($plan->modules) > 8)
                                    <li class="pl-6.5 text-muted">+ {{ count($plan->modules) - 8 }} more modules</li>
                                @endif
                            </ul>
                            <a href="#demo" data-plan="{{ $plan->name }}" class="btn mt-8 w-full {{ $plan->is_featured ? 'btn-primary' : 'btn-dark' }}">Start {{ $plan->trial_days }}-day free trial</a>
                        </article>
                    @endforeach
                </div>
            @endif

            <p class="mx-auto mt-10 max-w-2xl text-center text-sm text-body">Large school, college or group of campuses? <a href="#demo" class="font-semibold text-brand-text underline underline-offset-2">Ask for a custom plan</a>, including lifetime licences.</p>
        </div>
    </section>

    {{-- Security --}}
    <section id="security" class="section">
        <div class="container-x grid items-center gap-12 lg:grid-cols-2">
            <div>
                <p class="eyebrow">Security & privacy</p>
                <h2 class="heading-lg mt-3">Your school's data stays your school's data.</h2>
                <p class="mt-4 text-body">Every school on Skooline runs on its own separate database. No other school can ever see your students, staff or fee records.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['shield', 'Separate database', 'Each school is fully isolated from every other school.'],
                    ['lock', 'Encrypted & secure', 'HTTPS everywhere, hashed passwords and protected logins.'],
                    ['users', 'Role-based access', 'Staff only see the modules and classes you allow.'],
                    ['refresh', 'Daily backups', 'Automatic backups so your records are never lost.'],
                ] as [$icon, $title, $text])
                    <div class="rounded-md border border-line p-5">
                        <span class="grid size-10 place-items-center rounded-md bg-success-tint text-success"><x-icon :name="$icon" /></span>
                        <h3 class="mt-4 font-semibold text-ink">{{ $title }}</h3>
                        <p class="mt-1 text-sm text-body">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="section bg-canvas">
        <div class="container-x grid gap-10 lg:grid-cols-[1fr_1.6fr]">
            <div>
                <p class="eyebrow">FAQ</p>
                <h2 class="heading-lg mt-3">Questions schools ask us</h2>
                <p class="mt-4 text-body">Can't find your answer? <a href="#demo" class="font-semibold text-brand-text underline underline-offset-2">Talk to our team</a>.</p>
            </div>
            <div class="divide-y divide-line rounded-md border border-line bg-white">
                @foreach ([
                    ['Do we need to install anything?', 'No. Skooline runs in the browser on computers, tablets and phones. Your school gets its own web address to sign in.'],
                    ['Is our data separate from other schools?', 'Yes. Every school has its own database. Other schools can never access your data.'],
                    ['Which payment plans are available?', 'Monthly, quarterly, yearly, lifetime and per-student plans. We can also make a custom plan for your school.'],
                    ['Can you import our existing data?', 'Yes. Our team helps import students, staff and fee balances from Excel during setup.'],
                    ['What happens when our subscription ends?', 'You get a reminder before renewal and a short grace period. Your data stays safe and is restored as soon as you renew.'],
                    ['Is there a free trial?', 'Yes, every plan starts with a free trial so your staff can try Skooline before you pay.'],
                ] as [$q, $a])
                    <details class="group p-5 [&_summary::-webkit-details-marker]:hidden" @if ($loop->first) open @endif>
                        <summary class="flex cursor-pointer items-center justify-between gap-4 font-medium text-ink">
                            {{ $q }}
                            <x-icon name="chevron-down" size="5" class="text-muted transition group-open:rotate-180" />
                        </summary>
                        <p class="mt-3 text-sm leading-relaxed text-body">{{ $a }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Demo request --}}
    <section id="demo" class="section">
        <div class="container-x">
            <div class="grid overflow-hidden rounded-lg bg-ink lg:grid-cols-2">
                <div class="relative p-8 text-white sm:p-12">
                    <div aria-hidden="true" class="absolute -bottom-24 -left-24 size-80 rounded-full bg-brand/25 blur-3xl"></div>
                    <div class="relative">
                        <p class="eyebrow text-brand">Free demo</p>
                        <h2 class="font-display mt-3 text-5xl leading-[0.95] sm:text-6xl">See Skooline running your school.</h2>
                        <p class="mt-5 text-white/70">Tell us about your school and our team will contact you to set up a personal demo and free trial.</p>
                        <ul class="mt-8 space-y-3 text-sm text-white/85">
                            @foreach (['Personal walkthrough for your staff', 'Help importing your existing data', 'Plan that fits your school size'] as $point)
                                <li class="flex items-center gap-3"><span class="grid size-6 place-items-center rounded-full bg-brand text-white"><x-icon name="check" size="3" /></span>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="bg-white p-6 sm:p-10">
                    @if (session('demo_success'))
                        <div class="flex h-full flex-col items-center justify-center py-10 text-center" role="status">
                            <span class="grid size-16 place-items-center rounded-full bg-success-tint text-success"><x-icon name="check-circle" size="8" /></span>
                            <p class="font-display mt-5 text-4xl text-ink">Request received</p>
                            <p class="mt-2 max-w-sm text-body">{{ session('demo_success') }}</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('demo-request') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            <x-form.input name="name" label="Your name" required autocomplete="name" />
                            <x-form.input name="school_name" label="School name" required />
                            <x-form.input name="phone" type="tel" label="Phone / WhatsApp" required autocomplete="tel" placeholder="03xx-xxxxxxx" />
                            <x-form.input name="email" type="email" label="Email" autocomplete="email" />
                            <x-form.input name="city" label="City" />
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
