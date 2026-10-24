@php
    $firstName = explode(' ', trim($ambassador->name))[0] ?? $ambassador->name;
    $district = $ambassador->senatorialDistrict?->name ?? 'your zone';
    $nomination = $ambassador->nomination;
    $shareUrl = $nomination ? route('tmgamb.nominee', $nomination->public_id) : null;
@endphp

<x-layouts.tmgamb title="Dashboard · TMG Ambassadors" description="Your senatorial zone dashboard.">
    <div class="space-y-4">
        @unless ($zoneAssigned)
            <section class="amb-card amb-card-glow">
                <h2 class="amb-card-title">Your senatorial zone is being assigned</h2>
                <p class="amb-card-sub">
                    Your registration is confirmed, but we could not match your LGA to a senatorial
                    district yet. Please check back shortly or contact the TMG desk so we can place
                    you in the right zone.
                </p>
            </section>
        @endunless

        <section class="amb-hero">
            <div class="flex flex-wrap items-start justify-between gap-5">
                <div class="min-w-0">
                    <span class="amb-hero-eyebrow">
                        <span class="chip-dot" aria-hidden="true"></span>
                        {{ $district }} · {{ $ambassador->state?->name }}
                    </span>
                    <h1>Welcome, <em>{{ $firstName }}</em></h1>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-[color:var(--ink-soft)]">
                        This is your {{ $district }} dashboard. See the ambassadors in your zone,
                        nominate yourself, and cast your one vote for the next zone leader.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-1.5">
                        <span class="amb-badge">LGA · {{ $ambassador->lga?->name ?? '—' }}</span>
                        <span class="amb-badge">Ward · {{ $ambassador->ward?->name ?? '—' }}</span>
                        <span class="amb-badge">PU · {{ $ambassador->pollingUnit?->name ?? '—' }}</span>
                    </div>
                </div>

                <div class="flex w-full max-w-xs flex-col gap-2 sm:w-auto">
                    @if ($nomination)
                        <a href="{{ $shareUrl }}" class="btn btn-primary">
                            View my nomination
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </a>
                        <button type="button" class="btn btn-ghost" data-copy="{{ $shareUrl }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M15 5H6a2 2 0 0 0-2 2v9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <span data-copy-label>Copy my share link</span>
                        </button>
                    @elseif ($zoneAssigned)
                        <button type="button" class="btn btn-primary" data-open="nominate-modal">
                            Nominate myself
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        </button>
                    @else
                        <span class="amb-badge">Zone assignment pending</span>
                    @endif

                    <a href="{{ route('tmgamb.leaderboard') }}" class="btn btn-ghost">Open the leaderboard</a>
                </div>
            </div>
        </section>

        <section class="amb-stat-grid" aria-label="Zone statistics">
            <div class="amb-stat">
                <span class="amb-stat-label">Ambassadors</span>
                <span class="amb-stat-value">{{ number_format($stats['ambassadors']) }}</span>
                <span class="amb-stat-note">in {{ $district }}</span>
            </div>
            <div class="amb-stat">
                <span class="amb-stat-label">Nominees</span>
                <span class="amb-stat-value">{{ number_format($stats['nominations']) }}</span>
                <span class="amb-stat-note">on the zone ballot</span>
            </div>
            <div class="amb-stat">
                <span class="amb-stat-label">Votes cast</span>
                <span class="amb-stat-value">{{ number_format($stats['votes']) }}</span>
                <span class="amb-stat-note">one per ambassador</span>
            </div>
            <div class="amb-stat">
                <span class="amb-stat-label">Your rank</span>
                <span class="amb-stat-value">{{ $myRank ? '#'.$myRank : '—' }}</span>
                <span class="amb-stat-note">{{ $nomination ? number_format($nomination->votes_count).' vote(s)' : 'not nominated yet' }}</span>
            </div>
        </section>

        @if ($invitations->isNotEmpty())
            <section class="amb-card amb-card-glow">
                <h2 class="amb-card-title">Nomination invitations</h2>
                <p class="amb-card-sub">Ambassadors in {{ $district }} want you on the ballot.</p>

                <div class="space-y-2">
                    @foreach ($invitations as $invitation)
                        <div class="amb-row">
                            <x-tmgamb.avatar :ambassador="$invitation->inviter" size="sm" />
                            <div class="amb-row-main">
                                <div class="amb-row-title">
                                    {{ $invitation->inviter?->name }}
                                    <span class="amb-badge">invited you</span>
                                </div>
                                <p class="amb-row-meta">
                                    {{ $invitation->inviter?->lga?->name ?? 'Your zone' }}
                                    @if ($invitation->message)
                                        · “{{ $invitation->message }}”
                                    @endif
                                </p>
                            </div>
                            <div class="amb-row-actions">
                                <form method="POST" action="{{ route('tmgamb.invitations.accept', $invitation) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary !px-3 !py-2 text-xs">Accept</button>
                                </form>
                                <form method="POST" action="{{ route('tmgamb.invitations.reject', $invitation) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost !px-3 !py-2 text-xs">Decline</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-[1.45fr_1fr]">
            <section class="amb-card">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="amb-card-title">Zone top 5</h2>
                        <p class="amb-card-sub">Live standings in {{ $district }}</p>
                    </div>
                    <a href="{{ route('tmgamb.leaderboard') }}" class="text-xs font-semibold text-[color:var(--accent)]">Full leaderboard →</a>
                </div>

                @forelse ($zoneTop as $leader)
                    <a href="{{ route('tmgamb.nominee', $leader->public_id) }}" class="amb-row" style="text-decoration:none;">
                        <span @class(['amb-rank', 'amb-rank-1' => $loop->iteration === 1, 'amb-rank-2' => $loop->iteration === 2, 'amb-rank-3' => $loop->iteration === 3])>{{ $loop->iteration }}</span>
                        <x-tmgamb.avatar :ambassador="$leader->ambassador" size="sm" />
                        <div class="amb-row-main">
                            <div class="amb-row-title">{{ $leader->ambassador?->name }}</div>
                            <p class="amb-row-meta">{{ $leader->ambassador?->pollingUnit?->name }}</p>
                        </div>
                        <span class="amb-badge amb-badge-live">{{ number_format($leader->votes_count) }}</span>
                    </a>
                @empty
                    <div class="amb-empty">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 6H5v1a3 3 0 0 0 3 3m8-4h3v1a3 3 0 0 1-3 3M12 13v4m-3 3h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <p class="text-sm font-semibold">No nominees yet</p>
                        <p class="text-xs">Be the first to nominate yourself in {{ $district }}.</p>
                    </div>
                @endforelse
            </section>

            <section class="amb-card">
                <h2 class="amb-card-title">Your ballot</h2>
                <p class="amb-card-sub">One ambassador, one vote</p>

                @if ($myVotedNomination)
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">You voted for</span>
                        <p class="mt-1 font-display text-base font-bold">{{ $myVotedNomination->ambassador?->name }}</p>
                        <p class="mt-1 text-xs text-[color:var(--ink-mute)]">Your vote is locked in this zone.</p>
                        <a class="btn btn-ghost mt-3 !px-3 !py-2 text-xs" href="{{ route('tmgamb.nominee', $myVotedNomination->public_id) }}">View nominee</a>
                    </div>
                @elseif ($nomination)
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Your self-nomination counts as your vote</span>
                        <p class="mt-1 text-xs leading-relaxed text-[color:var(--ink-soft)]">
                            You entered the leaderboard with one vote. If you have not voted for anyone else,
                            your vote is already with you.
                        </p>
                    </div>
                @else
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">You have not voted yet</span>
                        <p class="mt-1 text-xs leading-relaxed text-[color:var(--ink-soft)]">
                            Search the nominees below or open the leaderboard. You can vote for one nominee
                            in {{ $district }}.
                        </p>
                        <a class="btn btn-primary mt-3 !px-3 !py-2 text-xs" href="{{ route('tmgamb.leaderboard') }}">Choose a nominee</a>
                    </div>
                @endif

                @if ($nomination)
                    <hr class="amb-divider">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <span class="amb-label">Your share link</span>
                            <p class="mt-1 text-xs text-[color:var(--ink-mute)]">Share it so ambassadors in {{ $district }} can vote for you.</p>
                        </div>
                        <button type="button" class="icon-btn" data-copy="{{ $shareUrl }}" aria-label="Copy share link">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M15 5H6a2 2 0 0 0-2 2v9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </button>
                    </div>
                @endif
            </section>
        </div>

        <section class="amb-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="amb-card-title">Ambassadors in {{ $district }}</h2>
                    <p class="amb-card-sub">{{ number_format($zoneAmbassadors->total()) }} people · search by name or phone number</p>
                </div>

                <form method="GET" action="{{ route('tmgamb.dashboard') }}" class="amb-search w-full sm:w-72" role="search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or phone number" aria-label="Search ambassadors in your zone">
                    <button type="submit" class="btn btn-primary !px-3 !py-2 text-xs">Search</button>
                </form>
            </div>

            @if ($search !== '')
                <p class="mt-3 text-xs text-[color:var(--ink-mute)]">
                    Showing results for “{{ $search }}”. <a class="amb-link" href="{{ route('tmgamb.dashboard') }}">Clear</a>
                </p>
            @endif

            <div class="mt-3">
                @forelse ($zoneAmbassadors as $person)
                    <div class="amb-row">
                        <x-tmgamb.avatar :ambassador="$person" size="md" />

                        <div class="amb-row-main">
                            <div class="amb-row-title">
                                <span class="truncate">{{ $person->name }}</span>

                                @if ($person->id === $ambassador->id)
                                    <span class="amb-badge">You</span>
                                @endif

                                @if ($person->nomination && $person->nomination->status === 'active')
                                    <span class="amb-badge amb-badge-live">{{ number_format($person->nomination->votes_count) }} vote(s)</span>
                                @endif
                            </div>
                            <p class="amb-row-meta">
                                {{ $person->lga?->name ?? '—' }} · {{ $person->ward?->name ?? '—' }} · {{ $person->pollingUnit?->name ?? '—' }}
                            </p>
                        </div>

                        <div class="amb-row-actions">
                            @if ($person->nomination)
                                <a href="{{ route('tmgamb.nominee', $person->nomination->public_id) }}" class="btn btn-ghost !px-3 !py-2 text-xs">View</a>
                            @elseif ($person->id !== $ambassador->id)
                                <form method="POST" action="{{ route('tmgamb.invite', $person) }}" data-confirm="Invite {{ $person->name }} to be nominated in {{ $district }}?">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost !px-3 !py-2 text-xs">Invite</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="amb-empty">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.6"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        <p class="text-sm font-semibold">No ambassadors found</p>
                        <p class="text-xs">Try a different name or phone number.</p>
                    </div>
                @endforelse
            </div>

            @if ($zoneAmbassadors->hasPages())
                <div class="mt-4">
                    {{ $zoneAmbassadors->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </div>

    @if ($zoneAssigned && ! $nomination)
        <div class="modal-backdrop" id="nominate-modal" data-modal>
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="nominate-modal-title">
                <div class="modal-head">
                    <div>
                        <span class="chip"><span class="chip-dot" aria-hidden="true"></span> {{ $district }}</span>
                        <h2 id="nominate-modal-title" class="mt-3">Nominate myself</h2>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
                </div>

                <p class="text-sm leading-relaxed text-[color:var(--ink-soft)]">
                    You will enter the {{ $district }} leaderboard with <strong>one vote</strong> from your
                    self-nomination. This uses your one vote in the zone. Other ambassadors can then find
                    you, invite you, and vote for you.
                </p>

                <form method="POST" action="{{ route('tmgamb.nominate') }}" class="mt-5 space-y-4">
                    @csrf

                    <div>
                        <label class="field-label" for="statement">Short statement <span class="font-normal text-[color:var(--ink-mute)]">(optional)</span></label>
                        <textarea id="statement" name="statement" class="amb-textarea" maxlength="280" placeholder="Why should ambassadors in your zone vote for you?"></textarea>
                        <p class="field-hint">
                            Tip: <a class="amb-link" href="{{ route('tmgamb.profile') }}">upload a profile photo</a> so ambassadors recognise you on the leaderboard.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Terms</span>
                        <p class="mt-2 max-h-40 overflow-y-auto text-xs leading-relaxed text-[color:var(--ink-soft)]">
                            {{ $termsContent['body'] }}
                        </p>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3 text-sm text-[color:var(--ink-soft)]">
                        <input type="checkbox" name="accept_terms" value="1" class="checkbox" required>
                        <span>I have read and accept the nomination and voting terms.</span>
                    </label>

                    <button type="submit" class="btn btn-primary w-full">Put me on the leaderboard</button>
                </form>
            </div>
        </div>
    @endif
</x-layouts.tmgamb>
