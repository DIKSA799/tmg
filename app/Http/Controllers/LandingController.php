<?php

namespace App\Http\Controllers;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Models\Lga;
use App\Models\PollingUnit;
use App\Models\State;
use App\Models\Ward;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class LandingController extends Controller
{
    public function __invoke(): View
    {
        return view('landing', [
            'stats' => Cache::rememberForever('geo:stats', fn (): array => [
                'states' => State::query()->count(),
                'lgas' => Lga::query()->count(),
                'wards' => Ward::query()->count(),
                'polling_units' => PollingUnit::query()->count(),
            ]),
            'options' => [
                'gender' => Gender::options(),
                'age_band' => AgeBand::options(),
                'registered_voter_status' => RegisteredVoterStatus::options(),
                'pvc_status' => PvcStatus::options(),
                'preferred_language' => PreferredLanguage::options(),
                'preferred_channel' => PreferredChannel::options(),
            ],
        ]);
    }
}
