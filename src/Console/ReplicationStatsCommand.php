<?php

namespace Phlib\DbHelper\Console;

use Phlib\DbHelper\Replication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * @package Phlib\DbHelper
 * @licence LGPL-3.0
 */
class ReplicationStatsCommand extends Command
{
    protected function configure()
    {
        $this->setName('replication:stats')
            ->setDescription('CLI for interacting with the Beanstalk server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config      = $this->getHelper('configuration')->fetch();
        $replication = Replication::createFromConfig($config);
        $replication->stats();
    }
}
