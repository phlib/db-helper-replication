<?php

declare(strict_types=1);

namespace Phlib\DbHelperReplication\Console;

use Phlib\ConsoleConfiguration\Helper\ConfigurationHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @package Phlib\DbHelperReplication
 * @licence LGPL-3.0
 */
class ConsoleTestCase extends TestCase
{
    /**
     * @var Command
     */
    protected $command;

    /**
     * @var CommandTester
     */
    protected $commandTester;

    /**
     * @var ConfigurationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configurationHelper;

    protected function setUp(): void
    {
        $application = new Application();
        $application->add($this->command);

        $this->configurationHelper = $this->createMock(ConfigurationHelper::class);
        $this->configurationHelper->method('getName')
            ->willReturn('configuration');
        $application
            ->getHelperSet()
            ->set($this->configurationHelper);

        $this->commandTester = new CommandTester($this->command);

        parent::setUp();
    }
}
