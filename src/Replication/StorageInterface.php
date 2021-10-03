<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Replication;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
interface StorageInterface
{
    public static function createFromConfig(array $config): self;

    public function getSecondsBehind(string $host): int;

    public function setSecondsBehind(string $host, int $value): self;

    /**
     * @return int[]
     */
    public function getHistory(string $host): array;

    /**
     * @param int[] $values
     */
    public function setHistory(string $host, array $values): self;
}
