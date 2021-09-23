<?php

namespace Phlib\DbHelperReplication\Replication;

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
    protected $memcache;

    /**
     * @var Memcache
     */
    protected $storage;

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

    public function testGetKeyContainsHost()
    {
        $host = '127.0.0.1';
        static::assertContains($host, $this->storage->getKey($host));
    }

    public function testGetKeyIsNamespaced()
    {
        $host = '127.0.0.1';
        static::assertNotEquals($host, $this->storage->getKey($host));
    }

    public function testGetSecondsBehindReturnsValue()
    {
        $seconds = 123;
        $this->memcache->method('get')
            ->willReturn($seconds);

        static::assertEquals($seconds, $this->storage->getSecondsBehind('test-host'));
    }

    public function testGetSecondsBehindRequestUsingHost()
    {
        $host = 'test-host';
        $this->memcache->expects(static::once())
            ->method('get')
            ->with(static::stringContains($host));

        $this->storage->getSecondsBehind($host);
    }

    public function testSetSecondsBehindReceivesValue()
    {
        $seconds = 123;
        $this->memcache->expects(static::once())
            ->method('set')
            ->with(static::anything(), $seconds);

        $this->storage->setSecondsBehind('test-host', $seconds);
    }

    public function testSetSecondsBehindRequestUsingHost()
    {
        $host = 'test-host';
        $this->memcache->expects(static::once())
            ->method('set')
            ->with(static::stringContains($host));

        $this->storage->setSecondsBehind($host, 123);
    }

    public function testGetHistoryReturnsArray()
    {
        $history = [123, 123, 123, 23, 23, 3];
        $serialized = serialize($history);
        $this->memcache->method('get')
            ->willReturn($serialized);
        static::assertEquals($history, $this->storage->getHistory('test-host'));
    }

    public function testGetHistoryUsesHost()
    {
        $host = 'test-host';
        $this->memcache->expects(static::once())
            ->method('get')
            ->with(static::stringContains($host));
        $this->storage->getHistory($host);
    }

    public function testSetHistorySetsString()
    {
        $this->memcache->expects(static::once())
            ->method('set')
            ->with(static::anything(), static::isType('string'));
        $history = [123, 123, 123, 23, 23, 3];
        $this->storage->setHistory('test-host', $history);
    }

    public function testSetHistoryUsesHost()
    {
        $host = 'test-host';
        $this->memcache->expects(static::once())
            ->method('set')
            ->with(static::stringContains($host));
        $this->storage->setHistory($host, [123, 123, 123, 23, 23, 3]);
    }
}
