<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

class HttpListener
{
    private readonly Measurement $measurement;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Stopwatch $stopwatch,
        private readonly PerformanceLogConfig $performanceLogConfig,
    ) {
        $this->measurement = new Measurement();
    }

    public function onRequestStart(): void
    {
        $this->performanceLogConfig->restoreSlowRequestThreshold();
        $this->stopwatch->start($this->measurement);
    }

    public function onRequestFinish(string $requestMethod, string $requestUrl): void
    {
        $threshold = $this->performanceLogConfig->getSlowRequestThreshold();
        $this->performanceLogConfig->restoreSlowRequestThreshold();

        if ($threshold === null) {
            return;
        }

        $time = $this->stopwatch->finishMilliseconds($this->measurement);
        if ($threshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $this->logger->debug('Http request time: '.$time.'ms method: "'.$requestMethod.'" url: "'.$requestUrl.'" pid: '.\getmypid());
            return;
        }
        if ($time >= $threshold) {
            $this->logger->warning('Http request is too slow: '.$time.'ms method: "'.$requestMethod.'" url: "'.$requestUrl.'" threshold: '.$threshold.'ms pid: '.\getmypid());
        }
    }
}
