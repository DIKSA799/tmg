<?php

namespace App\Services\Analytics;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardAnalytics
{
    /** @var list<int> */
    public const RANGES = [7, 30, 90, 365];

    public const DEFAULT_RANGE = 30;

    /**
     * Aggregate every metric the capture form records.
     *
     * @return array<string, mixed>
     */
    public function forRange(int $days): array
    {
        $days = in_array($days, self::RANGES, true) ? $days : self::DEFAULT_RANGE;

        $now = CarbonImmutable::now();
        $since = $now->subDays($days - 1)->startOfDay();

        /** @var Closure(): \Illuminate\Database\Eloquent\Builder<VoterRecord> $scoped */
        $scoped = fn () => VoterRecord::query()->where('created_at', '>=', $since);

        $inRange = $scoped()->count();

        $consent = $scoped()->selectRaw(
            'sum(case when consent_to_contact = 1 then 1 else 0 end) as contact, '
            .'sum(case when consent_to_data = 1 then 1 else 0 end) as data_consent'
        )->first();

        $coverage = $scoped()->selectRaw(
            'count(distinct state_id) as states, count(distinct lga_id) as lgas, '
            .'count(distinct ward_id) as wards, count(distinct polling_unit_id) as units'
        )->first();

        $gender = $this->distribution($scoped, 'gender', Gender::options());
        $ageBands = $this->distribution($scoped, 'age_band', AgeBand::options());
        $pvc = $this->distribution($scoped, 'pvc_status', PvcStatus::options());
        $voterStatus = $this->distribution($scoped, 'registered_voter_status', RegisteredVoterStatus::options());
        $languages = $this->distribution($scoped, 'preferred_language', PreferredLanguage::options());
        $channels = $this->distribution($scoped, 'preferred_channel', PreferredChannel::options());

        return [
            'range' => $days,
            'generated_at' => $now->toIso8601String(),
            'kpis' => [
                'total' => VoterRecord::query()->count(),
                'in_range' => $inRange,
                'today' => VoterRecord::query()->where('created_at', '>=', $now->startOfDay())->count(),
                'last_7_days' => VoterRecord::query()->where('created_at', '>=', $now->subDays(6)->startOfDay())->count(),
                'consent_contact_rate' => $this->rate((int) ($consent->contact ?? 0), $inRange),
                'consent_data_rate' => $this->rate((int) ($consent->data_consent ?? 0), $inRange),
                'pvc_rate' => $this->rate($this->value($pvc, PvcStatus::Collected->label()), $inRange),
                'registered_rate' => $this->rate($this->value($voterStatus, RegisteredVoterStatus::Yes->label()), $inRange),
            ],
            'series' => $this->dailySeries($since, $days),
            'gender' => $gender,
            'age_bands' => $ageBands,
            'pvc' => $pvc,
            'voter_status' => $voterStatus,
            'languages' => $languages,
            'channels' => $channels,
            'coverage' => [
                ['label' => 'States + FCT', 'covered' => (int) $coverage->states, 'total' => State::query()->count()],
                ['label' => 'LGAs', 'covered' => (int) $coverage->lgas, 'total' => Lga::query()->count()],
                ['label' => 'Wards', 'covered' => (int) $coverage->wards, 'total' => Ward::query()->count()],
                ['label' => 'Polling units', 'covered' => (int) $coverage->units, 'total' => PollingUnit::query()->count()],
            ],
            'top_lgas' => $this->topBy('lgas', 'lga_id', $scoped),
            'top_wards' => $this->topBy('wards', 'ward_id', $scoped),
            'top_units' => $this->topBy('polling_units', 'polling_unit_id', $scoped),
            'by_state' => $this->byState($scoped),
        ];
    }

    /**
     * @param  array<string, string>  $labels
     * @return array{labels: list<string>, values: list<int>}
     */
    private function distribution(Closure $scoped, string $column, array $labels): array
    {
        /** @var array<string, int|string> $counts */
        $counts = $scoped()->select($column, DB::raw('count(*) as total'))->groupBy($column)->pluck('total', $column)->all();

        $out = ['labels' => [], 'values' => []];

        foreach ($labels as $value => $label) {
            $out['labels'][] = $label;
            $out['values'][] = (int) ($counts[$value] ?? 0);
        }

        foreach ($counts as $value => $total) {
            if (! array_key_exists($value, $labels)) {
                $out['labels'][] = Str::headline((string) $value);
                $out['values'][] = (int) $total;
            }
        }

        return $out;
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function dailySeries(CarbonImmutable $since, int $days): array
    {
        /** @var array<string, int|string> $rows */
        $rows = VoterRecord::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->all();

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $since->addDays($offset);
            $labels[] = $date->format('d M');
            $values[] = (int) ($rows[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param  Closure(): \Illuminate\Database\Eloquent\Builder<VoterRecord>  $scoped
     * @return list<array{label: string, total: int}>
     */
    private function topBy(string $table, string $foreignKey, Closure $scoped): array
    {
        return $scoped()
            ->join($table, $table.'.id', '=', 'voter_records.'.$foreignKey)
            ->selectRaw($table.'.name as label, count(*) as total')
            ->groupBy($table.'.id', $table.'.name')
            ->orderByDesc('total')
            ->limit(7)
            ->get()
            ->map(fn (object $row): array => ['label' => (string) $row->label, 'total' => (int) $row->total])
            ->all();
    }

    /**
     * @param  Closure(): \Illuminate\Database\Eloquent\Builder<VoterRecord>  $scoped
     * @return list<array{name: string, total: int, lgas: int, wards: int, units: int}>
     */
    private function byState(Closure $scoped): array
    {
        return $scoped()
            ->join('states', 'states.id', '=', 'voter_records.state_id')
            ->selectRaw(
                'states.name as name, count(*) as total, '
                .'count(distinct voter_records.lga_id) as lgas, '
                .'count(distinct voter_records.ward_id) as wards, '
                .'count(distinct voter_records.polling_unit_id) as units'
            )
            ->groupBy('states.id', 'states.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn (object $row): array => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
                'lgas' => (int) $row->lgas,
                'wards' => (int) $row->wards,
                'units' => (int) $row->units,
            ])
            ->all();
    }

    /**
     * @param  array{labels: list<string>, values: list<int>}  $distribution
     */
    private function value(array $distribution, string $label): int
    {
        $index = array_search($label, $distribution['labels'], true);

        return $index === false ? 0 : $distribution['values'][$index];
    }

    private function rate(int $value, int $total): float
    {
        return $total === 0 ? 0.0 : round(($value / $total) * 100, 1);
    }
}
