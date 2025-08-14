<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Data;

final class Measurement
{
    /** @var array<string, float> */
    private array $startsAt = [];
    private ?float $nullStartAt = null;

    /**
     * @param float $time in seconds
     */
    public function start(?string $prefix, float $time): void
    {
        if ($prefix === null) {
            $this->nullStartAt = $time;
        }

        $this->startsAt[$prefix] = $time;
    }

    /**
     * @return float|null time in seconds
     */
    public function startAt(?string $prefix): ?float
    {
        if ($prefix === null) {
            return $this->nullStartAt;
        }

        return $this->startsAt[$prefix] ?? null;
    }

    /**
     * @return float time in seconds
     */
    public function finnish(?string $prefix): float
    {
        $time = $this->startAt($prefix);
        if ($time === null) {
            throw new \LogicException('Measurement with prefix: "'.$prefix.'" not started!');
        }

        if ($prefix === null) {
            $this->nullStartAt = null;
        } else {
            unset($this->startsAt[$prefix]);
        }

        return $time;
    }
}
