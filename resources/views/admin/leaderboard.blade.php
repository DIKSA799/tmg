<x-layouts.admin title="Nomination leaderboard" subtitle="All nominations and votes at a glance">
    <x-slot:actions>
        <a href="{{ route('admin.leaderboard.states') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">By state</a>
        <span class="admin-pill">{{ number_format($stats['nominations']) }} nominations</span>
        <span class="admin-pill">{{ number_format($stats['votes']) }} votes</span>
    </x-slot:actions>

    <section class="admin-grid admin-kpis">
        <div class="admin-kpi">
            <span class="admin-kpi-label">Nominations</span>
            <span class="admin-kpi-value">{{ number_format($stats['nominations']) }}</span>
            <span class="admin-kpi-note">{{ $filters['status'] === 'all' ? 'all statuses' : ucfirst($filters['status']) }}</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">Votes cast</span>
            <span class="admin-kpi-value">{{ number_format($stats['votes']) }}</span>
            <span class="admin-kpi-note">one per ambassador per zone</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">States in view</span>
            <span class="admin-kpi-value">{{ number_format($stats['states']) }}</span>
            <span class="admin-kpi-note">{{ number_format($stats['districts']) }} senatorial districts</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">Leading nominee</span>
            <span class="admin-kpi-value" style="font-size: 1.02rem; line-height: 1.3;">
                {{ $leader?->ambassador?->name ?? '—' }}
            </span>
            <span class="admin-kpi-note">
                @if ($leader)
                    {{ number_format($leader->votes_count) }} votes · {{ $leader->senatorialDistrict?->name }}
                @else
                    no nominations match this filter
                @endif
            </span>
        </div>
    </section>

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="admin-card-title">Filters</h2>
                <p class="admin-card-sub">Ranked by votes, then by nomination time</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.leaderboard') }}" class="btn btn-primary !px-4 !py-2.5 text-xs">All rankings</a>
                <a href="{{ route('admin.leaderboard.states') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">By state</a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.leaderboard') }}" class="admin-grid mt-4" style="grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));">
            <div>
                <label class="field-label" for="f-state">State</label>
                <select id="f-state" name="state_id" class="admin-input">
                    <option value="">All states</option>
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}" @selected($filters['state_id'] === $state->id)>{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="f-district">Senatorial district</label>
                <select id="f-district" name="district_id" class="admin-input">
                    <option value="">All districts</option>
                    @foreach ($districts as $district)
                        <option value="{{ $district->id }}" @selected($filters['district_id'] === $district->id)>
                            {{ $district->state?->name }} — {{ $district->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="f-status">Status</label>
                <select id="f-status" name="status" class="admin-input">
                    <option value="active" @selected($filters['status'] === 'active')>Active</option>
                    <option value="withdrawn" @selected($filters['status'] === 'withdrawn')>Withdrawn</option>
                    <option value="all" @selected($filters['status'] === 'all')>All</option>
                </select>
            </div>

            <div>
                <label class="field-label" for="f-q">Search</label>
                <input id="f-q" class="admin-input" name="q" value="{{ $filters['q'] }}" placeholder="Name or phone number">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Apply</button>
                <a href="{{ route('admin.leaderboard') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Reset</a>
            </div>
        </form>
    </section>

    @if ($top->isNotEmpty())
        <section class="admin-card">
            <h2 class="admin-card-title">Top 3</h2>
            <p class="admin-card-sub">The podium for the current filter</p>

            <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));">
                @foreach ($top as $nomination)
                    @php $rank = $loop->iteration; @endphp
                    <a
                        href="{{ route('admin.leaderboard.show', $nomination) }}"
                        class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4 transition hover:-translate-y-0.5"
                        style="text-decoration: none;"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span @class(['amb-rank', 'amb-rank-1' => $rank === 1, 'amb-rank-2' => $rank === 2, 'amb-rank-3' => $rank === 3])>{{ $rank }}</span>
                            <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }} votes</span>
                        </div>

                        <div class="mt-3 flex items-center gap-3">
                            <x-tmgamb.avatar :ambassador="$nomination->ambassador" size="md" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold">{{ $nomination->ambassador?->name }}</p>
                                <p class="truncate text-xs text-[color:var(--ink-mute)]">{{ $nomination->senatorialDistrict?->name }}</p>
                                <p class="truncate text-[0.68rem] text-[color:var(--ink-mute)]">{{ $nomination->ambassador?->pollingUnit?->name }}</p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="admin-card-title">Full ranking</h2>
                <p class="admin-card-sub">{{ number_format($nominations->total()) }} nominations matching the current filter</p>
            </div>
            <span class="admin-pill">Page {{ $nominations->currentPage() }} of {{ max(1, $nominations->lastPage()) }}</span>
        </div>

        <div class="admin-table-wrap mt-3">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nominee</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Senatorial zone</th>
                        <th scope="col">Polling unit</th>
                        <th scope="col">Votes</th>
                        <th scope="col">Status</th>
                        <th scope="col">Nominated</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($nominations as $nomination)
                        @php $rank = $rankOffset + $loop->iteration; @endphp
                        <tr>
                            <td class="num">
                                <span @class(['amb-rank', 'amb-rank-1' => $rank === 1, 'amb-rank-2' => $rank === 2, 'amb-rank-3' => $rank === 3])>{{ $rank }}</span>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <x-tmgamb.avatar :ambassador="$nomination->ambassador" size="sm" />
                                    <span class="font-semibold">{{ $nomination->ambassador?->name }}</span>
                                </div>
                            </td>
                            <td>{{ $nomination->ambassador?->phone }}</td>
                            <td>
                                {{ $nomination->senatorialDistrict?->name }}
                                <span class="text-[0.66rem] text-[color:var(--ink-mute)]">· {{ $nomination->state?->name }}</span>
                            </td>
                            <td>{{ $nomination->ambassador?->pollingUnit?->name }}</td>
                            <td class="num">
                                <span class="amb-badge amb-badge-live">{{ number_format($nomination->votes_count) }}</span>
                            </td>
                            <td><span class="admin-pill">{{ ucfirst($nomination->status) }}</span></td>
                            <td>{{ $nomination->created_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.leaderboard.show', $nomination) }}" class="btn btn-ghost !px-3 !py-2 text-xs">Details</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-[color:var(--ink-mute)]">No nominations match this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($nominations->hasPages())
            <div class="admin-pagination mt-4">
                <span>
                    Showing {{ number_format($nominations->firstItem() ?? 0) }}–{{ number_format($nominations->lastItem() ?? 0) }}
                    of {{ number_format($nominations->total()) }}
                </span>
                <x-admin.pagination :paginator="$nominations" />
            </div>
        @endif
    </section>

    <script>
        (function () {
            const state = document.getElementById('f-state');
            const district = document.getElementById('f-district');

            state?.addEventListener('change', function () {
                if (district) {
                    district.value = '';
                }

                state.form.requestSubmit();
            });
        })();
    </script>
</x-layouts.admin>
