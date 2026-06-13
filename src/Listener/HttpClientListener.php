<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

class HttpClientListener
{
    private readonly Measurement $measurement;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly Stopwatch $stopwatch,
    ) {
        $this->measurement = new Measurement();
    }

    public function onRequestStart(RequestInterface $request): void
    {
        $this->stopwatch->start($this->measurement, (string) \spl_object_id($request));
    }

    public function onRequestFinish(RequestInterface $request): void
    {
        $threshold = $this->performanceLogConfig->getSlowClientRequestThreshold();
        $time = $this->stopwatch->finishMilliseconds($this->measurement, (string) \spl_object_id($request));

        if ($threshold === null) {
            return;
        }
        if ($threshold === 0.0) {
            $this->logger->debug('Http client request time: '.$time.'ms method: '.$request->getMethod().' url: "'.$request->getUri()->__toString().'" pid: '.\getmypid());
            return;
        }
        if ($time >= $threshold) {
            $this->logger->warning('Http client request is too slow: '.$time.'ms method: '.$request->getMethod().' url: "'.$request->getUri()->__toString().'" threshold: '.$threshold.'ms pid: '.\getmypid());
        }
    }
}
