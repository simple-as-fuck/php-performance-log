<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Service;

use SimpleAsFuck\PerformanceLog\Data\Measurement;

class Stopwatch
{
    public function start(Measurement $measurement, ?string $prefix = null): void
    {
        $measurement->start($prefix, \microtime(true));
    }

    public function finishMilliseconds(Measurement $measurement, ?string $prefix = null): float
    {
        return $this->finishSeconds($measurement, $prefix) * 1000;
    }

    public function finishSeconds(Measurement $measurement, ?string $prefix = null): float
    {
        return \microtime(true) - $measurement->finnish($prefix);
    }
}
