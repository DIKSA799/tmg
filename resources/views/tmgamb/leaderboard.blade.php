@php
    $district = $ambassador->senatorialDistrict?->name ?? 'Your zone';
    $nomination = $ambassador->nomination;
    $ranks = $zoneLeaders->values()->mapWithKeys(fn ($leader, $index) => [$leader->id => $index + 1]);
    $top = $zoneLeaders->take(3);
    $podium = $top->count() === 3 ? collect([$top[1], $top[0], $top[2]]) : $top;
    $rest = $zoneLeaders->slice(3);
    $shareUrl = $nomination ? route('tmgamb.nominee', $nomination->public_id) : null;
@endphp

<x-layouts.tmgamb title="Leaderboard · TMG Ambassadors" description="Your senatorial zone leaderboard and state top five.">
    <div class="space-y-4">
        <section class="amb-hero">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="amb-hero-eyebrow">
                        <span class="chip-dot" aria-hidden="true"></span>
                        {{ $district }}
                    </span>
                    <h1>Zone <em>leaderboard</em></h1>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-[color:var(--ink-soft)]">
                        Ranked by votes in {{ $district }}. The top three carry gold, silver and bronze
                        badges. Every ambassador gets one vote.
                    </p>
                </div>

                <div class="grid w-full grid-cols-3 gap-2 sm:w-auto">
                    <div class="amb-stat">
                        <span class="amb-stat-label">Nominees</span>
                        <span class="amb-stat-value">{{ number_format($zoneLeaders->count()) }}</span>
                    </div>
                    <div class="amb-stat">
                        <span class="amb-stat-label">Votes</span>
                        <span class="amb-stat-value">{{ number_format($zoneLeaders->sum('votes_count')) }}</span>
                    </div>
                    <div class="amb-stat">
                        <span class="amb-stat-label">You</span>
                        <span class="amb-stat-value">{{ $myRank ? '#'.$myRank : '—' }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if ($nomination)
            <section class="amb-card flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <x-tmgamb.avatar :ambassador="$ambassador" size="md" />
                    <div>
                        <span class="amb-label">Your standing</span>
                        <p class="font-display text-lg font-bold">
                            {{ $myRank ? 'Rank #'.$myRank : 'Not ranked yet' }}
                            <span class="text-sm font-semibold text-[color:var(--ink-mute)]">· {{ number_format($nomination->votes_count) }} vote(s)</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ $shareUrl }}" class="btn btn-ghost !px-3 !py-2 text-xs">View my nomination</a>
                    <button type="button" class="btn btn-primary !px-3 !py-2 text-xs" data-copy="{{ $shareUrl }}">
                        <span data-copy-label>Copy share link</span>
                    </button>
                </div>
            </section>
        @else
            <section class="amb-card flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="amb-card-title">You are not on the ballot yet</h2>
                    <p class="amb-card-sub">Nominate yourself on the dashboard to join with one vote.</p>
                </div>
                <a href="{{ route('tmgamb.dashboard') }}" class="btn btn-primary !px-4 !py-2.5 text-sm">Go to my dashboard</a>
            </section>
        @endif

        <section class="amb-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="amb-card-title">Top of {{ $district }}</h2>
                    <p class="amb-card-sub">1st, 2nd and 3rd carry the zone badges</p>
                </div>
                <span class="amb-badge">Live · updated {{ now()->format('H:i') }}</span>
            </div>

            @if ($top->isEmpty())
                <div class="amb-empty">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8 4h8v5a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 6H5v1a3 3 0 0 0 3 3m8-4h3v1a3 3 0 0 1-3 3M12 13v4m-3 3h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    <p class="text-sm font-semibold">No nominations in {{ $district }} yet</p>
                    <p class="text-xs">Be the first name on this leaderboard.</p>
                    <a href="{{ route('tmgamb.dashboard') }}" class="btn btn-primary mt-2 !px-4 !py-2.5 text-sm">Nominate myself</a>
                </div>
            @else
                <div class="amb-podium mt-4">
                    @foreach ($podium as $leader)
                        @php $rank = $ranks[$leader->id] ?? 1; @endphp
                        <a href="{{ route('tmgamb.nominee', $leader->public_id) }}" @class(['amb-podium-card', 'amb-podium-1' => $rank === 1, 'amb-podium-2' => $rank === 2, 'amb-podium-3' => $rank === 3])>
                            <span @class(['amb-badge', 'amb-badge-gold' => $rank === 1, 'amb-badge-silver' => $rank === 2, 'amb-badge-bronze' => $rank === 3])>
                                {{ $rank === 1 ? '1st' : ($rank === 2 ? '2nd' : '3rd') }}
                            </span>
                            <x-tmgamb.avatar :ambassador="$leader->ambassador" :size="$rank === 1 ? 'lg' : 'md'" />
                            <span class="amb-podium-name">{{ $leader->ambassador?->name }}</span>
                            <span class="amb-podium-votes">{{ number_format($leader->votes_count) }} <small>votes</small></span>
                            <span class="text-[0.62rem] text-[color:var(--ink-mute)]">{{ $leader->ambassador?->pollingUnit?->name }}</span>
                        </a>
                    @endforeach
                </div>

                @if ($rest->isNotEmpty())
                    <div class="mt-4">
                        @foreach ($rest as $leader)
                            <a href="{{ route('tmgamb.nominee', $leader->public_id) }}" class="amb-row" style="text-decoration:none;">
                                <span class="amb-rank">{{ $ranks[$leader->id] ?? $loop->iteration + 3 }}</span>
                                <x-tmgamb.avatar :ambassador="$leader->ambassador" size="sm" />
                                <div class="amb-row-main">
                                    <div class="amb-row-title">{{ $leader->ambassador?->name }}</div>
                                    <p class="amb-row-meta">
                                        {{ $leader->ambassador?->lga?->name }} · {{ $leader->ambassador?->pollingUnit?->name }}
                                    </p>
                                </div>
                                <span class="amb-badge amb-badge-live">{{ number_format($leader->votes_count) }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>

        <section class="amb-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="amb-card-title">{{ $ambassador->state?->name }} top 5</h2>
                    <p class="amb-card-sub">Across every senatorial zone in the state</p>
                </div>
                <span class="amb-badge">Statewide</span>
            </div>

            @forelse ($stateLeaders as $leader)
                @php $mine = $leader->senatorial_district_id === $ambassador->senatorial_district_id; @endphp

                @if ($mine)
                    <a href="{{ route('tmgamb.nominee', $leader->public_id) }}" class="amb-row" style="text-decoration:none;">
                @else
                    <div class="amb-row">
                @endif
                        <span @class(['amb-rank', 'amb-rank-1' => $loop->iteration === 1, 'amb-rank-2' => $loop->iteration === 2, 'amb-rank-3' => $loop->iteration === 3])>{{ $loop->iteration }}</span>
                        <x-tmgamb.avatar :ambassador="$leader->ambassador" size="sm" />
                        <div class="amb-row-main">
                            <div class="amb-row-title">
                                <span class="truncate">{{ $leader->ambassador?->name }}</span>
                                @if ($mine)
                                    <span class="amb-badge">Your zone</span>
                                @endif
                            </div>
                            <p class="amb-row-meta">{{ $leader->senatorialDistrict?->name }} · {{ $leader->ambassador?->pollingUnit?->name }}</p>
                        </div>
                        <span class="amb-badge amb-badge-live">{{ number_format($leader->votes_count) }}</span>
                @if ($mine)
                    </a>
                @else
                    </div>
                @endif
            @empty
                <div class="amb-empty">
                    <p class="text-sm font-semibold">No state nominations yet</p>
                    <p class="text-xs">Standings appear as zones start nominating.</p>
                </div>
            @endforelse
        </section>

        <p class="px-1 text-center text-[0.7rem] leading-relaxed text-[color:var(--ink-mute)]">
            Ranks are ordered by votes, then by nomination time. A tie keeps the earlier nomination ahead.
        </p>
    </div>
</x-layouts.tmgamb>
