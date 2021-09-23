<?php

namespace Phlib\DbHelperReplication;

use Phlib\Db\Adapter;
use Phlib\DbHelperReplication\Exception\InvalidArgumentException;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationFactory
{
    /**
     * @param array $config
     * @return Replication
     */
    public function createFromConfig(array $config)
    {
        $primary = new Adapter([
            'host' => $config['host'],
            'username' => $config['username'],
            'password' => $config['password'],
        ]);

        $replicas = [];
        foreach ($config['replicas'] as $replica) {
            $replicas[] = new Adapter($replica);
        }

        $storageClass = $config['storage']['class'];
        if (!class_exists($storageClass)) {
            throw new InvalidArgumentException("Specified storage class '{$storageClass}' could not be found.");
        }
        if (!method_exists($storageClass, 'createFromConfig')) {
            throw new InvalidArgumentException(
                "Storage class '{$storageClass}' is missing required method 'createFromConfig'."
            );
        }
        $storage = call_user_func_array([$storageClass, 'createFromConfig'], $config['storage']['args']);

        return new Replication($primary, $replicas, $storage);
    }
}
