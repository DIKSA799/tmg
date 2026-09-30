<x-layouts.app>
    <a href="#capture" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-[color:var(--accent)] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Skip to the capture form
    </a>

    {{-- ------------------------------------------------------------------ Header --}}
    <header class="sticky top-0 z-50 pt-3">
        <div class="shell">
            <nav class="glass flex items-center justify-between gap-3 rounded-full px-3 py-2" aria-label="Primary">
                <a href="#overview" class="flex min-w-0 items-center gap-2.5 rounded-full pl-1 pr-2">
                    <img src="{{ asset('tmg-logo-256.jpg') }}" alt="" width="36" height="36" class="h-9 w-9 flex-none rounded-full bg-white object-contain ring-1 ring-black/5">
                    <span class="flex min-w-0 flex-col leading-none">
                        <span class="truncate font-display text-sm font-bold tracking-tight">Tinubu Must Go</span>
                        <span class="text-[10px] font-medium uppercase tracking-[0.14em] text-[color:var(--ink-mute)]">Nigeria</span>
                    </span>
                </a>

                <div class="hidden items-center gap-1 md:flex" data-nav-links>
                    <a href="#overview" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Overview</a>
                    <a href="#capture" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Capture</a>
                    <a href="#process" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>How it works</a>
                    <a href="#privacy" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Privacy</a>
                </div>

                <div class="flex flex-none items-center gap-2">
                    <x-auth-actions />
                    <x-theme-toggle />
                    <a href="{{ route('register') }}" class="btn btn-primary !px-4 !py-2.5 text-sm">
                        <span class="hidden sm:inline">Register a voter</span>
                        <span class="sm:hidden">Register</span>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        {{-- ------------------------------------------------------------------ Hero --}}
        <section id="overview" class="shell pb-10 pt-12 lg:pb-14 lg:pt-16">
            <div class="grid items-center gap-12 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="animate-rise">
                    <span class="chip">
                        <span class="chip-dot" aria-hidden="true"></span>
                        Grassroots voter data capture · Federal Republic of Nigeria
                    </span>

                    <h1 class="mt-6 font-display text-4xl font-bold leading-[1.03] tracking-tight sm:text-5xl lg:text-6xl">
                        Every voice.<br>
                        Every ward.<br>
                        <span class="bg-gradient-to-r from-[color:var(--accent)] via-[color:var(--accent-deep)] to-[color:var(--flag-blue)] bg-clip-text text-transparent">Every polling unit.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-relaxed text-[color:var(--ink-soft)] sm:text-lg">
                        A fast, consent-first way to register the people behind the movement — from state to LGA to ward to the exact
                        polling unit. Built for the field, on any phone, in seconds.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="#capture" class="btn btn-primary">
                            Start data capture
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <a href="#process" class="btn btn-ghost">See how it works</a>
                    </div>

                    <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-[color:var(--ink-mute)]">
                        <li class="flex items-center gap-2"><span class="text-[color:var(--accent)]">✓</span> Consent-first</li>
                        <li class="flex items-center gap-2"><span class="text-[color:var(--accent)]">✓</span> Duplicate-proof submissions</li>
                        <li class="flex items-center gap-2"><span class="text-[color:var(--accent)]">✓</span> Works on any device</li>
                    </ul>
                </div>

                <div class="relative">
                    <div class="pointer-events-none absolute -right-2 -top-6 h-40 w-40 rounded-full border border-[color:var(--line)]" aria-hidden="true">
                        <span class="absolute inset-0 rounded-full border border-[color:var(--line)] animate-ring"></span>
                    </div>
                    <div class="pointer-events-none absolute -bottom-6 -left-2 h-28 w-28 rounded-full border border-[color:var(--line)] animate-drift" aria-hidden="true"></div>

                    <div class="neo-glass rounded-[30px] p-6 sm:p-8 animate-float">
                        <div class="flex items-center gap-4">
                            <img src="{{ asset('tmg-logo-640.jpg') }}" alt="Tinubu Must Go emblem" width="64" height="64" fetchpriority="high" class="h-16 w-16 flex-none rounded-2xl bg-white object-contain p-1 ring-1 ring-black/5">
                            <div class="min-w-0">
                                <p class="font-display text-lg font-bold leading-tight">Tinubu Must Go</p>
                                <p class="text-xs font-medium uppercase tracking-[0.14em] text-[color:var(--ink-mute)]">Grassroots movement</p>
                            </div>
                        </div>

                        <dl class="mt-7 grid grid-cols-2 gap-3">
                            <div class="neo-inset rounded-2xl p-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--ink-mute)]">States + FCT</dt>
                                <dd class="mt-1 font-display text-2xl font-bold" data-count="{{ $stats['states'] }}">{{ number_format($stats['states']) }}</dd>
                            </div>
                            <div class="neo-inset rounded-2xl p-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--ink-mute)]">LGAs</dt>
                                <dd class="mt-1 font-display text-2xl font-bold" data-count="{{ $stats['lgas'] }}">{{ number_format($stats['lgas']) }}</dd>
                            </div>
                            <div class="neo-inset rounded-2xl p-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--ink-mute)]">Wards</dt>
                                <dd class="mt-1 font-display text-2xl font-bold" data-count="{{ $stats['wards'] }}">{{ number_format($stats['wards']) }}</dd>
                            </div>
                            <div class="neo-inset rounded-2xl p-4">
                                <dt class="text-[11px] font-semibold uppercase tracking-wider text-[color:var(--ink-mute)]">Polling units</dt>
                                <dd class="mt-1 font-display text-2xl font-bold" data-count="{{ $stats['polling_units'] }}">{{ number_format($stats['polling_units']) }}</dd>
                            </div>
                        </dl>

                        <div class="mt-6 h-2 overflow-hidden rounded-full flag-stripe" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ------------------------------------------------------------------ Capture (moved to the top) --}}
        {{-- <section id="capture" class="shell scroll-mt-24 pb-12 lg:pb-16">
            @include('partials.capture-card')
        </section> --}}

        {{-- ------------------------------------------------------------------ Marquee --}}
        <section class="overflow-hidden border-y border-[color:var(--line)] py-5" aria-hidden="true">
            <div class="marquee">
                @foreach ([1, 2] as $copy)
                    <div class="flex items-center gap-8 pr-8 font-display text-sm font-semibold uppercase tracking-[0.22em] text-[color:var(--ink-mute)]">
                        <span class="whitespace-nowrap">36 States + FCT</span><span class="text-[color:var(--flag-red)]">•</span>
                        <span class="whitespace-nowrap">774 LGAs</span><span class="text-[color:var(--flag-blue)]">•</span>
                        <span class="whitespace-nowrap">{{ number_format($stats['wards']) }} Wards</span><span class="text-[color:var(--flag-red)]">•</span>
                        <span class="whitespace-nowrap">{{ number_format($stats['polling_units']) }} Polling Units</span><span class="text-[color:var(--flag-blue)]">•</span>
                        <span class="whitespace-nowrap">Consent-first data</span><span class="text-[color:var(--flag-red)]">•</span>
                        <span class="whitespace-nowrap">One record per person</span><span class="text-[color:var(--flag-blue)]">•</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ------------------------------------------------------------------ Process --}}
        <section id="process" class="shell scroll-mt-24 py-16 lg:py-24">
            <div class="reveal max-w-2xl">
                <span class="chip"><span class="chip-dot" aria-hidden="true"></span> The process</span>
                <h2 class="mt-5 font-display text-3xl font-bold tracking-tight sm:text-4xl">Three steps from doorstep to data.</h2>
                <p class="mt-4 text-[color:var(--ink-soft)]">Pick the exact location, confirm who you are speaking with, secure their consent. Nothing more, nothing less.</p>
            </div>

            <ol class="mt-12 grid gap-6 md:grid-cols-3">
                @foreach ([
                    ['n' => '01', 'title' => 'Locate', 'body' => 'Detect the device location or walk the official register: state → LGA → ward → polling unit, with instant search on every level.'],
                    ['n' => '02', 'title' => 'Confirm', 'body' => 'Capture name, age band, contact and voter status. Phone numbers are normalised to +234 automatically, so records stay clean.'],
                    ['n' => '03', 'title' => 'Consent & submit', 'body' => 'Two explicit consents, logged with the timestamp, device and address context. Every submission is idempotent — retries never duplicate.'],
                ] as $index => $step)
                    <li class="reveal neo-glass press rounded-[26px] p-7" style="--reveal-delay: {{ $index * 90 }}ms">
                        <span class="font-display text-sm font-bold text-[color:var(--accent)]">{{ $step['n'] }}</span>
                        <h3 class="mt-4 font-display text-xl font-bold">{{ $step['title'] }}</h3>
                        <p class="mt-3 text-sm leading-relaxed text-[color:var(--ink-soft)]">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ------------------------------------------------------------------ Privacy --}}
        <section id="privacy" class="shell scroll-mt-24 pb-20">
            <div class="reveal glass grid gap-8 rounded-[30px] p-8 sm:p-10 lg:grid-cols-[0.9fr_1.1fr]">
                <div>
                    <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Privacy</span>
                    <h2 class="mt-5 font-display text-2xl font-bold tracking-tight sm:text-3xl">Consent you can point to.</h2>
                    <p class="mt-4 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                        This form is a data-capture tool for grassroots organising. A record is only stored when the person is informed of the
                        purpose and agrees to their data being processed.
                    </p>
                </div>

                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        ['title' => 'What we store', 'body' => 'The answers on this form, the capture timestamp, and the device, browser and network context.'],
                        ['title' => 'Why we store it', 'body' => 'To understand and mobilise support for the movement at polling-unit level.'],
                        ['title' => 'Your control', 'body' => 'Choose No on data processing and the record is not submitted at all.'],
                        ['title' => 'Duplicate safety', 'body' => 'Each submission carries a one-time key, so a retry can never create a second record.'],
                    ] as $item)
                        <li class="neo-inset rounded-2xl p-5">
                            <p class="font-display text-sm font-bold">{{ $item['title'] }}</p>
                            <p class="mt-2 text-xs leading-relaxed text-[color:var(--ink-soft)]">{{ $item['body'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </main>

    <footer class="border-t border-[color:var(--line)] py-10">
        <div class="shell flex flex-col items-center justify-between gap-6 sm:flex-row">
            <div class="flex items-center gap-3">
                <img src="{{ asset('tmg-logo-256.jpg') }}" alt="" width="40" height="40" loading="lazy" class="h-10 w-10 flex-none rounded-full bg-white object-contain ring-1 ring-black/5">
                <div class="min-w-0">
                    <p class="font-display text-sm font-bold">Tinubu Must Go</p>
                    <p class="text-xs text-[color:var(--ink-mute)]">Grassroots voter data capture · Nigeria</p>
                </div>
            </div>
            <p class="text-xs text-[color:var(--ink-mute)]">© {{ date('Y') }} Tinubu Must Go. Built for the field.</p>
        </div>
    </footer>
</x-layouts.app>
