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
    private bool $explainingQuery = false;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PerformanceLogConfig $performanceLogConfig,
        private readonly Stopwatch $stopwatch,
    ) {
        $this->transactionMeasurement = new Measurement();
    }

    /**
     * @param array<mixed> $bindings
     * @param float $time in milliseconds
     */
    public function onSqlQuery(string $sql, #[\SensitiveParameter] array $bindings, float $time, ?string $connectionName, \PDO $pdo): void
    {
        $queryThreshold = $this->performanceLogConfig->getSlowSqlQueryThreshold();
        $queryDebugThreshold = $this->performanceLogConfig->getSlowSqlQueryDebugThreshold();

        if ($queryThreshold !== null && $time >= $queryThreshold && $queryThreshold !== 0.0) {
            $this->logger->warning('Database query is too slow: '.$time.'ms sql: "'.$sql.'" threshold: '.$queryThreshold. 'ms connection: "'.$connectionName.'" pid: '.\getmypid(), [
                'explain' => $this->explainSqlQuery($sql, $bindings, $pdo),
            ]);
            return;
        }

        if (($queryDebugThreshold !== null && $time >= $queryDebugThreshold) || $queryThreshold === 0.0) {
            $this->logger->debug('Database query time: '.$time.'ms sql: "'.$sql.'" connection: "'.$connectionName.'" pid: '.\getmypid(), [
                'explain' => $this->explainSqlQuery($sql, $bindings, $pdo),
            ]);
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

    /**
     * @param array<mixed> $bindings
     */
    private function explainSqlQuery(string $sql, #[\SensitiveParameter] array $bindings, \PDO $pdo): string|array
    {
        if ($this->explainingQuery) {
            return 'Recursion protection';
        }

        $this->explainingQuery = true;
        try {
            $driverName = @$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($driverName === null) {
                return 'Detect drive failed';
            }

            $explainSqlPrefix = match ($driverName) {
                // https://dev.mysql.com/doc/refman/8.4/en/explain.html
                // https://mariadb.com/docs/server/reference/sql-statements/administrative-sql-statements/analyze-and-explain-statements/explain
                'mysql' => 'explain ',
                // https://sqlite.org/eqp.html
                'sqlite' => 'explain query plan ',
                // https://www.postgresql.org/docs/current/sql-explain.html
                'pgsql' => 'explain (generic_plan) ',
                default => null,
            };
            if ($explainSqlPrefix !== null) {
                return 'Unsupported';
            }

            $statement = @$pdo->prepare($explainSqlPrefix . $sql);
            if ($statement === false) {
                return 'Prepare SQL failed';
            }

            $bindingPosition = 1;
            foreach ($bindings as $key => $binding) {
                if ($driverName == 'pgsql') {
                    $binding = '$' . $bindingPosition;
                }

                if (! @$statement->bindValue(\is_string($key) ? $key : $key + 1, $binding, \is_string($binding) ? \PDO::PARAM_STR : \PDO::PARAM_INT)) {
                    return 'Bind value failed';
                }

                $bindingPosition++;
            }

            if (! @$statement->execute()) {
                return 'Execute SQL failed';
            }

            return @$statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Throwable $exception) {
            return $exception::class;
        } finally {
            $this->explainingQuery = false;
        }
    }
}
