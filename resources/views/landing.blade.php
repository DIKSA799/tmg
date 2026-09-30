<x-layouts.app
    title="Tinubu Must Go! | Civic Movement"
    description="An informational landing page for the Tinubu Must Go movement, presenting the movement identity, public information, downloadable assets, privacy information, Abuja time and weather."
>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[200] focus:rounded-xl focus:bg-[color:var(--ink)] focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:text-[color:var(--paper)]">
        Skip to content
    </a>

    <div class="bg-grid" aria-hidden="true"></div>
    <div class="bg-word bg-word-a" aria-hidden="true">NIGERIA</div>
    <div class="bg-word bg-word-b" aria-hidden="true">ACCOUNTABILITY</div>

    <div class="relative z-[2] flex min-h-dvh flex-col">
        <header class="fixed left-1/2 top-4 z-[60] w-[min(1180px,calc(100%-2rem))] -translate-x-1/2">
            <nav class="glass grid grid-cols-[auto_1fr_auto] items-center gap-3 rounded-[22px] px-3 py-2.5" aria-label="Primary">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center" aria-label="Tinubu Must Go — home">
                    <img src="{{ asset('favicon.png') }}" alt="Tinubu Must Go" width="2413" height="2875" class="brand-logo" draggable="false">
                </a>

                <div class="hidden justify-center gap-1 md:flex">
                    <button type="button" class="rounded-xl px-3 py-2.5 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[color:var(--ink-soft)] transition hover:bg-[color:var(--paper-raised)] hover:text-[color:var(--ink)]" data-open="about">Information</button>
                    <button type="button" class="rounded-xl px-3 py-2.5 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[color:var(--ink-soft)] transition hover:bg-[color:var(--paper-raised)] hover:text-[color:var(--ink)]" data-open="downloads">Downloads</button>
                    <button type="button" class="rounded-xl px-3 py-2.5 text-[0.78rem] font-extrabold uppercase tracking-[0.08em] text-[color:var(--ink-soft)] transition hover:bg-[color:var(--paper-raised)] hover:text-[color:var(--ink)]" data-open="privacy">Privacy</button>
                </div>

                <div class="flex flex-none items-center gap-2">
                    <x-auth-actions />
                    <x-theme-toggle />
                    <a href="{{ route('register') }}" class="btn btn-primary !px-4 !py-2.5 text-[0.78rem] font-black uppercase tracking-[0.08em]">Register</a>
                </div>
            </nav>
        </header>

        <main id="main" class="shell flex flex-1 flex-col justify-center pb-10 pt-32 lg:pt-36">
            <section class="grid items-center gap-10 lg:grid-cols-[minmax(0,1.04fr)_minmax(0,0.96fr)]" aria-labelledby="hero-title">
                <div class="min-w-0">
                    <span class="eyebrow">
                        <span class="eyebrow-dot" aria-hidden="true"></span>
                        <span data-scramble>CITIZEN-LED CIVIC PLATFORM</span>
                    </span>

                    <h1 id="hero-title" class="headline mt-6">
                        <span class="line"><span>Tinubu</span></span>
                        <span class="line"><span>Must Go!</span></span>
                    </h1>

                    <p class="lead">A Civic Platform for Accountability, Public Dialogue and Democratic Participation</p>

                    <p class="copy-muted">
                        An informational landing page presenting the movement's stated identity, public information, downloadable media assets
                        and privacy information. Visitors should independently assess political information and participate in civic processes
                        according to applicable law.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <button type="button" class="btn btn-primary font-extrabold tracking-[0.04em]" data-open="about">
                            Movement information <span aria-hidden="true">↗</span>
                        </button>
                        <button type="button" class="btn btn-ghost font-extrabold tracking-[0.04em]" data-open="downloads">
                            <span aria-hidden="true">↓</span> Brand assets
                        </button>
                    </div>
                </div>

                <div class="logo-stage" id="logoStage" aria-label="Movement logo presentation">
                    <div class="halo" aria-hidden="true"></div>
                    <div class="orbit" aria-hidden="true"></div>
                    <img
                        class="hero-logo"
                        src="{{ asset('t1.png') }}"
                        alt="Tinubu Must Go movement logo"
                        width="2413"
                        height="2875"
                        fetchpriority="high"
                        draggable="false"
                    >
                </div>
    {{-- no need for now --}}
                {{-- <div class="info-strip lg:col-span-2" aria-label="Live information">
                    <div class="metric">
                        <span class="metric-label">Abuja weather</span>
                        <div class="metric-value" data-weather>Loading weather…</div>
                        <div class="metric-minor" data-weather-minor>Abuja, Nigeria</div>
                    </div>

                    <div class="metric">
                        <span class="metric-label">Nigeria local time</span>
                        <div class="metric-value" data-clock>--:--:--</div>
                        <div class="metric-minor" data-date>Africa/Lagos</div>
                    </div>

                    <div class="metric">
                        <span class="metric-label">Polling units mapped</span>
                        <div class="metric-value"><span data-count="{{ $stats['polling_units'] }}">{{ number_format($stats['polling_units']) }}</span>+</div>
                        <div class="metric-minor">
                            {{ number_format($stats['states']) }} states · {{ number_format($stats['lgas']) }} LGAs · {{ number_format($stats['wards']) }} wards
                        </div>
                    </div>
                </div> --}}

                <div class="utility-row lg:col-span-2">
                    <span>Public information microsite · Abuja weather shown as fixed location</span>
                    <button type="button" class="link-underline" data-open="privacy">Privacy Notice / Consent to Data Processing</button>
                </div>
            </section>
        </main>
    </div>

    <button type="button" class="a11y-toggle" data-contrast-toggle aria-pressed="false" aria-label="Toggle enhanced contrast">Aa</button>

    {{-- ------------------------------------------------------------------ Movement information --}}
    <div class="modal-backdrop" id="about" data-modal role="dialog" aria-modal="true" aria-labelledby="aboutTitle">
        <div class="modal">
            <div class="modal-head">
                <h2 id="aboutTitle">Movement information</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>
            <p>This page is designed as an informational presentation of the movement's public identity. It does not independently verify political claims or provide personalised political recommendations.</p>
            <h3>Public information</h3>
            <p>Official statements, supporting documents, governance concerns and other movement materials can be linked here once they are supplied and verified for publication.</p>
            <h3>Independent assessment</h3>
            <p>Visitors should review political information critically, consult primary sources where possible, and make their own civic and electoral decisions.</p>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ Downloads --}}
    <div class="modal-backdrop" id="downloads" data-modal role="dialog" aria-modal="true" aria-labelledby="downloadsTitle">
        <div class="modal modal-wide">
            <div class="modal-head">
                <h2 id="downloadsTitle">Downloads / Brand Assets</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>
            <p>Original supplied PNG assets are served from this site and can be downloaded directly.</p>

            <div class="asset-grid mt-4">
                @foreach ([
                    ['file' => 'favicon.png', 'name' => 'Favicon / Symbol', 'note' => 'PNG · 1470 × 1470'],
                    ['file' => 't2.png', 'name' => 'Movement logo', 'note' => 'PNG · 2413 × 2875'],
                    ['file' => 't1.png', 'name' => 'Social preview artwork', 'note' => 'PNG · 2411 × 3415'],
                ] as $asset)
                    <article class="asset-card">
                        <div class="asset-preview">
                            <img src="{{ asset($asset['file']) }}" alt="{{ $asset['name'] }} preview" loading="lazy" draggable="false">
                        </div>
                        <div class="asset-meta">
                            <div class="min-w-0">
                                <strong>{{ $asset['name'] }}</strong>
                                <span>{{ $asset['note'] }}</span>
                            </div>
                            <a class="asset-btn" href="{{ asset($asset['file']) }}" download>Download</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ------------------------------------------------------------------ Privacy --}}
    <div class="modal-backdrop" id="privacy" data-modal role="dialog" aria-modal="true" aria-labelledby="privacyTitle">
        <div class="modal">
            <div class="modal-head">
                <h2 id="privacyTitle">Privacy Notice &amp; Consent to Data Processing</h2>
                <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
            </div>

            <h3>1. Introduction</h3>
            <p>This notice explains how information may be handled through this website. Organisation-specific controller details and contact information should be completed before production deployment.</p>
            <h3>2. Information We Collect</h3>
            <p>The current static prototype does not submit registration data. A production registration service may collect the fields shown in the registration form. Technical server logs may also be generated by the hosting provider.</p>
            <h3>3. Why We Collect Information</h3>
            <p>Any production collection should be limited to the clearly stated purpose communicated at the point of collection.</p>
            <h3>4. How Information Is Used</h3>
            <p>Information should not be repurposed for unrelated activities without an appropriate legal basis and notice.</p>
            <h3>5. Lawful Processing and Consent</h3>
            <p>Where consent is relied upon, it should be specific, informed and freely given. Consent controls must not be preselected.</p>
            <h3>6. Cookies and Similar Technologies</h3>
            <p>This site uses local/session storage only for a short-lived weather cache and interface preferences. It does not include third-party advertising or behavioural tracking.</p>
            <h3>7. Data Sharing</h3>
            <p>No third-party sharing arrangements are represented by this interface. Any production sharing should be disclosed before deployment.</p>
            <h3>8. Data Retention</h3>
            <p>Production retention periods should be documented and limited to what is necessary for the stated purpose.</p>
            <h3>9. Data Security</h3>
            <p>Production services should use HTTPS, strict access controls, server-side validation, secure database practices, rate limiting, monitoring and appropriate backups.</p>
            <h3>10. Your Choices and Rights</h3>
            <p>Applicable rights depend on the deployed service, the organisation responsible for processing and relevant data-protection law.</p>
            <h3>11. Withdrawal of Consent</h3>
            <p>Where processing is based on consent, the production service should provide an appropriate mechanism to withdraw it.</p>
            <h3>12. Contact</h3>
            <p>[OFFICIAL PRIVACY CONTACT TO BE INSERTED]</p>
            <h3>13. Changes to this Notice</h3>
            <p>Update this notice whenever data practices, analytics, integrations or processing purposes materially change.</p>
        </div>
    </div>
</x-layouts.app>
