<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ConversationTranscriptController;
use App\Http\Controllers\Api\ScenarioController;
use Illuminate\Support\Facades\Route;

Route::post('/scenarios/{scenario}/start', [ScenarioController::class, 'start'])
    ->name('api.scenarios.start');

Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
    ->name('api.conversations.show');

Route::get('/conversations/{conversation}/transcript', ConversationTranscriptController::class)
    ->name('api.conversations.transcript');
