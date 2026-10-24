<x-layouts.admin title="Leaderboard by state" subtitle="Nominations and votes across every state">
    <x-slot:actions>
        <a href="{{ route('admin.leaderboard') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">All rankings</a>
        <span class="admin-pill">{{ $states->count() }} states</span>
    </x-slot:actions>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.leaderboard') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">All rankings</a>
        <a href="{{ route('admin.leaderboard.states') }}" class="btn btn-primary !px-4 !py-2.5 text-xs">By state</a>
    </div>

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="admin-card-title">Choose a state</h2>
                <p class="admin-card-sub">See its district breakdown and statewide totals</p>
            </div>

            <form method="GET" action="{{ route('admin.leaderboard.states') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="field-label" for="s-state">State</label>
                    <select id="s-state" name="state_id" class="admin-input" style="min-width: 13rem;">
                        <option value="">All states</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected($stateId === $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Apply</button></noscript>
                @if ($stateId)
                    <a href="{{ route('admin.leaderboard.states') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Reset</a>
                @endif
            </form>
        </div>
    </section>

    @if ($selectedState)
        <section class="admin-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="admin-card-title">{{ $selectedState->name }} senatorial districts</h2>
                    <p class="admin-card-sub">Who leads each district, and how many votes are in</p>
                </div>
                <a href="{{ route('admin.leaderboard', ['state_id' => $selectedState->id]) }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">
                    Open {{ $selectedState->name }} ranking
                </a>
            </div>

            <div class="admin-table-wrap mt-3">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">District</th>
                            <th scope="col">Nominations</th>
                            <th scope="col">Votes</th>
                            <th scope="col">Leading nominee</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($selectedDistricts as $district)
                            @php
                                $stats = $districtStats[$district->id] ?? null;
                                $top = $districtTops[$district->id] ?? null;
                            @endphp
                            <tr>
                                <td class="font-semibold">{{ $district->name }}</td>
                                <td class="num">{{ number_format((int) ($stats->nominations ?? 0)) }}</td>
                                <td class="num">{{ number_format((int) ($stats->votes ?? 0)) }}</td>
                                <td>
                                    @if ($top)
                                        <div class="flex items-center gap-2">
                                            <x-tmgamb.avatar :ambassador="$top->ambassador" size="sm" />
                                            <div class="min-w-0">
                                                <a href="{{ route('admin.leaderboard.show', $top) }}" class="font-semibold">{{ $top->ambassador?->name }}</a>
                                                <div class="text-[0.66rem] text-[color:var(--ink-mute)]">{{ number_format($top->votes_count) }} votes</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-[color:var(--ink-mute)]">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.leaderboard', ['state_id' => $selectedState->id, 'district_id' => $district->id]) }}" class="btn btn-ghost !px-3 !py-2 text-xs">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="admin-card-title">States at a glance</h2>
                <p class="admin-card-sub">Every state, its covered districts, total votes and current leader</p>
            </div>
            <span class="admin-pill">Active nominations</span>
        </div>

        <div class="admin-table-wrap mt-3" style="max-height: 40rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">State</th>
                        <th scope="col">Districts covered</th>
                        <th scope="col">Nominations</th>
                        <th scope="col">Votes</th>
                        <th scope="col">Leading nominee</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($states as $state)
                        @php
                            $stats = $aggregates[$state->id] ?? null;
                            $top = $tops[$state->id] ?? null;
                        @endphp
                        <tr>
                            <td class="font-semibold">{{ $state->name }}</td>
                            <td class="num">{{ number_format((int) ($stats->districts ?? 0)) }} / {{ number_format($state->senatorial_districts_count) }}</td>
                            <td class="num">{{ number_format((int) ($stats->nominations ?? 0)) }}</td>
                            <td class="num">{{ number_format((int) ($stats->votes ?? 0)) }}</td>
                            <td>
                                @if ($top)
                                    <div class="flex items-center gap-2">
                                        <x-tmgamb.avatar :ambassador="$top->ambassador" size="sm" />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.leaderboard.show', $top) }}" class="font-semibold">{{ $top->ambassador?->name }}</a>
                                            <div class="text-[0.66rem] text-[color:var(--ink-mute)]">
                                                {{ $top->senatorialDistrict?->name }} · {{ number_format($top->votes_count) }} votes
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[color:var(--ink-mute)]">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.leaderboard', ['state_id' => $state->id]) }}" class="btn btn-ghost !px-3 !py-2 text-xs">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <script>
        (function () {
            const state = document.getElementById('s-state');

            state?.addEventListener('change', function () {
                state.form.requestSubmit();
            });
        })();
    </script>
</x-layouts.admin>
