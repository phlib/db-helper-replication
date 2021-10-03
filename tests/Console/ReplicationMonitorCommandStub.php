<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ReplicationMonitorCommandStub extends ReplicationMonitorCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = parent::execute($input, $output);

        /**
         * Call shutdown() to prevent infinite execution loops in tests
         * @see BackgroundCommand::background()
         */
        $this->shutdown();

        return $result;
    }
}
