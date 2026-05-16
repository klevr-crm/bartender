<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class NormalizedOutbound
{
    /**
     * @param  array<string, mixed>|null  $media
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public string $externalMessageId,
        public string $externalConversationId,
        public string $provider,
        public string $channelType,
        public string $content,
        public ?array $media = null,
        public ?array $metadata = null,
    ) {}
}
