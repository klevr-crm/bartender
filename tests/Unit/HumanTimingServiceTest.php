<?php

declare(strict_types=1);

use App\Personas\HumanTimingService;

beforeEach(function (): void {
    config()->set('bartender.timing.mode', 'realistic');
    config()->set('bartender.timing.realistic.median_ms', 75_000);
    config()->set('bartender.timing.realistic.sigma', 0.6);
    config()->set('bartender.timing.realistic.max_ms', 480_000);
    config()->set('bartender.timing.realistic.read_ms_per_char', 15);
    config()->set('bartender.timing.realistic.read_cap_ms', 20_000);
    config()->set('bartender.timing.fast.min_ms', 1_000);
    config()->set('bartender.timing.fast.max_ms', 5_000);
});

test('modo fast retorna delay dentro do range configurado', function (): void {
    config()->set('bartender.timing.mode', 'fast');

    $fixedRandom = 0.5;
    $service = new HumanTimingService(fn (): float => $fixedRandom);

    $delay = $service->computeDelayMs('qualquer texto');

    expect($delay)->toBe(3_000);
});

test('modo fast respeita min e max configurados', function (): void {
    config()->set('bartender.timing.mode', 'fast');
    config()->set('bartender.timing.fast.min_ms', 2_000);
    config()->set('bartender.timing.fast.max_ms', 4_000);

    $serviceMin = new HumanTimingService(fn (): float => 0.0);
    expect($serviceMin->computeDelayMs('x'))->toBe(2_000);

    $serviceMax = new HumanTimingService(fn (): float => 1.0);
    expect($serviceMax->computeDelayMs('x'))->toBe(4_000);
});

test('modo realistic com gerador fixo retorna delay determinístico esperado', function (): void {
    // Box-Muller com u1=0.5, u2=0.5:
    // z = sqrt(-2*ln(0.5)) * cos(2*pi*0.5)
    // ln(0.5) = -0.693147
    // -2*-0.693147 = 1.386294
    // sqrt(1.386294) = 1.17741
    // cos(pi) = -1
    // z = -1.17741
    // mu = ln(75000) = 11.22524
    // exp(11.22524 + 0.6*-1.17741) = exp(11.22524 - 0.706446) = exp(10.51879) = 37005
    $service = new HumanTimingService(function (): float {
        static $calls = 0;
        $calls++;

        return 0.5;
    });

    $delay = $service->computeDelayMs('');

    expect($delay)->toBe(37_005);
});

test('modo realistic respeita max_ms cap', function (): void {
    config()->set('bartender.timing.realistic.max_ms', 10_000);

    $service = new HumanTimingService(fn (): float => 0.99);
    $delay = $service->computeDelayMs('');

    expect($delay)->toBeLessThanOrEqual(10_000 + 1); // +1 por arredondamento
});

test('modo realistic soma tempo de leitura proporcional ao tamanho do texto', function (): void {
    $service = new HumanTimingService(fn (): float => 0.5);

    $delayShort = $service->computeDelayMs('oi');
    $delayLong = $service->computeDelayMs(str_repeat('a', 1_000));

    expect($delayLong)->toBeGreaterThan($delayShort);
});

test('tempo de leitura é capado em read_cap_ms', function (): void {
    config()->set('bartender.timing.realistic.read_cap_ms', 5_000);
    config()->set('bartender.timing.realistic.read_ms_per_char', 100);

    $service = new HumanTimingService(fn (): float => 0.5);

    // 100 chars * 100ms = 10_000ms, mas cap é 5_000
    $delay = $service->computeDelayMs(str_repeat('a', 100));

    $baseDelay = 37_005; // do teste determinístico
    expect($delay)->toBe($baseDelay + 5_000);
});

test('delay nunca é menor que 1000ms', function (): void {
    config()->set('bartender.timing.realistic.median_ms', 100);
    config()->set('bartender.timing.realistic.read_ms_per_char', 0);

    $service = new HumanTimingService(fn (): float => 0.01);
    $delay = $service->computeDelayMs('');

    expect($delay)->toBeGreaterThanOrEqual(1_000);
});
