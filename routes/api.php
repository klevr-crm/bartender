<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ConversationTranscriptController;
use Illuminate\Support\Facades\Route;

Route::get('/conversations/{conversation}/transcript', ConversationTranscriptController::class)
    ->name('api.conversations.transcript');
