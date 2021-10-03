<?php

namespace Phlib\DbHelperReplication;

use Phlib\Db\AdapterInterface;
use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\DbHelperReplication\Exception\RuntimeException;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class Replication
{
    public const MAX_HISTORY = 30;

    private const REPLICA_LAG_KEY = 'Seconds_Behind_Master';

    /**
     * @var AdapterInterface
     */
    protected $primary;

    /**
     * @var AdapterInterface[]
     */
    protected $replicas;

    /**
     * @var Replication\StorageInterface
     */
    protected $storage;

    /**
     * @var int
     */
    protected $weighting = 100;

    /**
     * @var int
     */
    protected $maxSleep = 1000; // ms

    /**
     * @var int
     */
    protected $loadValue = 0;

    /**
     * @var int
     */
    protected $loadUpdated = 0;

    /**
     * @var int
     */
    protected $updateInterval = 1;

    /**
     * @param AdapterInterface[] $replicas
     */
    public function __construct(AdapterInterface $primary, array $replicas, Replication\StorageInterface $storage)
    {
        $this->primary = $primary;
        $this->replicas = $replicas;
        $this->host = $primary->getConfig()['host'];
        $this->storage = $storage;

        if (empty($replicas)) {
            throw new InvalidArgumentException('Missing required list of replicas.');
        }
        foreach ($replicas as $replica) {
            if (!$replica instanceof AdapterInterface) {
                throw new InvalidArgumentException('Specified replica is not an expected adapter.');
            }
        }
    }

    public function getStorage(): Replication\StorageInterface
    {
        return $this->storage;
    }

    public function getWeighting(): int
    {
        return $this->weighting;
    }

    public function setWeighting(int $weighting): self
    {
        $this->weighting = $weighting;
        return $this;
    }

    /**
     * @return int milliseconds
     */
    public function getMaximumSleep(): int
    {
        return $this->maxSleep;
    }

    public function setMaximumSleep(int $milliseconds): self
    {
        $this->maxSleep = $milliseconds;
        return $this;
    }

    public function monitor(): self
    {
        $maxBehind = 0;
        foreach ($this->replicas as $replica) {
            $status = $this->fetchStatus($replica);
            $maxBehind = max($status[self::REPLICA_LAG_KEY], $maxBehind);
        }

        // append data point to the history for this host
        $history = $this->storage->getHistory($this->host);
        $history[] = $maxBehind;
        if (count($history) > self::MAX_HISTORY) {
            // trim the history
            array_shift($history);
        }

        // calculate the average
        $avgBehind = 0;
        $historyLength = count($history);
        if ($historyLength > 0) {
            $avgBehind = ceil(array_sum($history) / $historyLength);
        }

        $this->storage->setSecondsBehind($this->host, $avgBehind);
        $this->storage->setHistory($this->host, $history);

        return $this;
    }

    public function fetchStatus(AdapterInterface $replica): array
    {
        $status = $replica->query('SHOW SLAVE STATUS')
            ->fetch(\PDO::FETCH_ASSOC);
        if (
            !is_array($status) ||
            !array_key_exists(self::REPLICA_LAG_KEY, $status) ||
            $status[self::REPLICA_LAG_KEY] === null
        ) {
            throw new RuntimeException(self::REPLICA_LAG_KEY . ' is not a valid value');
        }
        return $status;
    }

    /**
     * Updates the stored load value if out-of-date, and sleeps for the required
     * time if throttling is enabled.
     */
    public function throttle(): self
    {
        $currentInterval = time() - $this->loadUpdated;
        if ($currentInterval > $this->updateInterval) {
            $this->loadValue = $this->storage->getSecondsBehind($this->host);
            $this->loadUpdated = time();
        }
        $this->sleep();

        return $this;
    }

    protected function sleep(): self
    {
        $alteredLoad = pow($this->loadValue, 5.2) / 100;
        $weighting = $this->weighting / 100;
        $sleepMs = min($alteredLoad * $weighting, $this->maxSleep);

        usleep(floor($sleepMs * 1000));

        return $this;
    }
}
