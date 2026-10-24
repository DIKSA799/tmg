<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityLink;
use App\Models\SenatorialDistrict;
use App\Models\Setting;
use App\Models\State;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings', [
            'noticeEnabled' => Setting::flag(Setting::NOTICE_ENABLED, (bool) config('ambassadors.notice.enabled')),
            'noticeTitle' => Setting::get(Setting::NOTICE_TITLE) ?? (string) config('ambassadors.notice.title'),
            'noticeBody' => Setting::get(Setting::NOTICE_BODY) ?? (string) config('ambassadors.notice.body'),
            'termsTitle' => Setting::get(Setting::TERMS_TITLE) ?? (string) config('ambassadors.terms.title'),
            'termsBody' => Setting::get(Setting::TERMS_BODY) ?? (string) config('ambassadors.terms.body'),
            'links' => CommunityLink::query()
                ->orderBy('platform')
                ->orderBy('scope_type')
                ->orderBy('id')
                ->get(),
            'states' => State::query()->orderBy('name')->get(['id', 'name']),
            'districts' => SenatorialDistrict::query()
                ->with('state:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'state_id']),
            'platforms' => CommunityLink::platforms(),
            'scopeTypes' => CommunityLink::scopeTypes(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notice_enabled' => ['boolean'],
            'notice_title' => ['required', 'string', 'max:120'],
            'notice_body' => ['required', 'string', 'max:5000'],
            'terms_title' => ['required', 'string', 'max:120'],
            'terms_body' => ['required', 'string', 'max:5000'],
        ]);

        Setting::put(Setting::NOTICE_ENABLED, $request->boolean('notice_enabled') ? '1' : '0');
        Setting::put(Setting::NOTICE_TITLE, $validated['notice_title']);
        Setting::put(Setting::NOTICE_BODY, $validated['notice_body']);
        Setting::put(Setting::TERMS_TITLE, $validated['terms_title']);
        Setting::put(Setting::TERMS_BODY, $validated['terms_body']);

        return back()->with('status', 'Ambassador settings saved.');
    }
}
