<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\VoterRecord;
use App\Models\Ward;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeographyController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'state_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:60'],
        ]);

        return view('admin.geography', [
            'filters' => $filters,
            'states' => State::query()->withCount(['lgas', 'wards'])->orderBy('name')->get(),
            'stateRecords' => VoterRecord::query()->selectRaw('state_id, count(*) as total')->groupBy('state_id')->pluck('total', 'state_id'),
            'stateUnits' => $this->unitsPerState(),
            'totals' => [
                'states' => State::query()->count(),
                'lgas' => Lga::query()->count(),
                'wards' => Ward::query()->count(),
                'units' => PollingUnit::query()->count(),
            ],
            'lgas' => Lga::query()
                ->with('state:id,name')
                ->withCount(['wards', 'pollingUnits'])
                ->when($filters['state_id'] ?? null, fn ($query, int|string $state) => $query->where('state_id', $state))
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'lgaRecords' => VoterRecord::query()->selectRaw('lga_id, count(*) as total')->groupBy('lga_id')->pluck('total', 'lga_id'),
            'stateHeat' => $this->recordsPerState(),
            'lgaHeat' => $this->recordsPerLga(),
        ]);
    }

    /**
     * Records per state keyed by a slug of the state name, so the boundary file
     * can be matched without relying on database ids.
     *
     * @return Collection<string, int>
     */
    private function recordsPerState(): Collection
    {
        return DB::table('voter_records')
            ->join('states', 'states.id', '=', 'voter_records.state_id')
            ->selectRaw('states.name as name, count(*) as total')
            ->groupBy('states.name')
            ->get()
            ->mapWithKeys(fn (object $row): array => [Str::slug((string) $row->name) => (int) $row->total]);
    }

    /**
     * Records per LGA keyed by "state-slug|lga-slug".
     *
     * @return Collection<string, int>
     */
    private function recordsPerLga(): Collection
    {
        return DB::table('voter_records')
            ->join('lgas', 'lgas.id', '=', 'voter_records.lga_id')
            ->join('states', 'states.id', '=', 'lgas.state_id')
            ->selectRaw('states.name as state, lgas.name as lga, count(*) as total')
            ->groupBy('states.name', 'lgas.name')
            ->get()
            ->mapWithKeys(fn (object $row): array => [
                Str::slug((string) $row->state).'|'.Str::slug((string) $row->lga) => (int) $row->total,
            ]);
    }

    /**
     * Polling units sit three levels below a state, so count them with a join.
     *
     * @return Collection<int|string, int|string>
     */
    private function unitsPerState(): Collection
    {
        return DB::table('polling_units')
            ->join('wards', 'wards.id', '=', 'polling_units.ward_id')
            ->join('lgas', 'lgas.id', '=', 'wards.lga_id')
            ->selectRaw('lgas.state_id as state_id, count(*) as total')
            ->groupBy('lgas.state_id')
            ->pluck('total', 'state_id');
    }
}
