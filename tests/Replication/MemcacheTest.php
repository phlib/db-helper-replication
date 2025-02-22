<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Replication;

use Phlib\DbHelperReplication\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class MemcacheTest extends TestCase
{
    private \Memcached&MockObject $memcache;

    private Memcache $storage;

    protected function setUp(): void
    {
        if (!extension_loaded(\Memcached::class)) {
            static::markTestSkipped();
        }

        $this->memcache = $this->createMock(\Memcached::class);
        $this->storage = new Memcache($this->memcache);
        parent::setUp();
    }

    public function testImplementsInterface(): void
    {
        static::assertInstanceOf(StorageInterface::class, $this->storage);
    }

    public function testGetSecondsBehind(): void
    {
        $host = sha1(uniqid());
        $expectedKey = "DbReplication:{$host}:SecondsBehind";
        $seconds = rand();

        $this->memcache->expects(static::once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn($seconds);

        $actual = $this->storage->getSecondsBehind($host);
        static::assertSame($seconds, $actual);
    }

    public function testSetSecondsBehind(): void
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

    public function testSetSecondsBehindFailure(): void
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

    public function testGetHistory(): void
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
        static::assertSame($history, $actual);
    }

    public function testSetHistory(): void
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

    public function testSetHistoryFailure(): void
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

    #[Group('integration')]
    public function testCreateFromConfig(): void
    {
        if ((bool)getenv('INTEGRATION_MEMCACHE_ENABLED') !== true) {
            static::markTestSkipped();
            return;
        }

        $config = [
            'host' => getenv('INTEGRATION_MEMCACHE_HOST'),
            'port' => (int)getenv('INTEGRATION_MEMCACHE_PORT'),
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
