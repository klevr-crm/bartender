<?php

declare(strict_types=1);

namespace App\Personas;

use Closure;

final class HumanTimingService
{
    /** @var Closure(): float */
    private readonly Closure $randomFloatGenerator;

    public function __construct(?Closure $randomFloatGenerator = null)
    {
        $this->randomFloatGenerator = $randomFloatGenerator ?? fn (): float => mt_rand() / mt_getrandmax();
    }

    /**
     * Calcula o delay (ms) até o cliente responder, baseado no tamanho
     * do texto que ele acabou de receber do agente.
     */
    public function computeDelayMs(string $agentReplyText): int
    {
        $mode = config('bartender.timing.mode', 'realistic');

        if ($mode === 'fast') {
            return $this->fastDelay();
        }

        return $this->realisticDelay($agentReplyText);
    }

    private function realisticDelay(string $text): int
    {
        $medianMs = (int) config('bartender.timing.realistic.median_ms', 75_000);
        $sigma = (float) config('bartender.timing.realistic.sigma', 0.6);
        $maxMs = (int) config('bartender.timing.realistic.max_ms', 480_000);
        $readMsPerChar = (int) config('bartender.timing.realistic.read_ms_per_char', 15);
        $readCapMs = (int) config('bartender.timing.realistic.read_cap_ms', 20_000);

        $mu = log($medianMs);

        $u1 = ($this->randomFloatGenerator)();
        $u2 = ($this->randomFloatGenerator)();

        // Evita log(0)
        if ($u1 <= 0.0) {
            $u1 = 1e-10;
        }

        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        $baseDelay = (int) round(exp($mu + $sigma * $z));
        $baseDelay = min($baseDelay, $maxMs);

        $readTime = min(strlen($text) * $readMsPerChar, $readCapMs);

        $total = $baseDelay + $readTime;

        return max($total, 1_000);
    }

    private function fastDelay(): int
    {
        $minMs = (int) config('bartender.timing.fast.min_ms', 1_000);
        $maxMs = (int) config('bartender.timing.fast.max_ms', 5_000);

        $random = ($this->randomFloatGenerator)();

        return (int) round($minMs + $random * ($maxMs - $minMs));
    }
}
