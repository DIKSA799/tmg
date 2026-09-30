<x-layouts.admin :chrome="false" title="Console access">
    <div class="admin-login-wrap">
        <div class="admin-login-card">
            <div class="flex items-center gap-3">
                <img src="{{ asset('t2.png') }}" alt="" width="2413" height="2875" class="brand-logo">
                <span>
                    <span class="admin-brand-name block">Tinubu Must Go</span>
                    <span class="admin-brand-sub">Operations console</span>
                </span>
            </div>

            <h1 class="mt-6 font-display text-xl font-bold tracking-tight">Console access</h1>
            <p class="mt-1.5 text-sm leading-relaxed text-[color:var(--ink-mute)]">
                Authorised operators only. Attempts are rate limited and this page is excluded from search engines.
            </p>

            @if ($errors->any())
                <p class="mt-5 rounded-xl border border-[color:var(--flag-red)]/40 bg-[color:var(--flag-red)]/10 p-3.5 text-sm font-medium text-[color:var(--flag-red)]" role="alert">
                    {{ $errors->first() }}
                </p>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="field-label" for="username">Username</label>
                    <input id="username" name="username" type="text" class="admin-input" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                </div>

                <div>
                    <label class="field-label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="admin-input" autocomplete="current-password" required>
                </div>

                <label class="flex items-center gap-2 text-sm text-[color:var(--ink-soft)]">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-[color:var(--accent)]">
                    Keep me signed in
                </label>

                <button type="submit" class="btn btn-primary w-full">Enter console</button>
            </form>
        </div>
    </div>
</x-layouts.admin>
