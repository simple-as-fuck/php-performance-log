<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\LaravelPerformanceLog\Model\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\LaravelPerformanceLog\Service\Stopwatch;

class QueueListener
{
    private readonly Measurement $measurement;

    public function __construct(
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly Stopwatch $stopwatch,
        private readonly LoggerInterface $logger,
    ) {
        $this->measurement = new Measurement();
    }

    public function onJobStart(?string $jobId): void
    {
        $this->performanceLogConfig->restoreSlowJobThreshold();

        $this->stopwatch->start($this->measurement, $jobId);
    }

    public function onJobFinish(string $jobName, ?string $jobId): void
    {
        $threshold = $this->performanceLogConfig->getSlowJobThreshold();
        $this->performanceLogConfig->restoreSlowJobThreshold();
        if ($threshold === null) {
            return;
        }

        if ($threshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $time = $this->stopwatch->checkPrefix($this->measurement, $threshold, $jobId);
            $this->logger->debug('Queue job time: ' . $time . 'ms job name: "' . $jobName . '" pid: ' . \getmypid());
            return;
        }

        $this->stopwatch->checkPrefix(
            $this->measurement,
            $threshold,
            $jobId,
            static fn (float $time) => $this->logger->warning('Queue job is too slow: ' . $time . 'ms job name: "' . $jobName . '" threshold: ' . $threshold . 'ms pid: ' . \getmypid())
        );
    }
}
