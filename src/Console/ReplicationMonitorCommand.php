<?php

namespace Phlib\DbHelperReplication\Console;

use Phlib\ConsoleProcess\Command\DaemonCommand;
use Phlib\DbHelperReplication\Replication;
use Phlib\DbHelperReplication\ReplicationFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationMonitorCommand extends DaemonCommand
{
    /**
     * @var ReplicationFactory
     */
    private $replicationFactory;

    /**
     * @var Replication
     */
    protected $replication;

    public function __construct(ReplicationFactory $replicationFactory)
    {
        $this->replicationFactory = $replicationFactory;

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

    protected function createChildOutput(): StreamOutput
    {
        $filename = getcwd() . '/replication-monitor.log';
        return new StreamOutput(fopen($filename, 'a'));
    }
}
