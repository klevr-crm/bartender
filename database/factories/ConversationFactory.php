<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ChannelInstance;
use App\Models\Conversation;
use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    public function definition(): array
    {
        return [
            'scenario_id' => Scenario::factory(),
            'persona_id' => Persona::factory(),
            'channel_instance_id' => ChannelInstance::factory(),
            'external_conversation_id' => fake()->uuid(),
            'status' => 'active',
            'turn_count' => 0,
            'started_at' => now(),
            'ended_at' => null,
            'error' => null,
            'end_reason' => null,
        ];
    }
}
