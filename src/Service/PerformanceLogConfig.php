<?php

declare(strict_types=1);

namespace SimpleAsFuck\PerformanceLog\Service;

use SimpleAsFuck\PerformanceLog\Data\TemporaryThreshold;

abstract class PerformanceLogConfig
{
    /** @var \WeakReference<TemporaryThreshold>|null */
    private ?\WeakReference $temporarySqlQueryThreshold = null;
    /** @var \WeakReference<TemporaryThreshold>|null */
    private ?\WeakReference $temporaryDbTransactionThreshold = null;
    private ?TemporaryThreshold $temporaryRequestThreshold = null;
    private ?TemporaryThreshold $temporaryCommandThreshold = null;
    private ?TemporaryThreshold $temporaryJobThreshold = null;

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowSqlQueryThreshold(): ?float
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporarySqlQueryThreshold);
        if ($temporaryThreshold !== null) {
            return $this->checkDebugThreshold($temporaryThreshold->value());
        }

        return $this->checkDebugThreshold($this->getConfigSlowSqlQueryThreshold());
    }

    /**
     * @param float|null $threshold threshold value in milliseconds
     */
    final public function setSlowSqlQueryThreshold(?float $threshold): TemporaryThreshold
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporarySqlQueryThreshold);
        if ($temporaryThreshold !== null) {
            return new TemporaryThreshold($threshold, $temporaryThreshold->value());
        }

        $temporaryThreshold = new TemporaryThreshold($threshold, $this->getSlowSqlQueryThreshold());
        $this->temporarySqlQueryThreshold = \WeakReference::create($temporaryThreshold);

        return $temporaryThreshold;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowDbTransactionThreshold(): ?float
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporaryDbTransactionThreshold);
        if ($temporaryThreshold !== null) {
            return $this->checkDebugThreshold($temporaryThreshold->value());
        }

        return $this->checkDebugThreshold($this->getConfigSlowDbTransactionThreshold());
    }

    /**
     * @param float|null $threshold threshold value in milliseconds
     */
    final public function setSlowDbTransactionThreshold(?float $threshold): TemporaryThreshold
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporaryDbTransactionThreshold);
        if ($temporaryThreshold !== null) {
            return new TemporaryThreshold($threshold, $temporaryThreshold->value());
        }

        $temporaryThreshold = new TemporaryThreshold($threshold, $this->getSlowDbTransactionThreshold());
        $this->temporaryDbTransactionThreshold = \WeakReference::create($temporaryThreshold);

        return $temporaryThreshold;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowRequestThreshold(): ?float
    {
        if ($this->temporaryRequestThreshold !== null) {
            return $this->checkDebugThreshold($this->temporaryRequestThreshold->value());
        }

        return $this->checkDebugThreshold($this->getConfigSlowRequestThreshold());
    }

    /**
     * @param float|null $threshold value in milliseconds
     */
    final public function setSlowRequestThreshold(?float $threshold): void
    {
        if ($this->temporaryRequestThreshold === null) {
            $this->temporaryRequestThreshold = new TemporaryThreshold($threshold, null);
        }
    }

    final public function restoreSlowRequestThreshold(): void
    {
        $this->temporaryRequestThreshold = null;
    }

    /**
     * @return float|null threshold value in seconds
     */
    final public function getSlowCommandThreshold(): ?float
    {
        if ($this->temporaryCommandThreshold !== null) {
            return $this->checkDebugThreshold($this->temporaryCommandThreshold->value());
        }

        return $this->checkDebugThreshold($this->getConfigSlowCommandThreshold());
    }

    /**
     * @param float|null $threshold threshold value in seconds
     */
    final public function setSlowCommandThreshold(?float $threshold): void
    {
        if ($this->temporaryCommandThreshold === null) {
            $this->temporaryCommandThreshold = new TemporaryThreshold($threshold, null);
        }
    }

    final public function restoreSlowCommandThreshold(): void
    {
        $this->temporaryCommandThreshold = null;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowJobThreshold(): ?float
    {
        if ($this->temporaryJobThreshold !== null) {
            return $this->checkDebugThreshold($this->temporaryJobThreshold->value());
        }

        return $this->checkDebugThreshold($this->getConfigSlowJobThreshold());
    }

    /**
     * @param float|null $threshold value in milliseconds
     */
    final public function setSlowJobThreshold(?float $threshold): void
    {
        $this->temporaryJobThreshold = new TemporaryThreshold($threshold, null);
    }

    final public function restoreSlowJobThreshold(): void
    {
        $this->temporaryJobThreshold = null;
    }

    protected function getConfigDebug(): bool
    {
        return false;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowSqlQueryThreshold(): ?float
    {
        return 50;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowDbTransactionThreshold(): ?float
    {
        return 300;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowRequestThreshold(): ?float
    {
        return 1000;
    }

    /**
     * @return float|null threshold value in seconds
     */
    protected function getConfigSlowCommandThreshold(): ?float
    {
        return null;
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowJobThreshold(): ?float
    {
        return null;
    }

    /**
     * @param \WeakReference<TemporaryThreshold>|null $weakReference
     */
    private static function getTemporaryThreshold(?\WeakReference &$weakReference): ?TemporaryThreshold
    {
        if ($weakReference === null) {
            return null;
        }

        $threshold = $weakReference->get();
        if ($threshold === null || $threshold->isRestored()) {
            $weakReference = null;
            return null;
        }

        return $threshold;
    }

    private function checkDebugThreshold(?float $threshold): ?float
    {
        if ($threshold === 0.0) {
            if ($this->getConfigDebug() === false) {
                return null;
            }
        }

        return $threshold;
    }
}
