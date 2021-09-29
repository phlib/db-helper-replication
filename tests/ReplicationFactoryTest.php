<?php

namespace Phlib\DbHelperReplication;

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

    public function testCreateFromConfigSuccessfully()
    {
        $config = $this->getDefaultConfig();
        $replication = (new ReplicationFactory())->createFromConfig($config);
        static::assertInstanceOf(Replication::class, $replication);
    }

    /**
     * @expectedException \Phlib\DbHelperReplication\Exception\InvalidArgumentException
     */
    public function testCreateFromConfigWithInvalidStorageClass()
    {
        $config = $this->getDefaultConfig();
        $config['storage']['class'] = '\My\Unknown\Class';
        (new ReplicationFactory())->createFromConfig($config);
    }

    /**
     * @expectedException \Phlib\DbHelperReplication\Exception\InvalidArgumentException
     */
    public function testCreateFromConfigWithInvalidStorageMethod()
    {
        $config = $this->getDefaultConfig();
        $config['storage']['class'] = \stdClass::class;
        (new ReplicationFactory())->createFromConfig($config);
    }

    /**
     * @return array
     */
    public function getDefaultConfig()
    {
        return [
            'host'     => '10.0.0.1',
            'username' => 'foo',
            'password' => 'bar',
            'slaves'   => [
                [
                    'host'     => '10.0.0.2',
                    'username' => 'foo',
                    'password' => 'bar'
                ]
            ],
            'storage' => [
                'class' => StorageMock::class,
                'args'  => [[]]
            ],
        ];
    }
}
