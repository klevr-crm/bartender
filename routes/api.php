<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\ScenarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function (): void {
    Route::post('/scenarios/{scenario}/start', [ScenarioController::class, 'start'])
        ->name('api.scenarios.start');

    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
        ->name('api.conversations.show');
});
