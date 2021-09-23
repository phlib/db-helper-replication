<?php

namespace Phlib\DbHelperReplication\Replication;

use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\DbHelperReplication\Exception\RuntimeException;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class Memcache implements StorageInterface
{
    /**
     * @param array $memcacheConfig
     * @return static
     */
    public static function createFromConfig(array $memcacheConfig)
    {
        $memcache = new \Memcached();

        if (!isset($memcacheConfig['host'])) {
            throw new InvalidArgumentException('Missing Memcache host');
        }

        $host = $memcacheConfig['host'];
        $port = $memcacheConfig['port'] ?? 11211;
        $timeout = $memcacheConfig['timeout'] ?? 200;

        $memcache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $timeout); // ms
        if ($memcache->addServer($host, $port) === false) {
            throw new RuntimeException('Unable to connect to Memcache');
        }
        return new static($memcache);
    }

    /**
     * @var \Memcached
     */
    protected $memcache;

    /**
     * @param \Memcached $memcache
     */
    public function __construct(\Memcached $memcache)
    {
        $this->memcache = $memcache;
    }

    /**
     * @param string $host
     * @return string
     */
    private function getKey($host)
    {
        return "DbReplication:$host";
    }

    /**
     * @inheritdoc
     */
    public function getSecondsBehind($host)
    {
        $key = $this->getKey($host) . ':SecondsBehind';
        return $this->memcache->get($key);
    }

    /**
     * @inheritdoc
     */
    public function setSecondsBehind($host, $value)
    {
        $key = $this->getKey($host) . ':SecondsBehind';
        $result = $this->memcache->set($key, (int)$value);
        if ($result === false) {
            throw new RuntimeException('Unable to store value to Memcache');
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHistory($host)
    {
        $key = $this->getKey($host) . ':History';
        return unserialize($this->memcache->get($key));
    }

    /**
     * @inheritdoc
     */
    public function setHistory($host, array $values)
    {
        $key = $this->getKey($host) . ':History';
        $result = $this->memcache->set($key, serialize($values));
        if ($result === false) {
            throw new RuntimeException('Unable to store value to Memcache');
        }

        return $this;
    }
}
