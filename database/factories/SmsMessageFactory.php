<?php

namespace Database\Factories;

use App\Models\SmsCampaign;
use App\Models\SmsMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsMessage>
 */
class SmsMessageFactory extends Factory
{
    protected $model = SmsMessage::class;

    public function definition(): array
    {
        return [
            'sms_campaign_id' => SmsCampaign::factory(),
            'voter_record_id' => null,
            'phone' => '+234803'.fake()->unique()->numerify('#######'),
            'status' => SmsMessage::STATUS_PENDING,
        ];
    }
}
