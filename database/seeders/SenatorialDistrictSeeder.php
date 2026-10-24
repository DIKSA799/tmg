<?php

namespace Database\Seeders;

use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Apply the committed LGA -> senatorial district mapping.
 *
 * The dataset covers all 774 LGAs across 37 States (36 states + FCT):
 * 36 states x 3 districts + FCT x 1 = 109 districts. Running this seeder
 * repeatedly is safe: districts are matched by (state, slug) and LGAs are
 * reassigned to the current mapping.
 */
class SenatorialDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $path = config('ambassadors.districts.data');

        if (! is_string($path) || ! is_file($path)) {
            $this->command?->error("Senatorial district dataset not found at {$path}.");

            return;
        }

        /** @var array<string, array<string, array<int, string>>> $data */
        $data = require $path;

        $states = State::query()
            ->get()
            ->keyBy(fn (State $state): string => Str::lower($state->name));

        $created = 0;
        $updated = 0;
        $repaired = 0;

        foreach ($data as $stateName => $districts) {
            $state = $states->get(Str::lower($stateName));

            if ($state === null) {
                $this->command?->warn("Unknown state in district dataset: {$stateName}");

                continue;
            }

            $lgas = Lga::query()
                ->where('state_id', $state->id)
                ->get(['id', 'name'])
                ->keyBy(fn (Lga $lga): string => Str::lower($lga->name));

            foreach ($districts as $districtName => $lgaNames) {
                $district = SenatorialDistrict::query()->updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'slug' => Str::slug($districtName),
                    ],
                    ['name' => $districtName],
                );

                $district->wasRecentlyCreated ? $created++ : $updated++;

                $ids = collect($lgaNames)
                    ->map(fn (string $lga): ?int => $lgas->get(Str::lower($lga))?->id)
                    ->filter()
                    ->values();

                $missing = count($lgaNames) - $ids->count();

                if ($missing > 0) {
                    $this->command?->warn("{$state->name}: {$missing} LGA(s) not found for {$districtName}.");
                }

                Lga::query()->whereIn('id', $ids)->update([
                    'senatorial_district_id' => $district->id,
                ]);

                // Repair ambassador accounts created before the mapping ran
                // (or with a stale district) so their dashboards have a zone.
                $repaired += Ambassador::query()
                    ->whereIn('lga_id', $ids)
                    ->where(function ($query) use ($district): void {
                        $query->whereNull('senatorial_district_id')
                            ->orWhere('senatorial_district_id', '!=', $district->id);
                    })
                    ->update([
                        'senatorial_district_id' => $district->id,
                        'updated_at' => now(),
                    ]);
            }
        }

        $this->command?->info(sprintf(
            'Senatorial districts synced: %d created, %d updated, %d LGAs mapped, %d ambassador(s) assigned.',
            $created,
            $updated,
            Lga::query()->whereNotNull('senatorial_district_id')->count(),
            $repaired,
        ));

        $unassigned = Ambassador::query()->whereNull('senatorial_district_id')->count();

        if ($unassigned > 0) {
            $this->command?->warn("{$unassigned} ambassador(s) still have no district. Check that their LGAs exist in the dataset.");
        }
    }
}
