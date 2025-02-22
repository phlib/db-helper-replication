<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Console;

use Phlib\ConsoleProcess\Command\DaemonCommand;
use Phlib\DbHelperReplication\Replication;
use Phlib\DbHelperReplication\ReplicationFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationMonitorCommand extends DaemonCommand
{
    public function __construct(
        private readonly ReplicationFactory $replicationFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('replication:monitor')
            ->setDescription('CLI for monitoring MySQL replica status.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getReplication()->monitor();

        return 0;
    }

    protected function getReplication(): Replication
    {
        $config = $this->getHelper('configuration')->fetch();
        return $this->replicationFactory->createFromConfig($config);
    }
}
