<?php

namespace Database\Seeders;

use App\Enums\AgeBand;
use App\Enums\Gender;
use App\Enums\PreferredChannel;
use App\Enums\PreferredLanguage;
use App\Enums\PvcStatus;
use App\Enums\RegisteredVoterStatus;
use App\Models\PollingUnit;
use App\Models\VoterRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SampleVoterRecordSeeder extends Seeder
{
    /**
     * Marker stored in `agent_id` so sample rows can be purged in one query:
     * DELETE FROM voter_records WHERE agent_id = 'sample-data';
     */
    public const MARKER = 'sample-data';

    private const COUNT = 3000;

    private const DAYS = 45;

    private const CHUNK = 500;

    public function run(): void
    {
        if (VoterRecord::query()->where('agent_id', self::MARKER)->exists()) {
            $this->command?->info('Sample voter records already present — skipping.');

            return;
        }

        $units = PollingUnit::query()
            ->with(['ward.lga.state'])
            ->inRandomOrder()
            ->limit(self::COUNT)
            ->get();

        $now = Carbon::now();
        $rows = [];

        foreach ($units as $unit) {
            $ward = $unit->ward;
            $lga = $ward->lga;
            $state = $lga->state;

            // Skew the capture time towards recent days so the trend line rises.
            $capturedAt = $now->copy()
                ->subDays((int) (self::DAYS * (mt_rand() / mt_getrandmax()) ** 0.65))
                ->subMinutes(mt_rand(0, 1439));

            $prefix = ['803', '806', '810', '703', '706', '813', '903', '906', '708', '802'][mt_rand(0, 9)];

            $rows[] = [
                'public_id' => (string) Str::uuid(),
                'idempotency_key' => (string) Str::uuid(),
                'full_name' => fake()->name(),
                'gender' => $this->pick([
                    Gender::Female->value, Gender::Female->value, Gender::Female->value,
                    Gender::Male->value, Gender::Male->value,
                    Gender::PreferNotToSay->value,
                ]),
                'age_band' => $this->pick([
                    AgeBand::EighteenToTwentyFour->value, AgeBand::EighteenToTwentyFour->value,
                    AgeBand::TwentyFiveToThirtyFour->value, AgeBand::TwentyFiveToThirtyFour->value,
                    AgeBand::TwentyFiveToThirtyFour->value, AgeBand::TwentyFiveToThirtyFour->value,
                    AgeBand::ThirtyFiveToFortyFour->value, AgeBand::ThirtyFiveToFortyFour->value,
                    AgeBand::ThirtyFiveToFortyFour->value,
                    AgeBand::FortyFiveToFiftyFour->value, AgeBand::FortyFiveToFiftyFour->value,
                    AgeBand::FiftyFiveToSixtyFour->value,
                    AgeBand::SixtyFivePlus->value,
                ]),
                'phone' => '+234'.$prefix.fake()->numerify('#######'),
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'ward_id' => $ward->id,
                'polling_unit_id' => $unit->id,
                'registered_voter_status' => $this->pick([
                    RegisteredVoterStatus::Yes->value, RegisteredVoterStatus::Yes->value,
                    RegisteredVoterStatus::Yes->value, RegisteredVoterStatus::Yes->value,
                    RegisteredVoterStatus::NotSure->value,
                    RegisteredVoterStatus::No->value,
                ]),
                'pvc_status' => $this->pick([
                    PvcStatus::Collected->value, PvcStatus::Collected->value, PvcStatus::Collected->value,
                    PvcStatus::AwaitingCollection->value, PvcStatus::AwaitingCollection->value,
                    PvcStatus::NotCollected->value,
                    PvcStatus::LostOrDamaged->value,
                    PvcStatus::NotSure->value,
                ]),
                'preferred_language' => $this->pick([
                    PreferredLanguage::Hausa->value, PreferredLanguage::Hausa->value,
                    PreferredLanguage::Yoruba->value, PreferredLanguage::Yoruba->value,
                    PreferredLanguage::Igbo->value, PreferredLanguage::Igbo->value,
                    PreferredLanguage::English->value, PreferredLanguage::English->value,
                    PreferredLanguage::Fulfulde->value,
                    PreferredLanguage::Other->value,
                ]),
                'preferred_language_other' => null,
                'preferred_channel' => $this->pick([
                    PreferredChannel::WhatsApp->value, PreferredChannel::WhatsApp->value,
                    PreferredChannel::WhatsApp->value, PreferredChannel::WhatsApp->value,
                    PreferredChannel::PhoneCall->value, PreferredChannel::PhoneCall->value,
                    PreferredChannel::Sms->value,
                    PreferredChannel::MobileApp->value,
                    PreferredChannel::Other->value,
                ]),
                'preferred_channel_other' => null,
                'consent_to_contact' => mt_rand(1, 100) <= 88,
                'consent_to_data' => true,
                'agent_id' => self::MARKER,
                'ip_address' => ['105.112.', '197.210.', '41.58.', '102.89.'][mt_rand(0, 3)].mt_rand(1, 254).'.'.mt_rand(1, 254),
                'latitude' => round(4.5 + (mt_rand(0, 900) / 100), 7),
                'longitude' => round(3.0 + (mt_rand(0, 1100) / 100), 7),
                'user_agent' => 'Mozilla/5.0 (Linux; Android 13; SM-A045F) AppleWebKit/537.36 Chrome/120 Mobile Safari/537.36',
                'device' => json_encode(['device_id' => (string) Str::uuid(), 'platform' => 'Android']),
                'captured_at' => $capturedAt,
                'created_at' => $capturedAt,
                'updated_at' => $capturedAt,
            ];

            if (count($rows) >= self::CHUNK) {
                VoterRecord::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            VoterRecord::query()->insert($rows);
        }

        $this->command?->info(sprintf("Seeded %s sample voter records (agent_id = '%s').", number_format($units->count()), self::MARKER));
    }

    /**
     * @param  list<string>  $values
     */
    private function pick(array $values): string
    {
        return $values[array_rand($values)];
    }
}
