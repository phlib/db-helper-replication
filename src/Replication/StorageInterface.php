<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Replication;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
interface StorageInterface
{
    /**
     * @return static
     */
    public static function createFromConfig(array $config);

    public function getSecondsBehind(string $host): int;

    /**
     * @return $this
     */
    public function setSecondsBehind(string $host, int $value);

    /**
     * @return int[]
     */
    public function getHistory(string $host): array;

    /**
     * @param int[] $values
     * @return $this
     */
    public function setHistory(string $host, array $values);
}
