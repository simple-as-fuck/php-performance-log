<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Listener;

use Psr\Log\LoggerInterface;
use SimpleAsFuck\LaravelPerformanceLog\Model\Measurement;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\LaravelPerformanceLog\Service\Stopwatch;

class DatabaseListener
{
    private readonly Measurement $transactionMeasurement;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Stopwatch $stopwatch,
        private readonly PerformanceLogConfig $performanceLogConfig
    ) {
        $this->transactionMeasurement = new Measurement();
    }

    public function onSqlQuery(string $sql, float $time, ?string $connectionName): void
    {
        $queryThreshold = $this->performanceLogConfig->getSlowSqlQueryThreshold();
        if ($queryThreshold === null) {
            return;
        }

        if ($queryThreshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
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

        if ($transactionThreshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $this->logger->debug('Database transaction begin connection: "'.$connectionName.'" pid: '.\getmypid());
        }

        $this->stopwatch->start($this->transactionMeasurement, $connectionName);
    }

    public function onTransactionFinnish(int $transactionLevel, ?string $connectionName): void
    {
        if ($transactionLevel !== 0) {
            return;
        }

        $transactionThreshold = $this->performanceLogConfig->getSlowDbTransactionThreshold();
        if ($transactionThreshold === null) {
            return;
        }

        if (! $this->transactionMeasurement->running($connectionName)) {
            $this->logger->error('Database transaction measurement not running database connection: "'.$connectionName.'" pid: '.\getmypid().', check if begin transaction is called before commit/rollback!');
            return;
        }

        if ($transactionThreshold === 0.0 && $this->performanceLogConfig->isDebugEnabled()) {
            $time = $this->stopwatch->checkPrefix($this->transactionMeasurement, $transactionThreshold, $connectionName);
            $this->logger->debug('Database transaction time: '.$time.'ms connection: "'.$connectionName.'" pid: '.\getmypid());
            return;
        }

        $this->stopwatch->checkPrefix(
            $this->transactionMeasurement,
            $transactionThreshold,
            $connectionName,
            fn (float $time) => $this->logger->warning('Database transaction is too slow: '.$time.'ms threshold: '.$transactionThreshold. 'ms connection: "'.$connectionName.'" pid: '.\getmypid())
        );
    }
}
