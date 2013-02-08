<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Client;
use Predis\Profile\ServerProfile;
use Predis\Profile\ServerProfileInterface;

/**
 *
 */
abstract class CommandTestCase extends StandardTestCase
{
    /**
     * Returns the expected command.
     *
     * @return CommandInterface|string Instance or FQN of the expected command.
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
     * @return CommandInterface
     */
    protected function getCommand()
    {
        $command = $this->getExpectedCommand();

        return $command instanceof CommandInterface ? $command : new $command();
    }

    /**
     * Return the server profile used during the tests.
     *
     * @return ServerProfileInterface
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
        return $this->getCommand() instanceof PrefixableCommandInterface;
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param ... List of arguments for the command.
     * @return CommandInterface
     */
    protected function getCommandWithArguments(/* arguments */)
    {
        return $this->getCommandWithArgumentsArray(func_get_args());
    }

    /**
     * Returns a new command instance with the specified arguments.
     *
     * @param array $arguments Arguments for the command.
     * @return CommandInterface
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

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals($this->getExpectedId(), $command->getId());
    }

    /**
     * @param  string $expectedVersion
     * @param  string $message Optional message.
     * @throws \RuntimeException when unable to retrieve server info or redis version
     * @throws \PHPUnit_Framework_SkippedTestError when expected redis version is not met
     */
    protected function markTestSkippedOnRedisVersionBelow($expectedVersion, $message = '')
    {
        $client = $this->getClient();
        $info = array_change_key_case($client->info());

        if (isset($info['server']['redis_version'])) {
            // Redis >= 2.6
            $version = $info['server']['redis_version'];
        } else if (isset($info['redis_version'])) {
            // Redis < 2.6
            $version = $info['redis_version'];
        } else {
            throw new \RuntimeException('Unable to retrieve server info');
        }

        if (version_compare($version, $expectedVersion) <= -1) {
            $this->markTestSkipped($message ?: "Test requires Redis $expectedVersion, current is $version.");
        }
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
