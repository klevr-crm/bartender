<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ChannelInstance extends Model
{
    protected $fillable = [
        'provider',
        'channel_type',
        'external_id',
        'name',
        'config',
        'last_post_at',
        'last_mq_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'last_post_at' => 'datetime',
            'last_mq_ping_at' => 'datetime',
        ];
    }

    /** @return HasMany<Conversation, $this> */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }
}
