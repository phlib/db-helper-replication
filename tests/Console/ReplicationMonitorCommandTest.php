<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Console;

use Phlib\DbHelperReplication\Replication;
use Phlib\DbHelperReplication\Replication\StorageMock;
use Phlib\DbHelperReplication\ReplicationFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationMonitorCommandTest extends ConsoleTestCase
{
    /**
     * @var ReplicationFactory|MockObject
     */
    private MockObject $replicationFactory;

    /**
     * @var Replication|MockObject
     */
    private MockObject $replication;

    protected function setUp(): void
    {
        $this->replicationFactory = $this->createMock(ReplicationFactory::class);
        $this->replication = $this->createMock(Replication::class);

        $this->command = new ReplicationMonitorCommandStub($this->replicationFactory);

        parent::setUp();
    }

    public function testExecute(): void
    {
        $config = [
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'replicas' => [
                [
                    'host' => '127.0.0.1',
                    'username' => 'root',
                    'password' => '',
                ],
            ],
            'storage' => [
                'class' => StorageMock::class,
                'args' => [[]],
            ],
        ];

        // Config should be fetched from the configuration helper
        $this->configurationHelper->expects(static::once())
            ->method('fetch')
            ->willReturn($config);

        // Config is then used to get the replication instance
        $this->replicationFactory->expects(static::once())
            ->method('createFromConfig')
            ->with($config)
            ->willReturn($this->replication);

        // Command must call `monitor()`
        $this->replication->expects(static::once())
            ->method('monitor')
            ->willReturnSelf();

        // As this command is a `DaemonCommand`, the tester needs to call the 'start' action.
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            'action' => 'start',
        ]);
    }
}
