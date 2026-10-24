<?php

namespace App\Services\Ambassadors;

use App\Models\Ambassador;
use App\Models\CommunityLink;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Content
{
    /**
     * @return array{title: string, body: string}
     */
    public function terms(): array
    {
        return [
            'title' => Setting::get(Setting::TERMS_TITLE) ?? (string) config('ambassadors.terms.title'),
            'body' => Setting::get(Setting::TERMS_BODY) ?? (string) config('ambassadors.terms.body'),
        ];
    }

    /**
     * @return array{enabled: bool, title: string, body: string}
     */
    public function notice(): array
    {
        return [
            'enabled' => Setting::flag(Setting::NOTICE_ENABLED, (bool) config('ambassadors.notice.enabled')),
            'title' => Setting::get(Setting::NOTICE_TITLE) ?? (string) config('ambassadors.notice.title'),
            'body' => Setting::get(Setting::NOTICE_BODY) ?? (string) config('ambassadors.notice.body'),
        ];
    }

    /**
     * Resolve the most specific community links for this ambassador.
     *
     * @return array{telegram: string|null, whatsapp: string|null}
     */
    public function community(?Ambassador $ambassador): array
    {
        $stateId = $ambassador?->state_id;
        $districtId = $ambassador?->senatorial_district_id;
        $version = CommunityLink::version();

        // An empty string is a cached "no link" sentinel: remember() would
        // otherwise re-query on every request whenever the value is null.
        $telegram = Cache::remember(
            "tmg.community.v{$version}.telegram.{$stateId}.{$districtId}",
            300,
            fn (): string => CommunityLink::resolve(CommunityLink::PLATFORM_TELEGRAM, $stateId, $districtId)?->url ?? '',
        );

        $whatsapp = Cache::remember(
            "tmg.community.v{$version}.whatsapp.{$stateId}.{$districtId}",
            300,
            fn (): string => CommunityLink::resolve(CommunityLink::PLATFORM_WHATSAPP, $stateId, $districtId)?->url ?? '',
        );

        return [
            'telegram' => $telegram !== '' ? $telegram : null,
            'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
        ];
    }
}
