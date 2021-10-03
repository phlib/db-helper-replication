<?php

namespace Phlib\DbHelperReplication\Replication;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class StorageMock implements StorageInterface
{
    public static function createFromConfig(array $config): self
    {
        return new static();
    }

    public function getSecondsBehind(string $host): int
    {
        return 0;
    }

    public function setSecondsBehind(string $host, int $value): self
    {
        return $this;
    }

    /**
     * @return int[]
     */
    public function getHistory(string $host): array
    {
        return [];
    }

    /**
     * @param int[] $values
     */
    public function setHistory(string $host, array $values): self
    {
        return $this;
    }
}
