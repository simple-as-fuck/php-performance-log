<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Data\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

class DatabaseListener
{
    private readonly Measurement $transactionMeasurement;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly Stopwatch $stopwatch,
    ) {
        $this->transactionMeasurement = new Measurement();
    }

    /**
     * @param float $time in milliseconds
     */
    public function onSqlQuery(string $sql, float $time, ?string $connectionName): void
    {
        $queryThreshold = $this->performanceLogConfig->getSlowSqlQueryThreshold();

        if ($queryThreshold === null) {
            return;
        }
        if ($queryThreshold === 0.0) {
            $this->logger->debug('Database query time: '.$time.'ms sql: "'.$sql.'" connection: "'.$connectionName.'" pid: '.\getmypid());
            return;
        }
        if ($time >= $queryThreshold) {
            $this->logger->warning('Database query is too slow: '.$time.'ms sql: "'.$sql.'" threshold: '.$queryThreshold. 'ms connection: "'.$connectionName.'" pid: '.\getmypid());
        }
    }

    public function onTransactionStart(int $transactionLevel, ?string $connectionName): void
    {
        if ($transactionLevel !== 1) {
            return;
        }

        $transactionThreshold = $this->performanceLogConfig->getSlowDbTransactionThreshold();
        if ($transactionThreshold === null) {
            return;
        }
        if ($transactionThreshold === 0.0) {
            $this->logger->debug('Database transaction begin connection: "'.$connectionName.'" pid: '.\getmypid());
        }

        $this->stopwatch->start($this->transactionMeasurement, $connectionName);
    }

    public function onTransactionFinnish(int $transactionLevel, ?string $connectionName): void
    {
        if ($transactionLevel !== 0) {
            return;
        }

        $threshold = $this->performanceLogConfig->getSlowDbTransactionThreshold();
        if ($threshold === null) {
            return;
        }

        if ($this->transactionMeasurement->get($connectionName) === null) {
            $this->logger->error('Database transaction measurement not running database connection: "'.$connectionName.'" pid: '.\getmypid().', check if begin transaction is called before commit/rollback!');
            return;
        }

        $time = $this->stopwatch->finishMilliseconds($this->transactionMeasurement, $connectionName);
        if ($threshold === 0.0) {
            $this->logger->debug('Database transaction time: '.$time.'ms connection: "'.$connectionName.'" pid: '.\getmypid());
            return;
        }
        if ($time >= $threshold) {
            $this->logger->warning('Database transaction is too slow: '.$time.'ms threshold: '.$threshold. 'ms connection: "'.$connectionName.'" pid: '.\getmypid());
        }
    }
}
