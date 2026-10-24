<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ambassador;
use App\Models\Lga;
use App\Models\Nomination;
use App\Models\SenatorialDistrict;
use App\Models\State;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SenatorialController extends Controller
{
    public function index(Request $request): View
    {
        $stateId = $request->integer('state_id') ?: null;

        return view('admin.senatorial', [
            'stateId' => $stateId,
            'states' => State::query()
                ->withCount(['senatorialDistricts', 'lgas', 'ambassadors'])
                ->orderBy('name')
                ->get(),
            'districts' => SenatorialDistrict::query()
                ->with('state:id,name')
                ->withCount(['lgas', 'ambassadors', 'nominations'])
                ->when($stateId !== null, fn ($query, $id) => $query->where('state_id', $id))
                ->orderBy('name')
                ->get(),
            'selectedState' => $stateId === null
                ? null
                : State::query()
                    ->with(['senatorialDistricts' => function ($query): void {
                        $query
                            ->withCount('lgas')
                            ->with(['lgas' => fn ($query) => $query->orderBy('name')])
                            ->orderBy('name');
                    }])
                    ->find($stateId),
            'totals' => [
                'districts' => SenatorialDistrict::query()->count(),
                'mapped' => Lga::query()->whereNotNull('senatorial_district_id')->count(),
                'unmapped' => Lga::query()->whereNull('senatorial_district_id')->count(),
                'ambassadors' => Ambassador::query()->count(),
                'nominations' => Nomination::query()->count(),
            ],
        ]);
    }
}
