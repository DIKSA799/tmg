<?php

namespace Database\Factories;

use App\Enums\CampaignChannel;
use App\Models\Campaign;
use App\Models\CampaignMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignMessage>
 */
class CampaignMessageFactory extends Factory
{
    protected $model = CampaignMessage::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'channel' => CampaignChannel::Sms->value,
            'voter_record_id' => null,
            'recipient' => '+234803'.fake()->unique()->numerify('#######'),
            'status' => CampaignMessage::STATUS_PENDING,
        ];
    }
}
