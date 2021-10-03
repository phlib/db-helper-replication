<?php

namespace Phlib\DbHelperReplication;

use Phlib\Db\AdapterInterface;
use Phlib\DbHelperReplication\Exception\InvalidArgumentException;
use Phlib\DbHelperReplication\Exception\RuntimeException;
use Phlib\DbHelperReplication\Replication\StorageInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationTest extends TestCase
{
    use PHPMock;

    private const REPLICA_LAG_KEY = 'Seconds_Behind_Master';

    /**
     * @var AdapterInterface|MockObject
     */
    protected $primary;

    /**
     * @var StorageInterface|MockObject
     */
    protected $storage;

    protected function setUp(): void
    {
        $this->primary = $this->createMock(AdapterInterface::class);
        $this->primary->method('getConfig')
            ->willReturn([
                'host' => '127.0.0.1',
            ]);

        $this->storage = $this->createMock(StorageInterface::class);

        parent::setUp();
    }

    public function testConstructDoesNotAllowEmptyReplicas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Replication($this->primary, [], $this->storage);
    }

    public function testGettingStorageReturnsSameInstance(): void
    {
        $replica = $this->createMock(AdapterInterface::class);
        $replication = new Replication($this->primary, [$replica], $this->storage);
        static::assertSame($this->storage, $replication->getStorage());
    }

    public function testConstructChecksReplicas(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $replicas = [new \stdClass()];
        new Replication($this->primary, $replicas, $this->storage);
    }

    public function testSetWeighting(): void
    {
        $weighting = 12345;
        $replication = new Replication($this->primary, [$this->createMock(AdapterInterface::class)], $this->storage);
        $replication->setWeighting($weighting);
        static::assertEquals($weighting, $replication->getWeighting());
    }

    public function testSetMaximumSleep(): void
    {
        $maxSleep = 123456;
        $replication = new Replication($this->primary, [$this->createMock(AdapterInterface::class)], $this->storage);
        $replication->setMaximumSleep($maxSleep);
        static::assertEquals($maxSleep, $replication->getMaximumSleep());
    }

    /**
     * @dataProvider monitorRecordsToStorageDataProvider
     */
    public function testMonitorRecordsToStorage(string $method): void
    {
        $this->storage->expects(static::once())
            ->method($method);
        $replica = $this->createMock(AdapterInterface::class);
        $this->setupReplica($replica, [
            self::REPLICA_LAG_KEY => 20,
        ]);
        $replication = new Replication($this->primary, [$replica], $this->storage);
        $replication->monitor();
    }

    public function monitorRecordsToStorageDataProvider(): array
    {
        return [
            ['setSecondsBehind'],
            ['setHistory'],
        ];
    }

    public function testHistoryGetsTrimmed(): void
    {
        $maxEntries = Replication::MAX_HISTORY;

        $history = array_pad([], $maxEntries, 20);
        $replica = $this->createMock(AdapterInterface::class);
        $this->setupReplica($replica, [
            self::REPLICA_LAG_KEY => 5,
        ]);

        $this->storage->method('getHistory')
            ->willReturn($history);

        $this->storage->expects(static::once())
            ->method('setHistory')
            ->with(static::anything(), static::countOf($maxEntries));

        $replication = new Replication($this->primary, [$replica], $this->storage);
        $replication->monitor();
    }

    public function testHistoryGetsNewReplicaValue(): void
    {
        $maxEntries = Replication::MAX_HISTORY;
        $newValue = 5;

        $history = array_pad([], $maxEntries / 2, 20);
        $replica = $this->createMock(AdapterInterface::class);
        $this->setupReplica($replica, [
            self::REPLICA_LAG_KEY => $newValue,
        ]);

        $this->storage->method('getHistory')
            ->willReturn($history);

        $this->storage->expects(static::once())
            ->method('setHistory')
            ->with(static::anything(), static::contains($newValue));

        $replication = new Replication($this->primary, [$replica], $this->storage);
        $replication->monitor();
    }

    public function testFetchStatusMakesCorrectCall(): void
    {
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn([
                self::REPLICA_LAG_KEY => 10,
            ]);

        /** @var AdapterInterface|MockObject $replica */
        $replica = $this->createMock(AdapterInterface::class);
        $replica->expects(static::once())
            ->method('query')
            ->with('SHOW SLAVE STATUS')
            ->willReturn($pdoStatement);

        $replication = new Replication($this->primary, [$replica], $this->storage);
        $replication->fetchStatus($replica);
    }

    /**
     * @param array|false $data
     * @dataProvider fetchStatusErrorsWithBadReturnedDataDataProvider
     */
    public function testFetchStatusErrorsWithBadReturnedData($data): void
    {
        $this->expectException(RuntimeException::class);
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn($data);

        /** @var AdapterInterface|MockObject $replica */
        $replica = $this->createMock(AdapterInterface::class);
        $replica->method('query')
            ->willReturn($pdoStatement);

        $replication = new Replication($this->primary, [$replica], $this->storage);
        $replication->fetchStatus($replica);
    }

    public function fetchStatusErrorsWithBadReturnedDataDataProvider(): array
    {
        return [
            [false],
            [[
                'FooColumn' => 'bar',
            ]],
            [[
                self::REPLICA_LAG_KEY => null,
            ]],
        ];
    }

    public function testThrottleWithNoReplicaLag(): void
    {
        $this->storage->method('getSecondsBehind')
            ->willReturn(0);

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::once())
            ->with(0);

        $replica = $this->createMock(AdapterInterface::class);
        (new Replication($this->primary, [$replica], $this->storage))->throttle();
    }

    public function testThrottleWithReplicaLag(): void
    {
        $this->storage->method('getSecondsBehind')
            ->willReturn(500);

        $usleep = $this->getFunctionMock(__NAMESPACE__, 'usleep');
        $usleep->expects(static::once())
            ->with(static::greaterThan(0));

        $replica = $this->createMock(AdapterInterface::class);
        (new Replication($this->primary, [$replica], $this->storage))->throttle();
    }

    /**
     * @param AdapterInterface|MockObject $replica
     * @param mixed $return
     */
    protected function setupReplica(MockObject $replica, $return): void
    {
        $pdoStatement = $this->createMock(\PDOStatement::class);
        $pdoStatement->method('fetch')
            ->willReturn($return);

        $replica->method('query')
            ->willReturn($pdoStatement);
    }
}
