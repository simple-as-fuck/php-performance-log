<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\LaravelPerformanceLog\Model\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\LaravelPerformanceLog\Service\Stopwatch;

class ConsoleListener
{
    private readonly Measurement $measurement;

    public function __construct(
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly LoggerInterface $logger,
        private readonly Stopwatch $stopwatch
    ) {
        $this->measurement = new Measurement();
    }

    public function onCommandStart(string $commandName): void
    {
        $this->performanceLogConfig->restoreSlowCommandThreshold();
        $this->measurement->start($commandName);
    }

    public function onCommandFinish(string $commandName): void
    {
        $threshold = $this->performanceLogConfig->getSlowCommandThreshold();

        $this->performanceLogConfig->restoreSlowCommandThreshold();
        if ($threshold === null) {
            return;
        }

        if ($threshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $time = $this->stopwatch->checkPrefix($this->measurement, $threshold * 1000, $commandName);
            $this->logger->debug('Console command time: '.($time / 1000).'s name: "'.$commandName.'" pid: '.\getmypid());
            return;
        }

        $this->stopwatch->checkPrefix($this->measurement, $threshold * 1000, $commandName, static fn (float $time) => $this->logger->warning('Console command is too slow time: '.($time / 1000).'s name: "'.$commandName.'" threshold: '.$threshold.'s pid: '.\getmypid()));
    }
}
