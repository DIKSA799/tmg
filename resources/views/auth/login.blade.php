<x-layouts.app
    title="Sign in · Tinubu Must Go"
    description="Sign in to the Tinubu Must Go voter registration desk."
>
    <main class="shell flex min-h-dvh flex-col items-center justify-center py-12">
        <div class="w-full max-w-md">
            <a href="{{ route('home') }}" class="mx-auto flex w-fit items-center gap-3">
                <img src="{{ asset('tmg-logo-256.jpg') }}" alt="" width="44" height="44" class="h-11 w-11 flex-none rounded-full bg-white object-contain ring-1 ring-black/5">
                <span class="flex flex-col leading-none">
                    <span class="font-display text-base font-bold tracking-tight">Tinubu Must Go</span>
                    <span class="text-[10px] font-medium uppercase tracking-[0.14em] text-[color:var(--ink-mute)]">Registration desk</span>
                </span>
            </a>

            <div class="neo-glass mt-8 rounded-[30px] p-7 sm:p-9">
                <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Restricted access</span>
                <h1 class="mt-5 font-display text-2xl font-bold tracking-tight sm:text-3xl">Sign in</h1>
                <p class="mt-2 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                    The voter registration desk is available to authorised agents only.
                </p>

                @if ($errors->any())
                    <p class="mt-6 rounded-2xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/5 p-4 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
                        {{ $errors->first() }}
                    </p>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <div class="min-w-0">
                        <label class="field-label" for="username">Username</label>
                        <input
                            id="username"
                            name="username"
                            type="text"
                            class="input"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            autocapitalize="none"
                            spellcheck="false"
                            required
                            autofocus
                        >
                    </div>

                    <div class="min-w-0">
                        <label class="field-label" for="password">Password</label>
                        <input id="password" name="password" type="password" class="input" autocomplete="current-password" required>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-[color:var(--ink-soft)]">
                        <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-[color:var(--accent)]">
                        Keep me signed in
                    </label>

                    <button type="submit" class="btn btn-primary w-full">
                        Sign in
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-[color:var(--ink-mute)]">
                <a href="{{ route('home') }}" class="font-semibold text-[color:var(--accent)] hover:underline">← Back to the home page</a>
            </p>
        </div>
    </main>
</x-layouts.app>
