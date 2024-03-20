<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Redis;

use Predis\Client;
use Predis\Command;
use Predis\Command\CommandInterface;
use PredisTestCase;

abstract class PredisCommandTestCase extends PredisTestCase
{
    /**
     * Returns the expected command for tests.
     *
     * @return CommandInterface|string Instance or FQCN of the expected command
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
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface
    {
        $command = $this->getExpectedCommand();

        return $command instanceof CommandInterface ? $command : new $command();
    }

    /**
     * Returns a new client instance.
     *
     * @param  array  $options Additional client options
     * @param  bool   $flushdb Flush selected database before returning the client
     * @return Client
     */
    public function getClient(array $options = [], bool $flushdb = true): Client
    {
        $commands = $this->getCommandFactory();

        if (!$commands->supports($id = $this->getExpectedId())) {
            $this->markTestSkipped(
                "The current command factory does not support command $id"
            );
        }

        if ($this->isClusterTest()) {
            $options['cluster'] = 'redis';
        }

        return $this->createClient(null, $options, $flushdb);
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
     * @param mixed ...$arguments List of arguments for the command
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
        $sanitizedCommandId = str_replace('.', '', $command->getId());

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals($this->getExpectedId(), $sanitizedCommandId);
    }

    /**
     * @group disconnected
     */
    public function testRawArguments(): void
    {
        $expected = ['1st', '2nd', '3rd', '4th'];

        $command = $this->getCommand();
        $command->setRawArguments($expected);

        $this->assertSame($expected, $command->getArguments());
    }
}
