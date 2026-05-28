<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    protected $model = Persona::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name' => fake()->name(),
            'system_prompt' => fake()->sentence(),
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'traits' => null,
        ];
    }
}
