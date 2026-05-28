<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Persona;
use App\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scenario>
 */
class ScenarioFactory extends Factory
{
    protected $model = Scenario::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'persona_id' => Persona::factory(),
            'channel' => 'meta_whatsapp_cloud',
            'target_org_id' => fake()->uuid(),
            'script' => null,
        ];
    }
}
