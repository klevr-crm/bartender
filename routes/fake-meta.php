<?php

declare(strict_types=1);

use App\Http\Controllers\FakeMeta\GraphApiController;
use App\Http\Controllers\FakeMeta\MessagesController;
use App\Http\Controllers\FakeMeta\OAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])
    ->name('fake-meta.oauth.authorize');

Route::get('/v23.0/oauth/access_token', [OAuthController::class, 'accessToken'])
    ->name('fake-meta.oauth.access_token');

Route::get('/v23.0/me/businesses', [GraphApiController::class, 'businesses'])
    ->name('fake-meta.businesses');

Route::get('/v23.0/{businessId}/owned_whatsapp_business_accounts', [GraphApiController::class, 'ownedWhatsappBusinessAccounts'])
    ->name('fake-meta.owned_whatsapp_business_accounts');

Route::post('/v23.0/{nodeId}/subscribed_apps', [GraphApiController::class, 'subscribedApps'])
    ->name('fake-meta.subscribed_apps');

Route::post('/v23.0/{phoneId}/messages', MessagesController::class)
    ->name('fake-meta.messages');

Route::get('/v23.0/{phoneId}', [GraphApiController::class, 'phoneNumber'])
    ->name('fake-meta.phone_number');
