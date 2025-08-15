<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

class ConsoleListener
{
    private readonly Measurement $measurement;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly Stopwatch $stopwatch
    ) {
        $this->measurement = new Measurement();
    }

    public function onCommandStart(string $commandName): void
    {
        $this->performanceLogConfig->restoreSlowCommandThreshold();
        $this->stopwatch->start($this->measurement, $commandName);
    }

    public function onCommandFinish(string $commandName): void
    {
        $threshold = $this->performanceLogConfig->getSlowCommandThreshold();
        $time = $this->stopwatch->finishSeconds($this->measurement, $commandName);

        $this->performanceLogConfig->restoreSlowCommandThreshold();

        if ($threshold === null) {
            return;
        }
        if ($threshold === 0.0) {
            $this->logger->debug('Console command time: '.$time.'s name: "'.$commandName.'" pid: '.\getmypid());
            return;
        }
        if ($time >= $threshold) {
            $this->logger->warning('Console command is too slow time: '.$time.'s name: "'.$commandName.'" threshold: '.$threshold.'s pid: '.\getmypid());
        }
    }
}
