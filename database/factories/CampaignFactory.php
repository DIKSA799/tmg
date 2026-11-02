<?php

namespace Database\Factories;

use App\Enums\CampaignChannel;
use App\Enums\SmsProvider;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'provider' => SmsProvider::Termii->value,
            'channel' => CampaignChannel::Sms->value,
            'admin_id' => null,
            'state_id' => null,
            'lga_id' => null,
            'scope_label' => 'Manual numbers',
            'sender_id' => 'TMG',
            'subject' => null,
            'message' => fake()->sentence(),
            'status' => Campaign::STATUS_PROCESSING,
            'paused' => false,
            'total_recipients' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
            'skipped_count' => 0,
        ];
    }
}
