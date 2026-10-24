@php
    $nominee = $nomination->ambassador;
    $district = $nomination->senatorialDistrict?->name ?? 'your zone';
    $shareText = 'Vote for '.$nominee?->name.' as TMG '.$district.' zone leader.';
    $whatsappShare = 'https://wa.me/?text='.rawurlencode($shareText.' '.$shareUrl);
    $xShare = 'https://twitter.com/intent/tweet?text='.rawurlencode($shareText).'&url='.rawurlencode($shareUrl);
    $facebookShare = 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl);
    $telegramShare = 'https://t.me/share/url?url='.rawurlencode($shareUrl).'&text='.rawurlencode($shareText);
    $canVote = ! $hasVoted && ! $isSelf && $nomination->status === 'active';
@endphp

<x-layouts.tmgamb :title="$nominee?->name.' · TMG '.$district" description="Nomination profile for the TMG senatorial zone leadership.">
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('tmgamb.leaderboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[color:var(--ink-soft)]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m6 6-6-6 6-6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Back to leaderboard
            </a>
            <span class="amb-badge">{{ $district }}</span>
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.35fr_1fr]">
            <section class="amb-hero">
                <div class="flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                    <x-tmgamb.avatar :ambassador="$nominee" size="xl" />

                    <div class="min-w-0">
                        <span class="amb-hero-eyebrow">
                            <span class="chip-dot" aria-hidden="true"></span>
                            Nominee · {{ $nomination->state?->name }}
                        </span>
                        <h1 class="mt-2 !text-[clamp(1.7rem,3.4vw,2.6rem)]">{{ $nominee?->name }}</h1>
                        <p class="mt-2 text-sm text-[color:var(--ink-soft)]">
                            {{ $nominee?->lga?->name }} · {{ $nominee?->ward?->name }}
                        </p>
                        <p class="text-xs text-[color:var(--ink-mute)]">
                            Polling unit: {{ $nominee?->pollingUnit?->name }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }} vote(s)</span>
                            @if ($rank)
                                <span @class(['amb-badge', 'amb-badge-gold' => $rank === 1, 'amb-badge-silver' => $rank === 2, 'amb-badge-bronze' => $rank === 3])>
                                    Rank #{{ $rank }}
                                </span>
                            @endif
                            @if ($isSelf)
                                <span class="amb-badge">This is you</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($nomination->statement)
                    <blockquote class="mt-5 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                        “{{ $nomination->statement }}”
                    </blockquote>
                @endif

                <div class="mt-5">
                    <span class="amb-label">Share this nomination</span>
                    <div class="amb-share mt-2">
                        <a href="{{ $whatsappShare }}" target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a9.9 9.9 0 0 0-8.5 15L2 22l5.2-1.4A10 10 0 1 0 12 2Z"/></svg>
                            WhatsApp
                        </a>
                        <a href="{{ $xShare }}" target="_blank" rel="noopener noreferrer" aria-label="Share on X">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-4.9-6.5L6.4 22H3.3l7.2-8.3L2.8 2h6.4l4.4 5.9L18.9 2Zm-1.1 18h1.7L7.2 3.9H5.4L17.8 20Z"/></svg>
                            X
                        </a>
                        <a href="{{ $facebookShare }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 22v-8h2.8l.4-3.2h-3.2V8.7c0-.9.3-1.6 1.7-1.6h1.7V4.2c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3v2.4H7.3V14h2.9v8h3.3Z"/></svg>
                            Facebook
                        </a>
                        <a href="{{ $telegramShare }}" target="_blank" rel="noopener noreferrer" aria-label="Share on Telegram">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.7.8l-4.7-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.8 8.8-8c.4-.3-.1-.5-.6-.2L6.6 13.2l-4.6-1.4c-1-.3-1-1 .2-1.5l18-7c.8-.3 1.6.2 1.7.9Z"/></svg>
                            Telegram
                        </a>
                        <button type="button" data-copy="{{ $shareUrl }}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M15 5H6a2 2 0 0 0-2 2v9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <span data-copy-label>Copy link</span>
                        </button>
                        <button
                            type="button"
                            data-native-share="{{ $shareUrl }}"
                            data-share-title="{{ $nominee?->name }} · TMG {{ $district }}"
                            data-share-text="{{ $shareText }}"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="18" cy="5" r="2.4" stroke="currentColor" stroke-width="1.7"/><circle cx="6" cy="12" r="2.4" stroke="currentColor" stroke-width="1.7"/><circle cx="18" cy="19" r="2.4" stroke="currentColor" stroke-width="1.7"/><path d="m8 11 8-4.5M8 13l8 4.5" stroke="currentColor" stroke-width="1.7"/></svg>
                            Share
                        </button>
                    </div>
                </div>
            </section>

            <section class="amb-card">
                <h2 class="amb-card-title">Cast your vote</h2>
                <p class="amb-card-sub">One ambassador, one vote in {{ $district }}</p>

                @if ($isSelf)
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Your nomination</span>
                        <p class="mt-2 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                            Share your link with ambassadors in {{ $district }}. Your self-nomination already
                            counts as your own vote.
                        </p>
                    </div>
                    <button type="button" class="btn btn-primary mt-4 w-full" data-copy="{{ $shareUrl }}">
                        <span data-copy-label>Copy my share link</span>
                    </button>
                @elseif ($votedForThisNominee)
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Vote counted</span>
                        <p class="mt-2 text-sm font-semibold">You voted for {{ $nominee?->name }}.</p>
                        <p class="mt-1 text-xs text-[color:var(--ink-mute)]">Your vote is locked in {{ $district }}.</p>
                    </div>
                @elseif ($hasVoted)
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Vote already used</span>
                        <p class="mt-2 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                            You have already voted in {{ $district }}. One ambassador, one vote.
                        </p>
                        <a href="{{ route('tmgamb.leaderboard') }}" class="btn btn-ghost mt-3 !px-3 !py-2 text-xs">See the leaderboard</a>
                    </div>
                @elseif ($nomination->status !== 'active')
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <span class="amb-label">Nominations closed</span>
                        <p class="mt-2 text-sm text-[color:var(--ink-soft)]">This nomination is not accepting votes.</p>
                    </div>
                @else
                    <p class="text-sm leading-relaxed text-[color:var(--ink-soft)]">
                        You have not used your vote in {{ $district }} yet. Voting is final — choose carefully.
                    </p>
                    <button type="button" class="btn btn-primary mt-4 w-full" data-open="vote-modal">
                        Vote for {{ explode(' ', trim($nominee?->name ?? 'this nominee'))[0] }}
                    </button>
                @endif

                <hr class="amb-divider">

                <div class="grid grid-cols-2 gap-3 text-center">
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-3">
                        <span class="amb-label">Votes</span>
                        <span class="mt-1 block font-display text-xl font-bold">{{ number_format($nomination->votes_count) }}</span>
                    </div>
                    <div class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-3">
                        <span class="amb-label">Zone rank</span>
                        <span class="mt-1 block font-display text-xl font-bold">{{ $rank ? '#'.$rank : '—' }}</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @if ($canVote)
        <div class="modal-backdrop" id="vote-modal" data-modal>
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="vote-modal-title">
                <div class="modal-head">
                    <div>
                        <span class="chip"><span class="chip-dot" aria-hidden="true"></span> {{ $district }}</span>
                        <h2 id="vote-modal-title" class="mt-3">Vote for {{ $nominee?->name }}</h2>
                    </div>
                    <button type="button" class="modal-close" data-modal-close aria-label="Close">×</button>
                </div>

                <p class="text-sm leading-relaxed text-[color:var(--ink-soft)]">
                    This is your only vote in {{ $district }} and it cannot be changed after it is counted.
                </p>

                <div class="mt-4 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                    <span class="amb-label">Terms</span>
                    <p class="mt-2 max-h-40 overflow-y-auto text-xs leading-relaxed text-[color:var(--ink-soft)]">
                        {{ $termsContent['body'] }}
                    </p>
                </div>

                <form method="POST" action="{{ route('tmgamb.vote', $nomination) }}" class="mt-4 space-y-4">
                    @csrf

                    <label class="flex cursor-pointer items-start gap-3 text-sm text-[color:var(--ink-soft)]">
                        <input type="checkbox" name="accept_terms" value="1" class="checkbox" required>
                        <span>I accept the terms and cast my one vote for {{ $nominee?->name }}.</span>
                    </label>

                    <button type="submit" class="btn btn-primary w-full">Cast my vote</button>
                </form>
            </div>
        </div>
    @endif
</x-layouts.tmgamb>
