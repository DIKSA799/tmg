@php
    $duties = [
        "Support TMG's chosen candidate, Atiku Abubakar.",
        'Mobilise and campaign at polling-unit and ward level.',
        'Recruit and organise other TMG volunteers.',
        'Encourage eligible voters to turn out and vote.',
        'Help protect the integrity of votes within the law.',
        'Report electoral irregularities through appropriate channels.',
        'Promote peaceful, lawful and democratic participation.',
    ];
@endphp

<section class="reveal neo-glass overflow-hidden rounded-[34px]" aria-labelledby="ambassador-intro-title">
    <div class="flex flex-col gap-6 border-b border-[color:var(--line)] p-7 sm:p-9 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-xl">
            <span class="chip"><span class="chip-dot" aria-hidden="true"></span> TMG Ambassador Programme</span>
            <h2 id="ambassador-intro-title" class="mt-5 font-display text-3xl font-bold tracking-tight sm:text-4xl">Become a TMG Ambassador</h2>
            <p class="mt-3 font-display text-lg font-bold text-[color:var(--flag-red)]">Volunteer to Save Nigeria.</p>
            <p class="mt-3 text-sm leading-relaxed text-[color:var(--ink-soft)] sm:text-base">As a TMG Ambassador, you will:</p>
        </div>

        <div class="flex items-center gap-2 sm:hidden">
            <button type="button" class="icon-btn disabled:cursor-not-allowed disabled:opacity-40" data-duties-prev aria-label="Previous duties">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <button type="button" class="icon-btn disabled:cursor-not-allowed disabled:opacity-40" data-duties-next aria-label="Next duties">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </div>

    <div class="relative">
        <ul
            class="flex snap-x snap-mandatory gap-3 overflow-x-auto p-7 sm:grid sm:grid-cols-2 sm:overflow-visible sm:p-9 lg:grid-cols-3 xl:grid-cols-4"
            data-duties-track
        >
            @foreach ($duties as $duty)
                <li
                    class="reveal neo-sm min-w-[78%] shrink-0 snap-start rounded-2xl p-5 sm:min-w-0"
                    style="--reveal-delay: {{ $loop->index * 60 }}ms"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[color:var(--accent-soft)] font-display text-sm font-bold text-[color:var(--accent-ink)]">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                    </span>
                    <p class="mt-4 text-sm leading-relaxed text-[color:var(--ink-soft)]">{{ $duty }}</p>
                </li>
            @endforeach
        </ul>

        <p class="px-7 pb-7 text-xs font-semibold uppercase tracking-[0.14em] text-[color:var(--ink-mute)] sm:hidden" data-duties-hint>
            Swipe to explore →
        </p>
    </div>
</section>
