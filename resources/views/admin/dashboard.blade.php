@php
    $kpis = $analytics['kpis'];

    $cards = [
        ['key' => 'total', 'label' => 'Total records', 'note' => 'All time', 'value' => $kpis['total']],
        ['key' => 'in_range', 'label' => 'This period', 'note' => $analytics['range'].'-day window', 'value' => $kpis['in_range']],
        ['key' => 'today', 'label' => 'Captured today', 'note' => now()->format('d M Y'), 'value' => $kpis['today']],
        ['key' => 'last_7_days', 'label' => 'Last 7 days', 'note' => 'Rolling week', 'value' => $kpis['last_7_days']],
        ['key' => 'consent_contact_rate', 'label' => 'Contact consent', 'note' => 'Agreed to be contacted', 'value' => $kpis['consent_contact_rate'], 'suffix' => '%'],
        ['key' => 'consent_data_rate', 'label' => 'Data consent', 'note' => 'Agreed to processing', 'value' => $kpis['consent_data_rate'], 'suffix' => '%'],
        ['key' => 'pvc_rate', 'label' => 'PVC collected', 'note' => 'Of this period', 'value' => $kpis['pvc_rate'], 'suffix' => '%'],
        ['key' => 'registered_rate', 'label' => 'Registered voters', 'note' => 'Of this period', 'value' => $kpis['registered_rate'], 'suffix' => '%'],
    ];
@endphp

<x-layouts.admin title="Dashboard" subtitle="Live capture analytics across every field the register records">
    <x-slot:actions>
        <span class="admin-pill"><span class="live-dot" aria-hidden="true"></span><span data-live-label>Live</span></span>

        <div class="admin-range" role="group" aria-label="Date range">
            @foreach ([7 => '7d', 30 => '30d', 90 => '90d', 365 => '1y'] as $days => $label)
                <button type="button" data-range="{{ $days }}" @class(['is-active' => $analytics['range'] === $days])>{{ $label }}</button>
            @endforeach
        </div>

        <button type="button" class="btn btn-ghost !px-3.5 !py-2 text-xs" data-refresh>Refresh</button>
    </x-slot:actions>

    {{-- KPIs --}}
    <section class="admin-grid admin-kpis" aria-label="Key numbers">
        @foreach ($cards as $card)
            <article class="admin-kpi">
                <span class="admin-kpi-label">{{ $card['label'] }}</span>
                <span class="admin-kpi-value" data-kpi="{{ $card['key'] }}" data-suffix="{{ $card['suffix'] ?? '' }}">{{ $card['value'] }}</span>
                <span class="admin-kpi-note">{{ $card['note'] }}</span>
            </article>
        @endforeach
    </section>

    {{-- Charts --}}
    <section class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr));">
        <div class="admin-card" style="grid-column: 1 / -1;">
            <h2 class="admin-card-title">Capture trend</h2>
            <p class="admin-card-sub" data-trend-sub>Records per day over the selected period</p>
            <div class="chart-box"><canvas id="chart-trend" aria-label="Records captured per day" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Gender split</h2>
            <p class="admin-card-sub">Who is being registered</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-gender" aria-label="Gender split" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Age bands</h2>
            <p class="admin-card-sub">Age distribution of respondents</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-age" aria-label="Age band distribution" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">PVC status</h2>
            <p class="admin-card-sub">Permanent Voter Card collection</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-pvc" aria-label="PVC status" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Registered voter status</h2>
            <p class="admin-card-sub">Register confirmation</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-voter" aria-label="Registered voter status" role="img"></canvas></div>
        </div>

        <div class="admin-card" style="grid-column: 1 / -1;">
            <h2 class="admin-card-title">Most active LGAs</h2>
            <p class="admin-card-sub">Top local government areas in the selected period</p>
            <div class="chart-box"><canvas id="chart-lgas" aria-label="Most active LGAs" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Preferred language</h2>
            <p class="admin-card-sub">Language of communication</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-languages" aria-label="Preferred language" role="img"></canvas></div>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Preferred channel</h2>
            <p class="admin-card-sub">How people want to be reached</p>
            <div class="chart-box-sm chart-box"><canvas id="chart-channels" aria-label="Preferred channel" role="img"></canvas></div>
        </div>
    </section>

    {{-- Coverage + leaderboards --}}
    <section class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(19rem, 1fr));">
        <div class="admin-card">
            <h2 class="admin-card-title">Register coverage</h2>
            <p class="admin-card-sub">Distinct locations captured in this period</p>
            <ul class="grid gap-4" data-coverage></ul>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Busiest wards</h2>
            <p class="admin-card-sub">Top wards by records</p>
            <ul class="grid gap-3" data-list="top_wards"></ul>
        </div>

        <div class="admin-card">
            <h2 class="admin-card-title">Busiest polling units</h2>
            <p class="admin-card-sub">Top polling units by records</p>
            <ul class="grid gap-3" data-list="top_units"></ul>
        </div>
    </section>

    {{-- Per-state breakdown --}}
    <section class="admin-card">
        <h2 class="admin-card-title">State breakdown</h2>
        <p class="admin-card-sub">Records and geographic spread per state in the selected period</p>
        <div class="admin-table-wrap" style="max-height: 26rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">State</th>
                        <th scope="col">Records</th>
                        <th scope="col">LGAs covered</th>
                        <th scope="col">Wards covered</th>
                        <th scope="col">Polling units covered</th>
                        <th scope="col">Share</th>
                    </tr>
                </thead>
                <tbody data-state-rows></tbody>
            </table>
        </div>
    </section>

    <script>
        window.__ADMIN = {
            analytics: {{ Js::from($analytics) }},
            dataUrl: {{ Js::from(route('admin.data')) }},
        };
    </script>
</x-layouts.admin>
