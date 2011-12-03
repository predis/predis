<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Commands;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Client;
use Predis\Profiles\ServerProfile;
use Predis\Profiles\IServerProfile;

/**
 *
 */
abstract class CommandTestCase extends StandardTestCase
{
    /**
     * Returns the expected command.
     *
     * @return ICommand|string Instance or FQN of the expected command.
     */
    protected abstract function getExpectedCommand();

    /**
     * Returns the expected command ID.
     *
     * @return string
     */
    protected abstract function getExpectedId();

    /**
     * Returns a new command instance.
     *
     * @return ICommand
     */
    protected function getCommand()
    {
        $command = $this->getExpectedCommand();

        return $command instanceof ICommand ? $command : new $command();
    }

    /**
     * Return the server profile used during the tests.
     *
     * @return IServerProfile
     */
    protected function getProfile()
    {
        return ServerProfile::get(REDIS_SERVER_VERSION);
    }

    /**
     * Returns a new client instance.
     *
     * @param Boolean $connect Flush selected database before returning the client.
     * @return Client
     */
    protected function getClient($flushdb = true)
    {
        $profile = $this->getProfile();

        if (!$profile->supportsCommand($id = $this->getExpectedId())) {
            $this->markTestSkipped("The profile {$profile->getVersion()} does not support command {$id}");
        }

        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
        );

        $options = array(
            'profile' => $profile
        );

        $client = new Client($parameters, $options);
        $client->connect();
        $client->select(REDIS_SERVER_DBNUM);

        if ($flushdb) {
            $client->flushdb();
        }

        return $client;
    }

    /**
     * Returns wether the command is prefixable or not.
     *
     * @return Boolean
     */
    protected function isPrefixable()
    {
        return $this->getCommand() instanceof IPrefixable;
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param ... List of arguments for the command.
     * @return ICommand
     */
    protected function getCommandWithArguments(/* arguments */)
    {
        return $this->getCommandWithArgumentsArray(func_get_args());
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param array $arguments Arguments for the command.
     * @return ICommand
     */
    protected function getCommandWithArgumentsArray(Array $arguments)
    {
        $command = $this->getCommand();
        $command->setArguments($arguments);

        return $command;
    }

    /**
     * Sleep the test case with microseconds resolution.
     *
     * @param float $seconds Seconds to sleep.
     */
    protected function sleep($seconds)
    {
        usleep($seconds * 1000000);
    }

    /**
     * Asserts that two arrays have the same values, even if with different order.
     *
     * @param Array $expected Expected array.
     * @param Array $actual Actual array.
     */
    protected function assertSameValues(Array $expected, Array $actual)
    {
        $this->assertThat($expected, new \ArrayHasSameValuesConstraint($actual));
    }

    /**
     * @group disconnected
     */
    public function testCommandId()
    {
        $command = $this->getCommand();

        $this->assertInstanceOf('Predis\Commands\ICommand', $command);
        $this->assertEquals($this->getExpectedId(), $command->getId());
    }

    /**
     * @group disconnected
     */
    public function testRawArguments()
    {
        $expected = array('1st', '2nd', '3rd', '4th');

        $command = $this->getCommand();
        $command->setRawArguments($expected);

        $this->assertSame($expected, $command->getArguments());
    }
}
