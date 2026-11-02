<?php

namespace App\Services\Messaging;

use App\Enums\CampaignChannel;
use App\Enums\SmsProvider;
use App\Models\Campaign;
use App\Services\Messaging\Contracts\ChannelGateway;
use App\Services\Messaging\Email\EmailGateway;
use App\Services\Messaging\Sms\SendchampGateway;
use App\Services\Messaging\Sms\TermiiGateway;

class ChannelGatewayFactory
{
    public function forCampaign(Campaign $campaign): ChannelGateway
    {
        return match ($campaign->channel) {
            CampaignChannel::Sms => $this->sms($campaign->smsProvider() ?? SmsProvider::Termii),
            CampaignChannel::Email => app(EmailGateway::class),
        };
    }

    public function sms(SmsProvider $provider): ChannelGateway
    {
        return match ($provider) {
            SmsProvider::Termii => app(TermiiGateway::class),
            SmsProvider::Sendchamp => app(SendchampGateway::class),
        };
    }
}
