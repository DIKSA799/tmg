<x-layouts.admin title="Geography" subtitle="Coverage of the official register">
    <x-slot:actions>
        <span class="admin-pill">{{ number_format($totals['states']) }} states</span>
        <span class="admin-pill">{{ number_format($totals['lgas']) }} LGAs</span>
        <span class="admin-pill">{{ number_format($totals['wards']) }} wards</span>
        <span class="admin-pill">{{ number_format($totals['units']) }} polling units</span>
    </x-slot:actions>

    <section class="admin-card">
        <h2 class="admin-card-title">States</h2>
        <p class="admin-card-sub">Register size and records captured per state</p>

        <div class="admin-table-wrap" style="max-height: 30rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">State</th>
                        <th scope="col">LGAs</th>
                        <th scope="col">Wards</th>
                        <th scope="col">Polling units</th>
                        <th scope="col">Records captured</th>
                        <th scope="col">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php $busiest = max(1, (int) $stateRecords->max()); @endphp

                    @foreach ($states as $state)
                        @php $records = (int) ($stateRecords[$state->id] ?? 0); @endphp
                        <tr>
                            <td>{{ $state->name }}</td>
                            <td class="num">{{ number_format($state->lgas_count) }}</td>
                            <td class="num">{{ number_format($state->wards_count) }}</td>
                            <td class="num">{{ number_format((int) ($stateUnits[$state->id] ?? 0)) }}</td>
                            <td class="num">{{ number_format($records) }}</td>
                            <td style="min-width: 8rem;">
                                <div class="admin-progress"><span style="width: {{ round(($records / $busiest) * 100, 1) }}%"></span></div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-card">
        <h2 class="admin-card-title">Local government areas</h2>
        <p class="admin-card-sub">Filter by state or search for an LGA</p>

        <form class="admin-grid mb-4" method="GET" action="{{ route('admin.geography') }}" style="grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));">
            <div>
                <label class="field-label" for="g-state">State</label>
                <select id="g-state" class="admin-input" name="state_id">
                    <option value="">All states</option>
                    @foreach ($states as $state)
                        <option value="{{ $state->id }}" @selected(($filters['state_id'] ?? null) == $state->id)>{{ $state->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="field-label" for="g-search">Search</label>
                <input id="g-search" class="admin-input" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="LGA name">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary !px-4 !py-2.5 text-xs">Apply</button>
                <a href="{{ route('admin.geography') }}" class="btn btn-ghost !px-4 !py-2.5 text-xs">Reset</a>
            </div>
        </form>

        <div class="admin-table-wrap" style="max-height: 32rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">LGA</th>
                        <th scope="col">State</th>
                        <th scope="col">Wards</th>
                        <th scope="col">Polling units</th>
                        <th scope="col">Records captured</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lgas as $lga)
                        <tr>
                            <td>{{ $lga->name }}</td>
                            <td>{{ $lga->state?->name }}</td>
                            <td class="num">{{ number_format($lga->wards_count) }}</td>
                            <td class="num">{{ number_format($lga->polling_units_count) }}</td>
                            <td class="num">{{ number_format((int) ($lgaRecords[$lga->id] ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-[color:var(--ink-mute)]">No LGAs match this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination mt-4">
            <span>
                Showing {{ number_format($lgas->firstItem() ?? 0) }}–{{ number_format($lgas->lastItem() ?? 0) }}
                of {{ number_format($lgas->total()) }}
            </span>
            <x-admin.pagination :paginator="$lgas" />
        </div>
    </section>
</x-layouts.admin>
