<?php

declare(strict_types=1);

use App\Livewire\Conversations\Index as ConversationsIndex;
use App\Livewire\Conversations\Show as ConversationsShow;
use App\Livewire\Dashboard;
use App\Livewire\Scenarios\Index as ScenariosIndex;
use App\Livewire\Scenarios\Run as ScenariosRun;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.basic')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/scenarios', ScenariosIndex::class)->name('scenarios.index');
    Route::get('/scenarios/{scenario}/run', ScenariosRun::class)->name('scenarios.run');
    Route::get('/conversations', ConversationsIndex::class)->name('conversations.index');
    Route::get('/conversations/{conversation}', ConversationsShow::class)->name('conversations.show');
});
