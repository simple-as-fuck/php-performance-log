<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Data;

final class TemporaryThreshold
{
    public function __construct(
        private ?float $value,
        private readonly ?float $original,
    ) {
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    public function value(): ?float
    {
        return $this->value;
    }

    public function isRestored(): bool
    {
        return $this->value === $this->original;
    }

    public function restore(): void
    {
        $this->value = $this->original;
    }

    public function __destruct()
    {
        $this->restore();
    }
}
