<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChannelInstance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelInstance>
 */
class ChannelInstanceFactory extends Factory
{
    protected $model = ChannelInstance::class;

    public function definition(): array
    {
        return [
            'provider' => 'meta_whatsapp_cloud',
            'channel_type' => 'whatsapp',
            'external_id' => fake()->uuid(),
            'name' => fake()->company(),
            'config' => null,
            'last_post_at' => null,
            'last_mq_ping_at' => null,
        ];
    }
}
