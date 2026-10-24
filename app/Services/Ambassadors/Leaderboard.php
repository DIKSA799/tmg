<?php

namespace App\Services\Ambassadors;

use App\Models\Ambassador;
use App\Models\Nomination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Leaderboard
{
    /**
     * @return Collection<int, Nomination>
     */
    public function zone(int $districtId): Collection
    {
        return $this->hydrate($this->cachedIds(
            'tmg.leaderboard.zone.'.$districtId,
            fn (): array => Nomination::query()
                ->active()
                ->forDistrict($districtId)
                ->ranked()
                ->limit((int) config('ambassadors.leaderboard.zone_limit', 100))
                ->pluck('id')
                ->all(),
        ));
    }

    /**
     * @return Collection<int, Nomination>
     */
    public function state(int $stateId): Collection
    {
        return $this->hydrate($this->cachedIds(
            'tmg.leaderboard.state.'.$stateId,
            fn (): array => Nomination::query()
                ->active()
                ->where('state_id', $stateId)
                ->ranked()
                ->limit((int) config('ambassadors.leaderboard.state_limit', 5))
                ->pluck('id')
                ->all(),
        ));
    }

    public function rankOf(Ambassador $ambassador, ?Nomination $nomination): ?int
    {
        if ($nomination === null || $ambassador->senatorial_district_id === null) {
            return null;
        }

        $ahead = Nomination::query()
            ->active()
            ->forDistrict($ambassador->senatorial_district_id)
            ->where(function (Builder $query) use ($nomination): void {
                $query->where('votes_count', '>', $nomination->votes_count)
                    ->orWhere(function (Builder $query) use ($nomination): void {
                        $query->where('votes_count', $nomination->votes_count)
                            ->where(function (Builder $query) use ($nomination): void {
                                $query->where('created_at', '<', $nomination->created_at)
                                    ->orWhere(function (Builder $query) use ($nomination): void {
                                        $query->where('created_at', $nomination->created_at)
                                            ->where('id', '<', $nomination->id);
                                    });
                            });
                    });
            })
            ->count();

        return $ahead + 1;
    }

    /**
     * Cache only scalar ids: the database cache store can then unserialize the
     * value in every request without relying on Eloquent classes being loaded.
     *
     * @param  callable(): array<int, int>  $query
     * @return array<int, int>
     */
    private function cachedIds(string $key, callable $query): array
    {
        /** @var array<int, int> $ids */
        $ids = Cache::remember(
            $key,
            (int) config('ambassadors.leaderboard.cache_seconds', 10),
            $query,
        );

        return $ids;
    }

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, Nomination>
     */
    private function hydrate(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $nominations = Nomination::query()
            ->with([
                'ambassador:id,name,photo_path,lga_id,polling_unit_id',
                'ambassador.lga:id,name',
                'ambassador.pollingUnit:id,name',
                'senatorialDistrict:id,name',
            ])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?Nomination => $nominations->get($id))
            ->filter()
            ->values();
    }
}
