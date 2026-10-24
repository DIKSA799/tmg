<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nomination;
use App\Models\NominationVote;
use App\Models\SenatorialDistrict;
use App\Models\State;
use App\Services\Ambassadors\Leaderboard;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $nominations = $this->filtered($filters)
            ->paginate(25)
            ->withQueryString();

        $top = $this->filtered($filters)->limit(3)->get();

        $stats = [
            'nominations' => $this->filtered($filters)->reorder()->count(),
            'votes' => (int) $this->filtered($filters)->reorder()->sum('votes_count'),
            'states' => $this->filtered($filters)->reorder()->distinct()->count('state_id'),
            'districts' => $this->filtered($filters)->reorder()->distinct()->count('senatorial_district_id'),
        ];

        return view('admin.leaderboard', [
            'filters' => $filters,
            'nominations' => $nominations,
            'top' => $top,
            'leader' => $top->first(),
            'stats' => $stats,
            'rankOffset' => ($nominations->currentPage() - 1) * $nominations->perPage(),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'districts' => $this->districts($filters['state_id']),
        ]);
    }

    public function states(Request $request): View
    {
        $stateId = $request->integer('state_id') ?: null;

        if ($stateId !== null && ! State::query()->whereKey($stateId)->exists()) {
            $stateId = null;
        }

        $aggregates = Nomination::query()
            ->active()
            ->selectRaw('state_id, count(*) as nominations, coalesce(sum(votes_count), 0) as votes, count(distinct senatorial_district_id) as districts')
            ->groupBy('state_id')
            ->get()
            ->keyBy('state_id');

        $topIds = DB::table('nominations as n')
            ->where('n.status', Nomination::STATUS_ACTIVE)
            ->selectRaw(
                'distinct (select n2.id from nominations as n2 where n2.state_id = n.state_id and n2.status = ? order by n2.votes_count desc, n2.created_at asc, n2.id asc limit 1) as top_id',
                [Nomination::STATUS_ACTIVE],
            )
            ->pluck('top_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $tops = Nomination::query()
            ->with([
                'ambassador:id,name,photo_path,lga_id,polling_unit_id',
                'ambassador.lga:id,name',
                'ambassador.pollingUnit:id,name',
                'senatorialDistrict:id,name',
            ])
            ->whereIn('id', $topIds)
            ->get()
            ->keyBy('state_id');

        $selectedDistricts = collect();
        $districtStats = collect();
        $districtTops = collect();

        if ($stateId !== null) {
            $selectedDistricts = SenatorialDistrict::query()
                ->where('state_id', $stateId)
                ->orderBy('name')
                ->get(['id', 'name']);

            $districtStats = Nomination::query()
                ->active()
                ->where('state_id', $stateId)
                ->selectRaw('senatorial_district_id, count(*) as nominations, coalesce(sum(votes_count), 0) as votes')
                ->groupBy('senatorial_district_id')
                ->get()
                ->keyBy('senatorial_district_id');

            $districtTops = $selectedDistricts->mapWithKeys(function (SenatorialDistrict $district): array {
                $top = Nomination::query()
                    ->active()
                    ->forDistrict($district->id)
                    ->ranked()
                    ->with('ambassador:id,name,photo_path')
                    ->first();

                return [$district->id => $top];
            });
        }

        return view('admin.leaderboard-states', [
            'stateId' => $stateId,
            'selectedState' => $stateId !== null ? State::query()->find($stateId, ['id', 'name']) : null,
            'states' => State::query()->withCount('senatorialDistricts')->orderBy('name')->get(['id', 'name']),
            'aggregates' => $aggregates,
            'tops' => $tops,
            'selectedDistricts' => $selectedDistricts,
            'districtStats' => $districtStats,
            'districtTops' => $districtTops,
        ]);
    }

    public function show(Nomination $nomination, Leaderboard $leaderboard): View
    {
        $nomination->load([
            'ambassador.state:id,name',
            'ambassador.lga:id,name',
            'ambassador.ward:id,name',
            'ambassador.pollingUnit:id,name',
            'senatorialDistrict:id,name',
            'state:id,name',
            'nominator:id,name',
        ]);

        $voters = NominationVote::query()
            ->where('nomination_id', $nomination->id)
            ->with([
                'voter:id,name,phone,lga_id,polling_unit_id',
                'voter.lga:id,name',
                'voter.pollingUnit:id,name',
            ])
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.leaderboard-show', [
            'nomination' => $nomination,
            'rank' => $leaderboard->rankOf($nomination->ambassador, $nomination),
            'voters' => $voters,
            'voteCount' => NominationVote::query()->where('nomination_id', $nomination->id)->count(),
        ]);
    }

    /**
     * @return array{state_id: int|null, district_id: int|null, q: string, status: string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'district_id' => ['nullable', 'integer', 'exists:senatorial_districts,id'],
            'q' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:active,withdrawn,all'],
        ]);

        return [
            'state_id' => isset($validated['state_id']) ? (int) $validated['state_id'] : null,
            'district_id' => isset($validated['district_id']) ? (int) $validated['district_id'] : null,
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => $validated['status'] ?? Nomination::STATUS_ACTIVE,
        ];
    }

    /**
     * @param  array{state_id: int|null, district_id: int|null, q: string, status: string}  $filters
     * @return Builder<Nomination>
     */
    private function filtered(array $filters): Builder
    {
        return Nomination::query()
            ->with([
                'ambassador:id,name,phone,photo_path,lga_id,polling_unit_id',
                'ambassador.lga:id,name',
                'ambassador.pollingUnit:id,name',
                'senatorialDistrict:id,name,state_id',
                'state:id,name',
            ])
            ->when($filters['status'] !== 'all', fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->when($filters['state_id'] !== null, fn (Builder $query): Builder => $query->where('state_id', $filters['state_id']))
            ->when($filters['district_id'] !== null, fn (Builder $query): Builder => $query->where('senatorial_district_id', $filters['district_id']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = $filters['q'];

                $query->whereHas('ambassador', function (Builder $query) use ($term): void {
                    $query->where(function (Builder $query) use ($term): void {
                        $query->where('name', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%');
                    });
                });
            })
            ->ranked();
    }

    /**
     * @return Collection<int, SenatorialDistrict>
     */
    private function districts(?int $stateId): Collection
    {
        return SenatorialDistrict::query()
            ->with('state:id,name')
            ->when($stateId !== null, fn (Builder $query): Builder => $query->where('state_id', $stateId))
            ->orderBy('name')
            ->get(['id', 'name', 'state_id']);
    }
}
