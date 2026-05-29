<?php

declare(strict_types=1);

use App\Models\Conversation;
use App\Models\Message;

test('marca conversa como stalled quando última mensagem é inbound antiga', function (): void {
    $conversation = new Conversation([
        'id' => 1,
        'status' => 'active',
    ]);

    $oldMessage = new Message([
        'id' => 1,
        'conversation_id' => 1,
        'direction' => 'inbound',
        'role' => 'user',
    ]);
    $oldMessage->forceFill(['created_at' => now()->subMinutes(5)]);

    $conversation->setRelation('messages', collect([$oldMessage]));

    $timeoutMs = (int) config('bartender.timing.inactivity_timeout_ms', 120_000);
    $threshold = now()->subMilliseconds($timeoutMs);

    $lastMessage = $conversation->messages->last();

    expect($lastMessage->direction)->toBe('inbound')
        ->and($lastMessage->created_at->lt($threshold))->toBeTrue();
});

test('NÃO marca stalled quando última mensagem é outbound recente', function (): void {
    $conversation = new Conversation([
        'id' => 2,
        'status' => 'active',
    ]);

    $recentOutbound = new Message([
        'id' => 2,
        'conversation_id' => 2,
        'direction' => 'outbound',
        'role' => 'assistant',
    ]);
    $recentOutbound->forceFill(['created_at' => now()->subMinutes(5)]);

    $conversation->setRelation('messages', collect([$recentOutbound]));

    $lastMessage = $conversation->messages->last();

    expect($lastMessage->direction)->toBe('outbound');
});

test('NÃO marca stalled quando última inbound é recente', function (): void {
    $conversation = new Conversation([
        'id' => 3,
        'status' => 'active',
    ]);

    $recentInbound = new Message([
        'id' => 3,
        'conversation_id' => 3,
        'direction' => 'inbound',
        'role' => 'user',
    ]);
    $recentInbound->forceFill(['created_at' => now()->subSeconds(10)]);

    $conversation->setRelation('messages', collect([$recentInbound]));

    $timeoutMs = (int) config('bartender.timing.inactivity_timeout_ms', 120_000);
    $threshold = now()->subMilliseconds($timeoutMs);

    $lastMessage = $conversation->messages->last();

    expect($lastMessage->direction)->toBe('inbound')
        ->and($lastMessage->created_at->gte($threshold))->toBeTrue();
});
