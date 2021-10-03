<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication;

use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\DbHelperReplication\Replication\StorageMock;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationFactoryTest extends TestCase
{
    use PHPMock;

    public function testCreateFromConfigSuccessfully(): void
    {
        $config = $this->getDefaultConfig();
        $replication = (new ReplicationFactory())->createFromConfig($config);
        static::assertInstanceOf(Replication::class, $replication);
    }

    public function testCreateFromConfigWithInvalidStorageClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = $this->getDefaultConfig();
        $config['storage']['class'] = '\My\Unknown\Class';
        (new ReplicationFactory())->createFromConfig($config);
    }

    public function testCreateFromConfigWithInvalidStorageMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = $this->getDefaultConfig();
        $config['storage']['class'] = \stdClass::class;
        (new ReplicationFactory())->createFromConfig($config);
    }

    public function getDefaultConfig(): array
    {
        return [
            'host' => '10.0.0.1',
            'username' => 'foo',
            'password' => 'bar',
            'replicas' => [
                [
                    'host' => '10.0.0.2',
                    'username' => 'foo',
                    'password' => 'bar',
                ],
            ],
            'storage' => [
                'class' => StorageMock::class,
                'args' => [[]],
            ],
        ];
    }
}
