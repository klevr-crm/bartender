<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'bartender@example.com'],
            [
                'name' => 'Bartender',
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            PersonaSeeder::class,
            ScenarioSeeder::class,
        ]);
    }
}
