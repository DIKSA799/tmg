<x-layouts.app
    title="Be part of #TMG · Tinubu Must Go"
    description="Be part of #TMG — pick your state, LGA, ward and polling unit, then capture the record securely."
>
    <header class="pt-3">
        <div class="shell">
            <nav class="glass flex items-center justify-between gap-3 rounded-full px-3 py-2" aria-label="Primary">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5 rounded-full pl-1 pr-2">
                    <img src="{{ asset('t2.png') }}" alt="Tinubu Must Go" width="2413" height="2875" class="brand-logo">
                    <span class="flex min-w-0 flex-col leading-none">
                        <span class="truncate font-display text-sm font-bold tracking-tight">Tinubu Must Go</span>
                        <span class="text-[10px] font-medium uppercase tracking-[0.14em] text-[color:var(--ink-mute)]">Nigeria</span>
                    </span>
                </a>

                <div class="flex flex-none items-center gap-2">
                    <x-auth-actions />
                    <x-theme-toggle />
                    <a href="{{ route('home') }}" class="btn btn-ghost !px-4 !py-2.5 text-sm">Back to home</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="shell py-10 lg:py-14">
        <div class="mx-auto max-w-3xl text-center">
            <span class="chip"><span class="chip-dot" aria-hidden="true"></span> Registration desk</span>
            <h1 class="mt-5 font-display text-3xl font-bold tracking-tight sm:text-4xl">Be part of #TMG</h1>
            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-[color:var(--ink-soft)] sm:text-base">
                Choose the state, LGA, ward and polling unit — or share your location and we will pre-fill it for you.
            </p>
        </div>

        <div class="mx-auto mt-8 max-w-4xl">
            @include('partials.ambassador-intro')
        </div>

        <div class="mx-auto mt-8 max-w-4xl">
            @include('partials.capture-card', ['cardHeading' => 'Ambassador details'])
        </div>
    </main>
</x-layouts.app>
