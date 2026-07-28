<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Service;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;

class Stopwatch
{
    /**
     * @todo 0.7 make $logger not null
     */
    public function __construct(
        private readonly ?LoggerInterface $logger = null,
    ){
    }

    public function start(Measurement $measurement, ?string $prefix = null): void
    {
        $measurement->set($prefix, \microtime(true));
    }

    public function finishMilliseconds(Measurement $measurement, ?string $prefix = null): float
    {
        return $this->finishSeconds($measurement, $prefix) * 1000;
    }

    public function finishSeconds(Measurement $measurement, ?string $prefix = null): float
    {
        $startTime = $measurement->pop($prefix);
        if ($startTime === null) {
            $this->logger?->warning('Measurement with prefix: "'.$prefix.'" not started!', [
                'running_measurements' => $measurement->dump(),
            ]);
            return 0;
        }

        return \microtime(true) - $startTime;
    }
}
