<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class TurnResult
{
    /** @param  array<string, mixed>|null  $media */
    public function __construct(
        public string $text,
        public ?array $media,
        public int $typingDelayMs,
        public bool $closeConversation,
    ) {}
}
