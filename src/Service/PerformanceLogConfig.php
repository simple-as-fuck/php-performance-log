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
    private ?TemporaryThreshold $temporaryServerRequestThreshold = null;
    /** @var \WeakReference<TemporaryThreshold>|null */
    private ?\WeakReference $temporaryClientRequestThreshold = null;
    private ?TemporaryThreshold $temporaryCommandThreshold = null;
    private ?TemporaryThreshold $temporaryJobThreshold = null;

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowSqlQueryThreshold(): ?float
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporarySqlQueryThreshold);
        if ($temporaryThreshold !== null) {
            return self::checkDebugThreshold($temporaryThreshold->value());
        }

        return self::checkDebugThreshold($this->getConfigSlowSqlQueryThreshold());
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
            return self::checkDebugThreshold($temporaryThreshold->value());
        }

        return self::checkDebugThreshold($this->getConfigSlowDbTransactionThreshold());
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
    final public function getSlowClientRequestThreshold(): ?float
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporaryClientRequestThreshold);
        if ($temporaryThreshold !== null) {
            return self::checkDebugThreshold($temporaryThreshold->value());
        }

        return self::checkDebugThreshold($this->getConfigSlowClientRequestThreshold());
    }

    /**
     * @param float|null $threshold value in milliseconds
     */
    final public function setSlowClientRequestThreshold(?float $threshold): TemporaryThreshold
    {
        $temporaryThreshold = self::getTemporaryThreshold($this->temporaryClientRequestThreshold);
        if ($temporaryThreshold !== null) {
            return new TemporaryThreshold($threshold, $temporaryThreshold->value());
        }

        $temporaryThreshold = new TemporaryThreshold($threshold, $this->getSlowClientRequestThreshold());
        $this->temporaryClientRequestThreshold = \WeakReference::create($temporaryThreshold);

        return $temporaryThreshold;
    }

    /**
     * @deprecated use $this->getSlowServerRequestThreshold
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowRequestThreshold(): ?float
    {
        return $this->getSlowServerRequestThreshold();
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    final public function getSlowServerRequestThreshold(): ?float
    {
        if ($this->temporaryServerRequestThreshold !== null) {
            return self::checkDebugThreshold($this->temporaryServerRequestThreshold->value());
        }

        return self::checkDebugThreshold($this->getConfigSlowServerRequestThreshold());
    }

    /**
     * @deprecated use $this->setSlowServerRequestThreshold
     * @param float|null $threshold value in milliseconds
     */
    final public function setSlowRequestThreshold(?float $threshold): void
    {
        $this->setSlowServerRequestThreshold($threshold);
    }

    /**
     * @deprecated use $this->restoreSlowServerRequestThreshold
     */
    final public function restoreSlowRequestThreshold(): void
    {
        $this->restoreSlowServerRequestThreshold();
    }

    /**
     * @param float|null $threshold value in milliseconds
     */
    final public function setSlowServerRequestThreshold(?float $threshold): void
    {
        $this->temporaryServerRequestThreshold = new TemporaryThreshold($threshold, null);
    }

    final public function restoreSlowServerRequestThreshold(): void
    {
        $this->temporaryServerRequestThreshold = null;
    }

    /**
     * @return float|null threshold value in seconds
     */
    final public function getSlowCommandThreshold(): ?float
    {
        if ($this->temporaryCommandThreshold !== null) {
            return self::checkDebugThreshold($this->temporaryCommandThreshold->value());
        }

        return self::checkDebugThreshold($this->getConfigSlowCommandThreshold());
    }

    /**
     * @param float|null $threshold threshold value in seconds
     */
    final public function setSlowCommandThreshold(?float $threshold): void
    {
        $this->temporaryCommandThreshold = new TemporaryThreshold($threshold, null);
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

    /**
     * @deprecated will be removed
     */
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
    protected function getConfigSlowClientRequestThreshold(): ?float
    {
        return 700;
    }

    /**
     * @deprecated use $this->getConfigSlowServerRequestThreshold
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowRequestThreshold(): ?float
    {
        return $this->getConfigSlowServerRequestThreshold();
    }

    /**
     * @return float|null threshold value in milliseconds
     */
    protected function getConfigSlowServerRequestThreshold(): ?float
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

    /**
     * @deprecated will be removed
     */
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
