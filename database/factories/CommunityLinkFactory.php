<?php

namespace Database\Factories;

use App\Models\CommunityLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityLink>
 */
class CommunityLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'platform' => CommunityLink::PLATFORM_TELEGRAM,
            'scope_type' => CommunityLink::SCOPE_GLOBAL,
            'scope_id' => null,
            'label' => 'TMG community',
            'url' => 'https://t.me/'.fake()->unique()->slug(2),
            'is_active' => true,
        ];
    }
}
