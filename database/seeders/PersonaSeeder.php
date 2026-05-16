<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Seeder;
use Symfony\Component\Yaml\Yaml;

final class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $path = storage_path('personas');
        $files = glob($path.'/*.yaml');

        foreach ($files as $file) {
            $data = Yaml::parseFile($file);

            Persona::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name' => $data['name'],
                    'system_prompt' => $data['system_prompt'],
                    'provider' => $data['provider'] ?? 'openai',
                    'model' => $data['model'] ?? 'gpt-4o-mini',
                    'traits' => $data['traits'] ?? null,
                ]
            );
        }
    }
}
