<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            Setting::NOTICE_ENABLED => config('ambassadors.notice.enabled') ? '1' : '0',
            Setting::NOTICE_TITLE => config('ambassadors.notice.title'),
            Setting::NOTICE_BODY => config('ambassadors.notice.body'),
            Setting::TERMS_TITLE => config('ambassadors.terms.title'),
            Setting::TERMS_BODY => config('ambassadors.terms.body'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
