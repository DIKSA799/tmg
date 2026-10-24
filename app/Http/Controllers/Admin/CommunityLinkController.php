<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommunityLinkController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'platform' => ['required', Rule::in(array_keys(CommunityLink::platforms()))],
            'scope_type' => ['required', Rule::in(array_keys(CommunityLink::scopeTypes()))],
            'label' => ['nullable', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ];

        $rules['state_id'] = ['exclude_unless:scope_type,'.CommunityLink::SCOPE_STATE, 'required', 'integer', 'exists:states,id'];
        $rules['district_id'] = ['exclude_unless:scope_type,'.CommunityLink::SCOPE_DISTRICT, 'required', 'integer', 'exists:senatorial_districts,id'];

        $validated = $request->validate($rules);

        $scopeId = match ($validated['scope_type']) {
            CommunityLink::SCOPE_STATE => (int) $validated['state_id'],
            CommunityLink::SCOPE_DISTRICT => (int) $validated['district_id'],
            default => null,
        };

        CommunityLink::query()->create([
            'platform' => $validated['platform'],
            'scope_type' => $validated['scope_type'],
            'scope_id' => $scopeId,
            'label' => $validated['label'] ?? null,
            'url' => $validated['url'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', 'Community link added.');
    }

    public function destroy(CommunityLink $communityLink): RedirectResponse
    {
        $communityLink->delete();

        return back()->with('status', 'Community link removed.');
    }
}
