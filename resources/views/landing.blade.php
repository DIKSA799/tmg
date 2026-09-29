<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#008751">
        <meta name="description" content="Tinubu Must Go — grassroots voter data capture for Nigeria. Register voters from state to ward to polling unit in seconds.">

        <title>Tinubu Must Go · Grassroots Voter Data Capture</title>

        @fonts

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="antialiased">
        <div class="top-progress" data-top-progress></div>

        <a href="#capture" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-full focus:bg-[color:var(--accent)] focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
            Skip to the capture form
        </a>

        {{-- ------------------------------------------------------------------ Header --}}
        <header class="sticky top-0 z-50 pt-3">
            <div class="shell">
                <nav class="glass flex items-center justify-between gap-4 rounded-full px-3 py-2" aria-label="Primary">
                    <a href="#overview" class="flex items-center gap-2.5 rounded-full pl-1 pr-3">
                        <img src="{{ asset('tmg-logo-256.jpg') }}" alt="" width="36" height="36" class="h-9 w-9 rounded-full bg-white object-contain ring-1 ring-black/5">
                        <span class="flex flex-col leading-none">
                            <span class="font-display text-sm font-bold tracking-tight">Tinubu Must Go</span>
                            <span class="text-[10px] font-medium uppercase tracking-[0.14em] text-[color:var(--ink-mute)]">Nigeria</span>
                        </span>
                    </a>

                    <div class="hidden items-center gap-1 md:flex" data-nav-links>
                        <a href="#overview" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Overview</a>
                        <a href="#process" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>How it works</a>
                        <a href="#capture" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Capture</a>
                        <a href="#privacy" class="rounded-full px-3 py-2 text-sm font-medium text-[color:var(--ink-soft)] transition hover:text-[color:var(--accent)]" data-nav-link>Privacy</a>
                    </div>

                    <a href="#capture" class="btn btn-primary !px-4 !py-2.5 text-sm">
                        Start capture
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </nav>
            </div>
        </header>

        <main>
            {{-- ------------------------------------------------------------------ Hero --}}
            <section id="overview" class="shell pb-14 pt-12 lg:pb-20 lg:pt-16">
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
                        <div class="pointer-events-none absolute -right-4 -top-6 h-40 w-40 rounded-full border border-[color:var(--line)]" aria-hidden="true">
                            <span class="absolute inset-0 rounded-full border border-[color:var(--line)] animate-ring"></span>
                        </div>
                        <div class="pointer-events-none absolute -bottom-8 -left-6 h-28 w-28 rounded-full border border-[color:var(--line)] animate-drift" aria-hidden="true"></div>

                        <div class="neo-glass rounded-[30px] p-6 sm:p-8 animate-float">
                            <div class="flex items-center gap-4">
                                <img src="{{ asset('tmg-logo-640.jpg') }}" alt="Tinubu Must Go emblem" width="64" height="64" fetchpriority="high" class="h-16 w-16 rounded-2xl bg-white object-contain p-1 ring-1 ring-black/5">
                                <div>
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

            {{-- ------------------------------------------------------------------ Marquee --}}
            <section class="border-y border-[color:var(--line)] py-5" aria-hidden="true">
                <div class="marquee">
                    @foreach ([1, 2] as $copy)
                        <div class="flex items-center gap-8 pr-8 font-display text-sm font-semibold uppercase tracking-[0.22em] text-[color:var(--ink-mute)]">
                            <span>36 States + FCT</span><span class="text-[color:var(--flag-red)]">•</span>
                            <span>774 LGAs</span><span class="text-[color:var(--flag-blue)]">•</span>
                            <span>{{ number_format($stats['wards']) }} Wards</span><span class="text-[color:var(--flag-red)]">•</span>
                            <span>{{ number_format($stats['polling_units']) }} Polling Units</span><span class="text-[color:var(--flag-blue)]">•</span>
                            <span>Consent-first data</span><span class="text-[color:var(--flag-red)]">•</span>
                            <span>One record per person</span><span class="text-[color:var(--flag-blue)]">•</span>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- ------------------------------------------------------------------ Process --}}
            <section id="process" class="shell py-16 lg:py-24">
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

            {{-- ------------------------------------------------------------------ Capture --}}
            <section id="capture" class="shell scroll-mt-24 pb-16 lg:pb-24">
                <div class="reveal neo-glass overflow-hidden rounded-[34px]">
                    <div class="flex flex-col gap-6 border-b border-[color:var(--line)] p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-xl">
                            <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Live capture</span>
                            <h2 class="mt-5 font-display text-3xl font-bold tracking-tight sm:text-4xl">Capture a voter</h2>
                            <p class="mt-3 text-sm leading-relaxed text-[color:var(--ink-soft)] sm:text-base">
                                Fields marked <span class="font-semibold text-[color:var(--flag-red)]">*</span> are required. Location is pre-filled to the first
                                available option — use your device location to jump straight to your own ward.
                            </p>
                        </div>

                        <div class="flex flex-col items-start gap-3">
                            <button type="button" class="btn btn-ghost" data-geolocate>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2v3m0 14v3M2 12h3m14 0h3M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                Use my location
                            </button>
                            <p class="text-xs text-[color:var(--ink-mute)]" data-geo-status role="status" aria-live="polite">No location shared yet.</p>
                        </div>
                    </div>

                    {{-- Success panel --}}
                    <div class="hidden p-7 sm:p-9" data-success hidden>
                        <div class="mx-auto max-w-lg text-center">
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[color:var(--accent-soft)] text-[color:var(--accent)]">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <h3 class="mt-5 font-display text-2xl font-bold" data-success-title>Record captured</h3>
                            <p class="mt-2 text-sm text-[color:var(--ink-soft)]" data-success-message>Thank you. Your response has been recorded securely.</p>
                            <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-[color:var(--line)] px-4 py-2 font-mono text-xs text-[color:var(--ink-soft)]">
                                Reference <span data-success-reference class="font-semibold text-[color:var(--ink)]">—</span>
                            </p>
                            <div class="mt-7">
                                <button type="button" class="btn btn-primary" data-capture-again>Capture another voter</button>
                            </div>
                        </div>
                    </div>

                    <form class="space-y-10 p-7 sm:p-9" data-capture-form novalidate>
                        <input type="hidden" name="idempotency_key" value="" data-idempotency>
                        <input type="hidden" name="latitude" value="" data-latitude>
                        <input type="hidden" name="longitude" value="" data-longitude>

                        <noscript>
                            <p class="rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm text-[color:var(--flag-red)]">
                                JavaScript is required to search states, wards and polling units. Please enable it to continue.
                            </p>
                        </noscript>

                        {{-- 1 · Identity --}}
                        <fieldset class="space-y-6" data-section="identity">
                            <legend class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent)]">1</span>
                                <span class="font-display text-lg font-bold">Identity</span>
                            </legend>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="field-label" for="full_name">Full name <span class="req">*</span></label>
                                    <input id="full_name" name="full_name" type="text" class="input" placeholder="e.g. Amina Musa" autocomplete="name" required>
                                    <p class="field-error" data-error-for="full_name" hidden></p>
                                </div>

                                <div class="sm:col-span-2">
                                    <span class="field-label">Gender <span class="req">*</span></span>
                                    <div class="segments">
                                        @foreach ($options['gender'] as $value => $label)
                                            <label class="segment">
                                                <input type="radio" name="gender" value="{{ $value }}" @checked($loop->first)>
                                                <span>{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="field-error" data-error-for="gender" hidden></p>
                                </div>

                                <div>
                                    <label class="field-label" for="age_band">Age band <span class="req">*</span></label>
                                    <select id="age_band" name="age_band" class="input" required>
                                        <option value="" disabled selected>Select age band…</option>
                                        @foreach ($options['age_band'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-error" data-error-for="age_band" hidden></p>
                                </div>

                                <div>
                                    <label class="field-label" for="phone">Phone number <span class="req">*</span></label>
                                    <input id="phone" name="phone" type="tel" class="input" placeholder="0800 000 0000" inputmode="tel" autocomplete="tel" required>
                                    <p class="field-hint">Stored in +234 format.</p>
                                    <p class="field-error" data-error-for="phone" hidden></p>
                                </div>
                            </div>
                        </fieldset>

                        {{-- 2 · Location --}}
                        <fieldset class="space-y-6" data-section="location">
                            <legend class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent)]">2</span>
                                <span class="font-display text-lg font-bold">Location</span>
                            </legend>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <span class="field-label">Country</span>
                                    <div class="combo-trigger neo-inset cursor-default">
                                        <span class="flex items-center gap-2 text-[color:var(--ink)]">
                                            <span class="flag-stripe inline-block h-4 w-6 rounded-[3px]" aria-hidden="true"></span>
                                            Nigeria
                                        </span>
                                        <span class="chip !px-2.5 !py-1 text-[10px]">Fixed</span>
                                    </div>
                                </div>

                                <x-combobox name="state_id" label="State" placeholder="Select state…" />
                                <x-combobox name="lga_id" label="Local Government Area" placeholder="Select LGA…" :disabled="true" />
                                <x-combobox name="ward_id" label="Ward / Registration Area" placeholder="Select ward…" :disabled="true" />
                                <x-combobox name="polling_unit_id" label="Polling unit" placeholder="Select polling unit…" hint="Search by name or code." :disabled="true" />
                            </div>
                        </fieldset>

                        {{-- 3 · Status & preferences --}}
                        <fieldset class="space-y-6" data-section="status">
                            <legend class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent)]">3</span>
                                <span class="font-display text-lg font-bold">Voter status &amp; preferences</span>
                            </legend>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="field-label" for="registered_voter_status">Registered voter? <span class="req">*</span></label>
                                    <select id="registered_voter_status" name="registered_voter_status" class="input" required>
                                        <option value="" disabled selected>Select…</option>
                                        @foreach ($options['registered_voter_status'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-error" data-error-for="registered_voter_status" hidden></p>
                                </div>

                                <div>
                                    <label class="field-label" for="pvc_status">PVC status <span class="req">*</span></label>
                                    <select id="pvc_status" name="pvc_status" class="input" required>
                                        <option value="" disabled selected>Select…</option>
                                        @foreach ($options['pvc_status'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-error" data-error-for="pvc_status" hidden></p>
                                </div>

                                <div>
                                    <label class="field-label" for="preferred_language">Preferred language <span class="req">*</span></label>
                                    <select id="preferred_language" name="preferred_language" class="input" required data-other-trigger="preferred_language">
                                        <option value="" disabled selected>Select…</option>
                                        @foreach ($options['preferred_language'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-error" data-error-for="preferred_language" hidden></p>
                                </div>

                                <div>
                                    <label class="field-label" for="preferred_channel">Preferred channel <span class="req">*</span></label>
                                    <select id="preferred_channel" name="preferred_channel" class="input" required data-other-trigger="preferred_channel">
                                        <option value="" disabled selected>Select…</option>
                                        @foreach ($options['preferred_channel'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <p class="field-error" data-error-for="preferred_channel" hidden></p>
                                </div>

                                <div class="hidden" data-other-field="preferred_language">
                                    <label class="field-label" for="preferred_language_other">Specify language <span class="req">*</span></label>
                                    <input id="preferred_language_other" name="preferred_language_other" type="text" class="input" placeholder="Type the language" disabled>
                                    <p class="field-error" data-error-for="preferred_language_other" hidden></p>
                                </div>

                                <div class="hidden" data-other-field="preferred_channel">
                                    <label class="field-label" for="preferred_channel_other">Specify channel <span class="req">*</span></label>
                                    <input id="preferred_channel_other" name="preferred_channel_other" type="text" class="input" placeholder="Type the channel" disabled>
                                    <p class="field-error" data-error-for="preferred_channel_other" hidden></p>
                                </div>
                            </div>
                        </fieldset>

                        {{-- 4 · Consent --}}
                        <fieldset class="space-y-6" data-section="consent">
                            <legend class="flex items-center gap-3">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent)]">4</span>
                                <span class="font-display text-lg font-bold">Consent</span>
                            </legend>

                            <div class="grid gap-5">
                                <div>
                                    <span class="field-label">Agree to receive relevant communications through the selected channel? <span class="req">*</span></span>
                                    <div class="segments max-w-sm">
                                        <label class="segment"><input type="radio" name="consent_to_contact" value="1" checked><span>Yes</span></label>
                                        <label class="segment"><input type="radio" name="consent_to_contact" value="0"><span>No</span></label>
                                    </div>
                                    <p class="field-error" data-error-for="consent_to_contact" hidden></p>
                                </div>

                                <div>
                                    <span class="field-label">Informed of the purpose and consent to storage &amp; processing? <span class="req">*</span></span>
                                    <div class="segments max-w-sm">
                                        <label class="segment"><input type="radio" name="consent_to_data" value="1" checked><span>Yes</span></label>
                                        <label class="segment"><input type="radio" name="consent_to_data" value="0"><span>No</span></label>
                                    </div>
                                    <p class="field-error" data-error-for="consent_to_data" hidden></p>
                                </div>
                            </div>
                        </fieldset>

                        <div class="flex flex-col gap-4 border-t border-[color:var(--line)] pt-7 sm:flex-row sm:items-center sm:justify-between">
                            <p class="max-w-md text-xs leading-relaxed text-[color:var(--ink-mute)]">
                                Submitting stores the record with the capture time, device context and a duplicate-proof reference.
                            </p>
                            <button type="submit" class="btn btn-primary w-full sm:w-auto" data-submit>
                                <span data-submit-label>Submit record</span>
                                <svg data-submit-spinner class="hidden animate-[spin_0.8s_linear_infinite]" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                            </button>
                        </div>

                        <p class="hidden rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" data-form-error role="alert"></p>
                    </form>
                </div>
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
                    <img src="{{ asset('tmg-logo-256.jpg') }}" alt="" width="40" height="40" loading="lazy" class="h-10 w-10 rounded-full bg-white object-contain ring-1 ring-black/5">
                    <div>
                        <p class="font-display text-sm font-bold">Tinubu Must Go</p>
                        <p class="text-xs text-[color:var(--ink-mute)]">Grassroots voter data capture · Nigeria</p>
                    </div>
                </div>
                <p class="text-xs text-[color:var(--ink-mute)]">© {{ date('Y') }} Tinubu Must Go. Built for the field.</p>
            </div>
        </footer>
    </body>
</html>
