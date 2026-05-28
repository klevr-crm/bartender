<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonaFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseFactory(PersonaFactory::class)]
final class Persona extends Model
{
    /** @use HasFactory<PersonaFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'system_prompt',
        'provider',
        'model',
        'traits',
    ];

    protected function casts(): array
    {
        return [
            'traits' => 'array',
        ];
    }

    /** @return HasMany<Scenario, $this> */
    public function scenarios(): HasMany
    {
        return $this->hasMany(Scenario::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
