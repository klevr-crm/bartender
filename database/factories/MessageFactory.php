<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'direction' => 'inbound',
            'role' => 'user',
            'content' => fake()->sentence(),
            'media' => null,
            'external_message_id' => fake()->uuid(),
            'status' => 'sent',
            'sent_at' => now(),
            'delivered_at' => null,
            'read_at' => null,
        ];
    }
}
