<?php

namespace Phlib\DbHelperReplication;

use Phlib\DbHelperReplication\Exception\RuntimeException;
use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\Db\AdapterInterface;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class Replication
{
    const MAX_HISTORY = 30;

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
     * @var Replication\StorageInterface $storage
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
     * Constructor
     *
     * @param AdapterInterface $primary
     * @param AdapterInterface[] $replicas
     * @param Replication\StorageInterface $storage
     */
    public function __construct(AdapterInterface $primary, array $replicas, Replication\StorageInterface $storage)
    {
        $this->primary  = $primary;
        $this->replicas = $replicas;
        $this->host    = $primary->getConfig()['host'];
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

    /**
     * @return Replication\StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Get throttle weighting
     *
     * @return int
     */
    public function getWeighting()
    {
        return $this->weighting;
    }

    /**
     * Set throttle weighting
     *
     * @param int $weighting
     * @return $this
     */
    public function setWeighting($weighting)
    {
        $this->weighting = (int)$weighting;
        return $this;
    }

    /**
     * Get the maximum number of milliseconds the throttle can sleep for.
     *
     * @return int
     */
    public function getMaximumSleep()
    {
        return $this->maxSleep;
    }

    /**
     * Set the maximum number of milliseconds the throttle can sleep for.
     *
     * @param int $milliseconds
     * @return $this
     */
    public function setMaximumSleep($milliseconds)
    {
        $this->maxSleep = (int)$milliseconds;
        return $this;
    }

    /**
     * @return $this
     */
    public function monitor()
    {
        $maxBehind = 0;
        foreach ($this->replicas as $replica) {
            $status    = $this->fetchStatus($replica);
            $maxBehind = max($status[self::REPLICA_LAG_KEY], $maxBehind);
        }

        // append data point to the history for this host
        $history   = $this->storage->getHistory($this->host);
        $history[] = $maxBehind;
        if (count($history) > self::MAX_HISTORY) {
            // trim the history
            array_shift($history);
        }

        // calculate the average
        $avgBehind     = 0;
        $historyLength = count($history);
        if ($historyLength > 0) {
            $avgBehind = ceil(array_sum($history) / $historyLength);
        }

        $this->storage->setSecondsBehind($this->host, $avgBehind);
        $this->storage->setHistory($this->host, $history);

        return $this;
    }

    /**
     * @param AdapterInterface $replica
     * @return array
     */
    public function fetchStatus(AdapterInterface $replica)
    {
        $status = $replica->query('SHOW SLAVE STATUS')
            ->fetch(\PDO::FETCH_ASSOC);
        if (
            !is_array($status) ||
            !array_key_exists(self::REPLICA_LAG_KEY, $status) ||
            is_null($status[self::REPLICA_LAG_KEY])
        ) {
            throw new RuntimeException(self::REPLICA_LAG_KEY . ' is not a valid value');
        }
        return $status;
    }

    /**
     * Throttle
     *
     * Updates the stored load value if out-of-date, and sleeps for the required
     * time if throttling is enabled.
     *
     * @return $this
     */
    public function throttle()
    {
        $currentInterval = time() - $this->loadUpdated;
        if ($currentInterval > $this->updateInterval) {
            $this->loadValue   = (int)$this->storage->getSecondsBehind($this->host);
            $this->loadUpdated = time();
        }
        $this->sleep();

        return $this;
    }

    /**
     * Sleep for stored sleep time
     *
     * @return $this
     */
    protected function sleep()
    {
        $alteredLoad = pow($this->loadValue, 5.2) / 100;
        $weighting   = $this->weighting / 100;
        $sleepMs     = min($alteredLoad * $weighting, $this->maxSleep);

        usleep(floor($sleepMs * 1000));

        return $this;
    }
}
