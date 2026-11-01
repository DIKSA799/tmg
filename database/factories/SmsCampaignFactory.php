<?php

namespace Database\Factories;

use App\Enums\SmsProvider;
use App\Models\SmsCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsCampaign>
 */
class SmsCampaignFactory extends Factory
{
    protected $model = SmsCampaign::class;

    public function definition(): array
    {
        return [
            'provider' => SmsProvider::Termii->value,
            'admin_id' => null,
            'state_id' => null,
            'lga_id' => null,
            'scope_label' => 'Manual numbers',
            'channel' => 'sms',
            'sender_id' => 'TMG',
            'message' => fake()->sentence(),
            'status' => SmsCampaign::STATUS_PROCESSING,
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ];
    }
}
