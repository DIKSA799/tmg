<x-layouts.tmgamb
    title="Sign in · TMG Ambassadors Space"
    description="Sign in to the TMG Ambassadors Space with your phone number or email and your TMG number."
    :bare="true"
>
    <div class="amb-login">
        <div class="amb-login-grid">
            <section class="amb-login-hero" aria-label="TMG Ambassadors Space">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('t2.png') }}" alt="Tinubu Must Go" width="2413" height="2875" class="brand-logo">
                    <span class="flex flex-col leading-none">
                        <span class="font-display text-sm font-bold tracking-tight">Tinubu Must Go</span>
                        <span class="text-[10px] font-medium uppercase tracking-[0.16em] text-[color:var(--ink-mute)]">Ambassadors Space</span>
                    </span>
                </div>

                <div>
                    <span class="amb-hero-eyebrow"><span class="chip-dot" aria-hidden="true"></span> Senatorial leadership</span>
                    <h1 class="mt-4">Pick your <span>zone leader.</span></h1>
                    <p class="mt-4">
                        Every ambassador belongs to one senatorial zone. Nominate yourself, invite others,
                        and vote for the person who will lead TMG in your zone.
                    </p>

                    <ul class="amb-login-points mt-6">
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            One ambassador, one vote — per zone.
                        </li>
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Live zone leaderboard with 1st, 2nd and 3rd badges.
                        </li>
                        <li>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m5 13 4 4L19 7" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Invite ambassadors from your zone and share your nomination.
                        </li>
                    </ul>
                </div>

                <p class="text-[0.7rem] text-[color:var(--ink-mute)]">
                    TMG Ambassadors Space · Senatorial zone leadership
                </p>
            </section>

            <section class="amb-login-card">
                <div class="amb-login-topbar">
                    <x-theme-toggle />
                </div>

                <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Ambassador sign-in</span>
                <h2 class="mt-4 font-display text-2xl font-bold tracking-tight">Welcome back</h2>
                <p class="mt-2 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                    Use your phone number or email and your password. Your TMG number is your initial
                    password — if you have changed it, use the new one.
                </p>

                @if ($errors->any())
                    <p class="mt-5 rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
                        {{ $errors->first() }}
                    </p>
                @endif

                @if (session('status'))
                    <p class="mt-5 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 text-sm font-medium" role="status">
                        {{ session('status') }}
                    </p>
                @endif

                <form method="POST" action="{{ route('tmgamb.login.store') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label class="field-label" for="identity">Phone number or email</label>
                        <input
                            id="identity"
                            name="identity"
                            type="text"
                            class="input"
                            value="{{ old('identity') }}"
                            placeholder="0803 123 4567 or you@example.com"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            autofocus
                        >
                    </div>

                    <div>
                        <label class="field-label" for="password">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="input uppercase pr-12"
                                placeholder="TMG-DEL-NDW-033-XXXXXX"
                                autocomplete="current-password"
                                autocapitalize="characters"
                                spellcheck="false"
                                data-uppercase
                                required
                            >
                            <button
                                type="button"
                                class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex h-8 w-8 items-center justify-center rounded-full text-[color:var(--ink-soft)]"
                                data-password-toggle="password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                <svg data-icon-show width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.8" stroke="currentColor" stroke-width="1.7"/></svg>
                                <svg data-icon-hide class="hidden" width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m4 4 16 16M9.9 6A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a15 15 0 0 1-2.2 2.9M6.3 8.2A15.8 15.8 0 0 0 2.5 12S6 18.5 12 18.5a9.7 9.7 0 0 0 3.6-.7M9.9 9.9a3 3 0 0 0 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            </button>
                        </div>
                        <p class="field-hint">Not case-sensitive · shown in uppercase.</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-[color:var(--ink-soft)]">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-[color:var(--accent)]" @checked(old('remember'))>
                            Keep me signed in
                        </label>
                        <a class="amb-link text-xs" href="{{ route('tmgamb.password.request') }}">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn btn-primary w-full">
                        Sign in
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>

                <details class="mt-5 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                    <summary class="cursor-pointer text-sm font-semibold">Where do I find my TMG number?</summary>
                    <p class="mt-2 text-xs leading-relaxed text-[color:var(--ink-mute)]">
                        It is the reference shown when you completed registration, in the form
                        <span class="amb-secret">TMG-DEL-NDW-033-XXXXXX</span>.
                        It is your initial password and always works as a reset code. If you changed
                        your password and forgot it, use
                        <a class="amb-link" href="{{ route('tmgamb.password.request') }}">Forgot password?</a>
                        to have the TMG number emailed to the address on your registration.
                    </p>
                </details>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-2 text-xs text-[color:var(--ink-mute)]">
                    <span>Not registered yet? <a class="amb-link" href="{{ route('register') }}">Register</a></span>
                    <a class="amb-link" href="{{ route('home') }}">Back to home</a>
                </div>
            </section>
        </div>
    </div>
</x-layouts.tmgamb>
