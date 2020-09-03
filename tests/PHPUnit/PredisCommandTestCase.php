<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use PredisTestCase;
use Predis\Client;
use Predis\Command;
use Predis\Command\CommandInterface;

/**
 *
 */
abstract class PredisCommandTestCase extends PredisTestCase
{
    /**
     * Returns the expected command for tests.
     *
     * @return Command\CommandInterface|string Instance or FQCN of the expected command
     */
    abstract protected function getExpectedCommand(): string;

    /**
     * Returns the expected command ID for tests.
     *
     * @return string
     */
    abstract protected function getExpectedId(): string;

    /**
     * Returns a new command instance.
     *
     * @return Command\CommandInterface
     */
    public function getCommand(): Command\CommandInterface
    {
        $command = $this->getExpectedCommand();

        return $command instanceof Command\CommandInterface ? $command : new $command();
    }

    /**
     * Returns a new client instance.
     *
     * @param bool $flushdb Flush selected database before returning the client
     *
     * @return Client
     */
    public function getClient(bool $flushdb = true): Client
    {
        $commands = $this->getCommandFactory();

        if (!$commands->supports($id = $this->getExpectedId())) {
            $this->markTestSkipped(
                "The current command factory does not support command $id"
            );
        }

        $client = $this->createClient(null, null, $flushdb);

        return $client;
    }

    /**
     * Verifies if the command implements the prefixable interface.
     *
     * @return bool
     */
    protected function isPrefixable(): bool
    {
        return $this->getCommand() instanceof Command\PrefixableCommandInterface;
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param ... List of arguments for the command
     *
     * @return CommandInterface
     */
    protected function getCommandWithArguments(...$arguments): CommandInterface
    {
        return $this->getCommandWithArgumentsArray($arguments);
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param array $arguments Arguments for the command
     *
     * @return CommandInterface
     */
    protected function getCommandWithArgumentsArray(array $arguments): CommandInterface
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        return $command;
    }

    /**
     * @group disconnected
     */
    public function testCommandId(): void
    {
        $command = $this->getCommand();

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals($this->getExpectedId(), $command->getId());
    }

    /**
     * @group disconnected
     */
    public function testRawArguments(): void
    {
        $expected = array('1st', '2nd', '3rd', '4th');

        $command = $this->getCommand();
        $command->setRawArguments($expected);

        $this->assertSame($expected, $command->getArguments());
    }
}
