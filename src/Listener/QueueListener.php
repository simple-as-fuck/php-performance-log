<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

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

        $time = $this->stopwatch->finishMilliseconds($this->measurement, $jobId);
        if ($threshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $this->logger->debug('Queue job time: ' . $time . 'ms job name: "' . $jobName . '" pid: ' . \getmypid());
            return;
        }
        if ($time >= $threshold) {
            $this->logger->warning('Queue job is too slow: ' . $time . 'ms job name: "' . $jobName . '" threshold: ' . $threshold . 'ms pid: ' . \getmypid());
        }
    }
}
