<?php

namespace App\Http\Controllers\Ambassador;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAmbassadorPhotoRequest;
use App\Models\Ambassador;
use App\Services\Ambassadors\AmbassadorPhoto;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $ambassador->loadMissing([
            'voterRecord:id,reference,full_name,phone,email,gender,age_band,captured_at',
            'state:id,name',
            'lga:id,name',
            'ward:id,name',
            'pollingUnit:id,name',
            'senatorialDistrict:id,name',
            'nomination',
        ]);

        return view('tmgamb.profile', [
            'ambassador' => $ambassador,
        ]);
    }

    public function updatePhoto(UpdateAmbassadorPhotoRequest $request, AmbassadorPhoto $photos): RedirectResponse
    {
        /** @var Ambassador $ambassador */
        $ambassador = $request->user('ambassador');

        $photos->store($ambassador, $request->file('photo'));

        return back()->with('status', 'Your photo has been updated.');
    }
}
