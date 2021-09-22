<?php

namespace Phlib\DbHelper;

use Phlib\DbHelper\Exception\RuntimeException;
use Phlib\DbHelper\Exception\InvalidArgumentException;
use Phlib\Db\Adapter;
use Phlib\Db\AdapterInterface;

/**
 * @package Phlib\DbHelper
 * @licence LGPL-3.0
 */
class Replication
{
    const MAX_HISTORY = 30;

    /**
     * @var AdapterInterface
     */
    protected $master;

    /**
     * @var AdapterInterface[]
     */
    protected $slaves;

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
     * @param AdapterInterface $master
     * @param AdapterInterface[] $slaves
     * @param Replication\StorageInterface $storage
     */
    public function __construct(AdapterInterface $master, array $slaves, Replication\StorageInterface $storage)
    {
        $this->master  = $master;
        $this->slaves  = $slaves;
        $this->host    = $master->getConfig()['host'];
        $this->storage = $storage;

        if (empty($slaves)) {
            throw new InvalidArgumentException('Missing required list of slaves.');
        }
        foreach ($slaves as $slave) {
            if (!$slave instanceof AdapterInterface) {
                throw new InvalidArgumentException('Specified slave is not an expected adapter.');
            }
        }
    }

    /**
     * @param array $config
     * @return self
     */
    public static function createFromConfig(array $config)
    {
        $master = new Adapter([
            'host'     => $config['host'],
            'username' => $config['username'],
            'password' => $config['password']
        ]);
        $slaves = [];
        foreach ($config['slaves'] as $slave) {
            $slaves[] = new Adapter($slave);
        }
        $storageClass = $config['storage']['class'];
        if (!class_exists($storageClass)) {
            throw new InvalidArgumentException("Specified storage class '$storageClass' could not be found.");
        }
        if (!method_exists($storageClass, 'createFromConfig')) {
            throw new InvalidArgumentException(
                "Storage class '$storageClass' is missing required method 'createFromConfig'."
            );
        }
        $storage = call_user_func_array([$storageClass, 'createFromConfig'], $config['storage']['args']);
        return new static($master, $slaves, $storage);
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
        foreach ($this->slaves as $slave) {
            $status    = $this->fetchStatus($slave);
            $maxBehind = max($status['Seconds_Behind_Master'], $maxBehind);
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
     * @param AdapterInterface $slave
     * @return array
     */
    public function fetchStatus(AdapterInterface $slave)
    {
        $status = $slave->query('SHOW SLAVE STATUS')
            ->fetch(\PDO::FETCH_ASSOC);
        if (
            !is_array($status) ||
            !array_key_exists('Seconds_Behind_Master', $status) ||
            is_null($status['Seconds_Behind_Master'])
        ) {
            throw new RuntimeException('Seconds_Behind_Master is not a valid value');
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
