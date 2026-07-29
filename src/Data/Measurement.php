<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Data;

final class Measurement
{
    /** @var array<string, float> */
    private array $startsAt = [];
    private ?float $nullStartAt = null;

    /**
     * @deprecated use set
     * @param float $time in seconds
     */
    public function start(?string $prefix, float $time): void
    {
        $this->set($prefix, $time);
    }

    /**
     * @param float $time in seconds
     */
    public function set(?string $prefix, float $time): void
    {
        if ($prefix === null) {
            $this->nullStartAt = $time;
        } else {
            $this->startsAt[$prefix] = $time;
        }
    }

    /**
     * @deprecated use get
     * @return float|null time in seconds
     */
    public function startAt(?string $prefix): ?float
    {
        return $this->get($prefix);
    }

    /**
     * @return float|null time in seconds
     */
    public function get(?string $prefix): ?float
    {
        if ($prefix === null) {
            return $this->nullStartAt;
        }

        return $this->startsAt[$prefix] ?? null;
    }

    /**
     * @deprecated use pop
     * @return float time in seconds
     */
    public function finnish(?string $prefix): float
    {
        $time = $this->pop($prefix);
        if ($time === null) {
            throw new \LogicException('Measurement with prefix: "'.$prefix.'" not started!');
        }

        return $time;
    }

    /**
     * @return float|null time in seconds
     */
    public function pop(?string $prefix): ?float
    {
        $time = $this->get($prefix);
        if ($prefix === null) {
            $this->nullStartAt = null;
        } else {
            unset($this->startsAt[$prefix]);
        }

        return $time;
    }

    /**
     * @return array{null: float|null, named: array<string, float>}
     */
    public function dump(): array
    {
        return [
            'null' => $this->nullStartAt,
            'named' => $this->startsAt,
        ];
    }
}
