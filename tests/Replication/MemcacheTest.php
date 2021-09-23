<?php

namespace Phlib\DbHelperReplication\Replication;

use Phlib\DbHelperReplication\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class MemcacheTest extends TestCase
{
    /**
     * @var \Memcached|\PHPUnit_Framework_MockObject_MockObject
     */
    private $memcache;

    /**
     * @var Memcache
     */
    private $storage;

    public function setUp()
    {
        if (!extension_loaded('Memcached')) {
            static::markTestSkipped();
            return;
        }

        $this->memcache = $this->createMock(\Memcached::class);
        $this->storage  = new Memcache($this->memcache);
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->storage  = null;
        $this->memcache = null;
    }

    public function testImplementsInterface()
    {
        static::assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testGetSecondsBehind()
    {
        $host = sha1(uniqid());
        $expectedKey = "DbReplication:{$host}:SecondsBehind";
        $seconds = rand();

        $this->memcache->expects(static::once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn($seconds);

        $actual = $this->storage->getSecondsBehind($host);
        static::assertEquals($seconds, $actual);
    }

    public function testSetSecondsBehind()
    {
        $host = sha1(uniqid());
        $expectedKey = "DbReplication:{$host}:SecondsBehind";
        $seconds = rand();

        $this->memcache->expects(static::once())
            ->method('set')
            ->with($expectedKey, $seconds)
            ->willReturn(true);

        $this->storage->setSecondsBehind($host, $seconds);
    }

    public function testSetSecondsBehindFailure()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to store value');

        $host = sha1(uniqid());
        $seconds = rand();

        $this->memcache->expects(static::once())
            ->method('set')
            ->willReturn(false);

        $this->storage->setSecondsBehind($host, $seconds);
    }

    public function testGetHistory()
    {
        $host = sha1(uniqid());
        $expectedKey = "DbReplication:{$host}:History";
        $history = [rand(), rand(), rand(), rand(), rand(), rand()];
        $serialized = serialize($history);

        $this->memcache->expects(static::once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn($serialized);

        $actual = $this->storage->getHistory($host);
        static::assertEquals($history, $actual);
    }

    public function testSetHistory()
    {
        $host = sha1(uniqid());
        $expectedKey = "DbReplication:{$host}:History";
        $history = [rand(), rand(), rand(), rand(), rand(), rand()];
        $serialized = serialize($history);

        $this->memcache->expects(static::once())
            ->method('set')
            ->with($expectedKey, $serialized)
            ->willReturn(true);

        $this->storage->setHistory($host, $history);
    }

    public function testSetHistoryFailure()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to store value');

        $host = sha1(uniqid());
        $history = [rand()];

        $this->memcache->expects(static::once())
            ->method('set')
            ->willReturn(false);

        $this->storage->setHistory($host, $history);
    }

    /**
     * @group integration
     */
    public function testCreateFromConfig()
    {
        if ((bool)getenv('INTEGRATION_MEMCACHE_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        $config = [
            'host' => getenv('INTEGRATION_MEMCACHE_HOST'),
            'port' => getenv('INTEGRATION_MEMCACHE_PORT'),
            'timeout' => 10,
        ];

        $storage = Memcache::createFromConfig($config);

        $host = sha1(uniqid());
        $seconds = rand();

        $storage->setSecondsBehind($host, $seconds);
        $actual = $storage->getSecondsBehind($host);

        static::assertSame($seconds, $actual);
    }
}
