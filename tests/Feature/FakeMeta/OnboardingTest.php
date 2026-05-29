<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('bartender.fake_meta.enabled', true);
});

test('retorna access token', function (): void {
    getJson('/fake-meta/v23.0/oauth/access_token?code=x')
        ->assertOk()
        ->assertJsonPath('access_token', 'fake-access-token')
        ->assertJsonPath('token_type', 'bearer')
        ->assertJsonPath('expires_in', 5_184_000);
});

test('retorna lista de negócios', function (): void {
    getJson('/fake-meta/v23.0/me/businesses')
        ->assertOk()
        ->assertJsonPath('data.0.id', 'business_123')
        ->assertJsonPath('data.0.name', 'Bartender Business');
});

test('retorna contas WABA do negócio', function (): void {
    getJson('/fake-meta/v23.0/business_123/owned_whatsapp_business_accounts')
        ->assertOk()
        ->assertJsonPath('data.0.id', 'waba_123')
        ->assertJsonPath('data.0.name', 'Bartender WABA')
        ->assertJsonPath('data.0.phone_numbers.data.0.id', '1234567890')
        ->assertJsonPath('data.0.phone_numbers.data.0.display_phone_number', '+55 11 99999-9999')
        ->assertJsonPath('data.0.phone_numbers.data.0.verified_name', 'Bartender');
});

test('retorna dados do número de telefone', function (): void {
    getJson('/fake-meta/v23.0/1234567890')
        ->assertOk()
        ->assertJsonPath('id', '1234567890')
        ->assertJsonPath('display_phone_number', '+55 11 99999-9999')
        ->assertJsonPath('verified_name', 'Bartender');
});

test('inscreve app no nó', function (): void {
    postJson('/fake-meta/v23.0/waba_123/subscribed_apps')
        ->assertOk()
        ->assertJsonPath('success', true);
});

test('renderiza tela de consentimento', function (): void {
    get('/fake-meta/oauth/authorize?redirect_uri=https://crm.test/cb&state=abc')
        ->assertOk()
        ->assertSee('fake_code_')
        ->assertSee('https://crm.test/cb');
});
