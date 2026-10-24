@php
    $ambassador = $nomination->ambassador;
    $selfNominated = $nomination->nominated_by === $nomination->ambassador_id;
    $medal = $rank === 1 ? 'amb-badge-gold' : ($rank === 2 ? 'amb-badge-silver' : ($rank === 3 ? 'amb-badge-bronze' : null));
@endphp

<x-layouts.admin :title="$ambassador?->name ?? 'Nomination'" subtitle="Nomination details">
    <x-slot:actions>
        <a href="{{ route('admin.leaderboard') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Back to leaderboard</a>
        <span class="admin-pill">{{ ucfirst($nomination->status) }}</span>
    </x-slot:actions>

    <section class="admin-card">
        <div class="flex flex-wrap items-center gap-4">
            <x-tmgamb.avatar :ambassador="$ambassador" size="lg" />

            <div class="min-w-0 flex-1">
                <h2 class="font-display text-xl font-bold">{{ $ambassador?->name ?? 'Unknown ambassador' }}</h2>
                <p class="mt-1 text-sm text-[color:var(--ink-mute)]">
                    {{ $nomination->senatorialDistrict?->name }} · {{ $nomination->state?->name }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }} votes</span>
                    @if ($rank)
                        <span @class(['amb-badge', $medal])>Zone rank #{{ $rank }}</span>
                    @endif
                    <span class="admin-pill">{{ ucfirst($nomination->status) }}</span>
                    @if ($selfNominated)
                        <span class="admin-pill">Self-nominated</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                <div class="admin-kpi">
                    <span class="admin-kpi-label">Votes</span>
                    <span class="admin-kpi-value">{{ number_format($nomination->votes_count) }}</span>
                </div>
                <div class="admin-kpi">
                    <span class="admin-kpi-label">Zone rank</span>
                    <span class="admin-kpi-value">{{ $rank ? '#'.$rank : '—' }}</span>
                </div>
                <div class="admin-kpi">
                    <span class="admin-kpi-label">Voters</span>
                    <span class="admin-kpi-value">{{ number_format($voteCount) }}</span>
                </div>
            </div>
        </div>

        @if ($nomination->statement)
            <blockquote class="mt-5 rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 text-sm leading-relaxed text-[color:var(--ink-soft)]">
                “{{ $nomination->statement }}”
            </blockquote>
        @endif
    </section>

    <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr));">
        <section class="admin-card">
            <h3 class="admin-card-title">Ambassador details</h3>
            <p class="admin-card-sub">Contact and geography from the registration record</p>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="amb-label">Full name</dt>
                    <dd class="amb-value">{{ $ambassador?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Phone</dt>
                    <dd class="amb-value">
                        @if ($ambassador?->phone)
                            <a class="amb-link" href="tel:{{ $ambassador->phone }}">{{ $ambassador->phone }}</a>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="amb-label">Email</dt>
                    <dd class="amb-value">
                        @if ($ambassador?->email)
                            <a class="amb-link" href="mailto:{{ $ambassador->email }}">{{ $ambassador->email }}</a>
                        @else
                            Not provided
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="amb-label">State</dt>
                    <dd class="amb-value">{{ $ambassador?->state?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Local government</dt>
                    <dd class="amb-value">{{ $ambassador?->lga?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Ward</dt>
                    <dd class="amb-value">{{ $ambassador?->ward?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Polling unit</dt>
                    <dd class="amb-value">{{ $ambassador?->pollingUnit?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Senatorial district</dt>
                    <dd class="amb-value">{{ $nomination->senatorialDistrict?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Ambassador since</dt>
                    <dd class="amb-value">{{ $ambassador?->created_at?->format('d M Y') ?? '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="admin-card">
            <h3 class="admin-card-title">Nomination details</h3>
            <p class="admin-card-sub">How this nominee entered the ballot</p>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="amb-label">Nominated by</dt>
                    <dd class="amb-value">
                        {{ $selfNominated ? 'Self-nomination' : ($nomination->nominator?->name ?? '—') }}
                    </dd>
                </div>
                <div>
                    <dt class="amb-label">Status</dt>
                    <dd class="amb-value">{{ ucfirst($nomination->status) }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Nominated at</dt>
                    <dd class="amb-value">{{ $nomination->created_at?->format('d M Y, H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="amb-label">Accepted at</dt>
                    <dd class="amb-value">{{ $nomination->accepted_at?->format('d M Y, H:i') ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="amb-label">Nomination ID</dt>
                    <dd class="mt-1">
                        <code class="amb-secret">{{ $nomination->public_id }}</code>
                    </dd>
                </div>
            </dl>
        </section>
    </div>

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="admin-card-title">Recent voters</h3>
                <p class="admin-card-sub">{{ number_format($voteCount) }} total · latest {{ $voters->count() }} shown</p>
            </div>
            <span class="admin-pill">One vote per ambassador per zone</span>
        </div>

        <div class="admin-table-wrap mt-3" style="max-height: 28rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Ambassador</th>
                        <th scope="col">Phone</th>
                        <th scope="col">LGA</th>
                        <th scope="col">Polling unit</th>
                        <th scope="col">Voted at</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($voters as $vote)
                        <tr>
                            <td class="num">{{ $loop->iteration }}</td>
                            <td>{{ $vote->voter?->name ?? '—' }}</td>
                            <td>{{ $vote->voter?->phone ?? '—' }}</td>
                            <td>{{ $vote->voter?->lga?->name ?? '—' }}</td>
                            <td>{{ $vote->voter?->pollingUnit?->name ?? '—' }}</td>
                            <td>{{ $vote->created_at?->format('d M Y, H:i') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-[color:var(--ink-mute)]">No votes recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
