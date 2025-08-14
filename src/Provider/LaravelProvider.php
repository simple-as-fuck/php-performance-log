<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Provider;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\ServiceProvider;
use SimpleAsFuck\PerformanceLog\Listener\ConsoleListener;
use SimpleAsFuck\PerformanceLog\Listener\DatabaseListener;
use SimpleAsFuck\PerformanceLog\Listener\HttpListener;
use SimpleAsFuck\PerformanceLog\Listener\QueueListener;
use SimpleAsFuck\PerformanceLog\Service\PerformanceLogConfig;

class LaravelProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PerformanceLogConfig::class);
        $this->app->singleton(DatabaseListener::class);
        $this->app->singleton(ConsoleListener::class);
        $this->app->singleton(QueueListener::class);
        $this->app->singleton(HttpListener::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/laravel.php', 'performance_log');
        $this->publishes([
            __DIR__.'/../../config/laravel.php' => $this->app->configPath('performance_log.php'),
        ], 'performance-log-config');

        $this->app->make('events');

        /** @var DatabaseManager $databaseManager */
        $databaseManager = $this->app->make(DatabaseManager::class);
        $databaseDispatcher = $databaseManager->connection()->getEventDispatcher();

        $databaseListener = $this->app->make(DatabaseListener::class);
        $databaseDispatcher->listen(QueryExecuted::class, static fn (QueryExecuted $query) => $databaseListener->onSqlQuery($query->sql, $query->time ?? 0.0, $query->connectionName));
        $databaseDispatcher->listen(TransactionBeginning::class, static fn (TransactionBeginning $transaction) => $databaseListener->onTransactionStart($transaction->connection->transactionLevel(), $transaction->connectionName));
        $databaseDispatcher->listen(TransactionRolledBack::class, static fn (TransactionRolledBack $transaction) => $databaseListener->onTransactionFinnish($transaction->connection->transactionLevel(), $transaction->connectionName));
        $databaseDispatcher->listen(TransactionCommitted::class, static fn (TransactionCommitted $transaction) => $databaseListener->onTransactionFinnish($transaction->connection->transactionLevel(), $transaction->connectionName));

        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->app->make(Dispatcher::class);

        $consoleListener = $this->app->make(ConsoleListener::class);
        $dispatcher->listen(CommandStarting::class, static fn (CommandStarting $command) => $consoleListener->onCommandStart($command->command));
        $dispatcher->listen(CommandFinished::class, static fn (CommandFinished $command) => $consoleListener->onCommandFinish($command->command));

        $queueListener = $this->app->make(QueueListener::class);
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality getJobId for now return int|string */
        $dispatcher->listen(JobProcessing::class, static fn (JobProcessing $job) => $queueListener->onJobStart((string) $job->job->getJobId()));
        /** @phpstan-ignore-next-line laravel developers are imbeciles in reality getJobId for now return int|string */
        $dispatcher->listen(JobProcessed::class, static fn (JobProcessed $job) => $queueListener->onJobFinish($job->job->resolveName(), (string) $job->job->getJobId()));
    }
}
