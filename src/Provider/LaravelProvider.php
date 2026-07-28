<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Provider;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use SimpleAsFuck\PerformanceLog\Listener\ConsoleListener;
use SimpleAsFuck\PerformanceLog\Listener\DatabaseListener;
use SimpleAsFuck\PerformanceLog\Listener\HttpListener;
use SimpleAsFuck\PerformanceLog\Listener\QueueListener;
use SimpleAsFuck\PerformanceLog\Service\LaravelPerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;
use SimpleAsFuck\PerformanceLog\Service\Stopwatch;

class LaravelProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PerformanceLogConfig::class, LaravelPerformanceLogConfig::class);
        $this->app->singleton(Stopwatch::class, fn () => new Stopwatch(
            $this->makePerformanceLogger(),
        ));

        $this->app->singleton(ConsoleListener::class, fn () => new ConsoleListener(
            $this->makePerformanceLogger(),
            $this->makePerformanceLogConfig(),
            $this->makeStopwatch(),
        ));
        $this->app->singleton(DatabaseListener::class, fn () => new DatabaseListener(
            $this->makePerformanceLogger(),
            $this->makePerformanceLogConfig(),
            $this->makeStopwatch(),
        ));
        $this->app->singleton(HttpListener::class, fn () => new HttpListener(
            $this->makePerformanceLogger(),
            $this->makePerformanceLogConfig(),
            $this->makeStopwatch(),
        ));
        $this->app->singleton(QueueListener::class, fn () => new QueueListener(
            $this->makePerformanceLogger(),
            $this->makePerformanceLogConfig(),
            $this->makeStopwatch(),
        ));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/laravel.php' => $this->app->configPath('performance_log.php'),
        ], 'performance-log-config');

        $this->app->make('events');
        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make(Dispatcher::class);

        /** @var ConsoleListener $consoleListener */
        $consoleListener = $this->app->make(ConsoleListener::class);
        $dispatcher->listen(CommandStarting::class, static fn (CommandStarting $command) => $consoleListener->onCommandStart($command->command));
        $dispatcher->listen(CommandFinished::class, static fn (CommandFinished $command) => $consoleListener->onCommandFinish($command->command));

        /** @var DatabaseListener $databaseListener */
        $databaseListener = $this->app->make(DatabaseListener::class);
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality time for now return float|null */
        $dispatcher->listen(QueryExecuted::class, static fn (QueryExecuted $query) => $databaseListener->onSqlQuery($query->sql, $query->time ?? 0.0, $query->connectionName));
        $dispatcher->listen(TransactionBeginning::class, static fn (TransactionBeginning $transaction) => $databaseListener->onTransactionStart($transaction->connection->transactionLevel(), $transaction->connectionName));
        $dispatcher->listen(TransactionRolledBack::class, static fn (TransactionRolledBack $transaction) => $databaseListener->onTransactionFinnish($transaction->connection->transactionLevel(), $transaction->connectionName));
        $dispatcher->listen(TransactionCommitted::class, static fn (TransactionCommitted $transaction) => $databaseListener->onTransactionFinnish($transaction->connection->transactionLevel(), $transaction->connectionName));

        $queueListener = $this->app->make(QueueListener::class);
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality getJobId for now return int|string */
        $dispatcher->listen(JobProcessing::class, static fn (JobProcessing $job) => $queueListener->onJobStart($job->job->resolveName() . '-' . ((string) $job->job->getJobId())));
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality getJobId for now return int|string */
        $dispatcher->listen(JobFailed::class, static fn (JobFailed $job) => $queueListener->onJobFinish($job->job->resolveName(), $job->job->resolveName() . '-' . ((string) $job->job->getJobId())));
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality getJobId for now return int|string */
        $dispatcher->listen(JobProcessed::class, static fn (JobProcessed $job) => $queueListener->onJobFinish($job->job->resolveName(), $job->job->resolveName() . '-' . ((string) $job->job->getJobId())));
    }

    private function makePerformanceLogger(): LoggerInterface
    {
        /** @var Repository $config */
        $config = $this->app->make(Repository::class);
        /** @var LogManager $logManager */
        $logManager = $this->app->make(LogManager::class);

        $performanceLogChanel = $config->get('performance_log.log_channel');
        if (! is_string($performanceLogChanel)) {
            $performanceLogChanel = null;
        }
        return $logManager->channel($performanceLogChanel);
    }

    private function makePerformanceLogConfig(): PerformanceLogConfig
    {
        /** @var PerformanceLogConfig */
        return $this->app->make(PerformanceLogConfig::class);
    }

    private function makeStopwatch(): Stopwatch
    {
        /** @var Stopwatch */
        return $this->app->make(Stopwatch::class);
    }
}
