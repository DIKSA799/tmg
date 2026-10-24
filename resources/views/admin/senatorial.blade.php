<x-layouts.admin title="Senatorial districts" subtitle="109 districts · 774 LGAs across 37 states">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($totals['districts']) }} districts</span>
        <span class="admin-pill">{{ number_format($totals['mapped']) }} LGAs mapped</span>
        <span class="admin-pill">{{ number_format($totals['ambassadors']) }} ambassadors</span>
        <span class="admin-pill">{{ number_format($totals['nominations']) }} nominations</span>
    </x-slot:actions>

    <section class="admin-card">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="admin-card-title">Browse zones</h2>
                <p class="admin-card-sub">Pick a state to see its three senatorial districts and the LGAs inside each one.</p>
            </div>

            <form method="GET" action="{{ route('admin.senatorial') }}" class="flex flex-wrap items-end gap-2">
                <div>
                    <label class="field-label" for="state-filter">State</label>
                    <select id="state-filter" name="state_id" class="admin-input" style="min-width: 12rem;" onchange="this.form.submit()">
                        <option value="">All states</option>
                        @foreach ($states as $state)
                            <option value="{{ $state->id }}" @selected($stateId === $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Apply</button></noscript>
                @if ($stateId)
                    <a href="{{ route('admin.senatorial') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Reset</a>
                @endif
            </form>
        </div>
    </section>

    <section class="admin-grid admin-kpis">
        <div class="admin-kpi">
            <span class="admin-kpi-label">Senatorial districts</span>
            <span class="admin-kpi-value">{{ number_format($totals['districts']) }}</span>
            <span class="admin-kpi-note">36 states × 3 + FCT</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">LGAs mapped</span>
            <span class="admin-kpi-value">{{ number_format($totals['mapped']) }}</span>
            <span class="admin-kpi-note">of 774 LGAs</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">Unmapped LGAs</span>
            <span class="admin-kpi-value">{{ number_format($totals['unmapped']) }}</span>
            <span class="admin-kpi-note">{{ $totals['unmapped'] === 0 ? 'mapping complete' : 'needs attention' }}</span>
        </div>
        <div class="admin-kpi">
            <span class="admin-kpi-label">Ambassadors</span>
            <span class="admin-kpi-value">{{ number_format($totals['ambassadors']) }}</span>
            <span class="admin-kpi-note">with accounts</span>
        </div>
    </section>

    @if ($selectedState)
        <section class="admin-card">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="admin-card-title">{{ $selectedState->name }} senatorial districts</h2>
                    <p class="admin-card-sub">{{ $selectedState->senatorialDistricts->count() }} district(s) · {{ $selectedState->senatorialDistricts->sum('lgas_count') }} LGAs</p>
                </div>
                <span class="admin-pill">{{ number_format($selectedState->ambassadors_count) }} ambassadors statewide</span>
            </div>

            <div class="mt-4 grid gap-3" style="grid-template-columns: repeat(auto-fit, minmax(17rem, 1fr));">
                @foreach ($selectedState->senatorialDistricts as $district)
                    <article class="rounded-2xl border border-[color:var(--line)] bg-[color:var(--paper)] p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="font-display text-base font-bold">{{ $district->name }}</h3>
                            <span class="admin-pill">{{ $district->lgas_count }} LGAs</span>
                        </div>
                        <p class="mt-1 text-xs text-[color:var(--ink-mute)]">
                            {{ number_format($district->ambassadors_count) }} ambassadors ·
                            {{ number_format($district->nominations_count) }} nominations
                        </p>
                        <div class="mt-3 flex flex-wrap gap-1">
                            @foreach ($district->lgas as $lga)
                                <span class="admin-pill">{{ $lga->name }}</span>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @else
        <section class="admin-card">
            <h2 class="admin-card-title">States</h2>
            <p class="admin-card-sub">Every state has three senatorial districts, except the FCT with one.</p>

            <div class="admin-table-wrap" style="max-height: 32rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">State</th>
                            <th scope="col">Districts</th>
                            <th scope="col">LGAs</th>
                            <th scope="col">Ambassadors</th>
                            <th scope="col">Browse</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($states as $state)
                            <tr>
                                <td>{{ $state->name }}</td>
                                <td class="num">{{ number_format($state->senatorial_districts_count) }}</td>
                                <td class="num">{{ number_format($state->lgas_count) }}</td>
                                <td class="num">{{ number_format($state->ambassadors_count) }}</td>
                                <td>
                                    <a href="{{ route('admin.senatorial', ['state_id' => $state->id]) }}" class="btn btn-ghost !px-3 !py-2 text-xs">View zones</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-card">
            <h2 class="admin-card-title">All senatorial districts</h2>
            <p class="admin-card-sub">Names, LGA coverage and current activity</p>

            <div class="admin-table-wrap" style="max-height: 32rem;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th scope="col">District</th>
                            <th scope="col">State</th>
                            <th scope="col">LGAs</th>
                            <th scope="col">Ambassadors</th>
                            <th scope="col">Nominations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($districts as $district)
                            <tr>
                                <td>{{ $district->name }}</td>
                                <td>{{ $district->state?->name }}</td>
                                <td class="num">{{ number_format($district->lgas_count) }}</td>
                                <td class="num">{{ number_format($district->ambassadors_count) }}</td>
                                <td class="num">{{ number_format($district->nominations_count) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.admin>
