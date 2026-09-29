<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\Lga;
use App\Models\State;
use App\Models\Ward;
use App\Services\Geography\LocationResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class GeographyController extends Controller
{
    public function states(): JsonResponse
    {
        return $this->cached('geo:states', fn (): array => State::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray());
    }

    public function lgas(State $state): JsonResponse
    {
        return $this->cached("geo:lgas:{$state->id}", fn (): array => $state->lgas()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray());
    }

    public function wards(Lga $lga): JsonResponse
    {
        return $this->cached("geo:wards:{$lga->id}", fn (): array => $lga->wards()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray());
    }

    public function pollingUnits(Ward $ward): JsonResponse
    {
        return $this->cached("geo:pus:{$ward->id}", fn (): array => $ward->pollingUnits()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'pu_code'])
            ->toArray());
    }

    public function locate(LocationRequest $request, LocationResolver $resolver): JsonResponse
    {
        $location = $resolver->resolve(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        return response()->json(['data' => $location])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * @param  Closure(): array<int, array<string, mixed>>  $resolver
     */
    private function cached(string $key, Closure $resolver): JsonResponse
    {
        return response()
            ->json(['data' => Cache::rememberForever($key, $resolver)])
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
