<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Replication;

use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\DbHelperReplication\Exception\RuntimeException;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class Memcache implements StorageInterface
{
    public static function createFromConfig(array $memcacheConfig): self
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

    public function __construct(
        private readonly \Memcached $memcache,
    ) {
    }

    private function getKey(string $host): string
    {
        return "DbReplication:{$host}";
    }

    public function getSecondsBehind(string $host): int
    {
        $key = $this->getKey($host) . ':SecondsBehind';
        return $this->memcache->get($key);
    }

    public function setSecondsBehind(string $host, int $value): self
    {
        $key = $this->getKey($host) . ':SecondsBehind';
        $result = $this->memcache->set($key, $value);
        if ($result === false) {
            throw new RuntimeException('Unable to store value to Memcache');
        }

        return $this;
    }

    /**
     * @return int[]
     */
    public function getHistory(string $host): array
    {
        $key = $this->getKey($host) . ':History';
        return unserialize($this->memcache->get($key));
    }

    /**
     * @param int[] $values
     */
    public function setHistory(string $host, array $values): self
    {
        $key = $this->getKey($host) . ':History';
        $result = $this->memcache->set($key, serialize($values));
        if ($result === false) {
            throw new RuntimeException('Unable to store value to Memcache');
        }

        return $this;
    }
}
