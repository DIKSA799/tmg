<x-layouts.tmgamb
    title="Reset password · TMG Ambassadors Space"
    description="Recover access to the TMG Ambassadors Space with your registered phone number or email."
    :bare="true"
>
    <div class="amb-login">
        <section class="amb-login-card" style="width: min(30rem, 100%);">
            <div class="amb-login-topbar">
                <x-theme-toggle />
            </div>

            <div class="flex items-center gap-3">
                <img src="{{ asset('t2.png') }}" alt="Tinubu Must Go" width="2413" height="2875" class="brand-logo">
                <span class="flex flex-col leading-none">
                    <span class="font-display text-sm font-bold tracking-tight">Tinubu Must Go</span>
                    <span class="text-[10px] font-medium uppercase tracking-[0.16em] text-[color:var(--ink-mute)]">Ambassadors Space</span>
                </span>
            </div>

            <span class="chip mt-5"><span class="chip-dot" aria-hidden="true"></span> Account recovery</span>
            <h1 class="mt-4 font-display text-2xl font-bold tracking-tight">Forgot your password?</h1>
            <p class="mt-2 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                Enter the phone number or email you registered with. If that account has an email
                address on file, we will send the TMG number to it and set it as your password again.
            </p>

            @if (session('status'))
                <p class="mt-5 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 text-sm font-medium" role="status">
                    {{ session('status') }}
                </p>
            @endif

            @if ($errors->any())
                <p class="mt-5 rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
                    {{ $errors->first() }}
                </p>
            @endif

            <form method="POST" action="{{ route('tmgamb.password.email') }}" class="mt-6 space-y-4">
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

                <button type="submit" class="btn btn-primary w-full">
                    Email my TMG number
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </form>

            <p class="mt-4 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 text-xs leading-relaxed text-[color:var(--ink-mute)]">
                We can only email you if an email address is on your registration. Passwords are
                not case-sensitive — they are stored and compared in uppercase.
            </p>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 text-xs text-[color:var(--ink-mute)]">
                <a class="amb-link" href="{{ route('tmgamb.login') }}">Back to sign in</a>
                <a class="amb-link" href="{{ route('home') }}">Back to home</a>
            </div>
        </section>
    </div>
</x-layouts.tmgamb>
