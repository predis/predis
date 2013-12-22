<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionParameters;
use Predis\Profile\ServerProfile;
use Predis\Profile\ServerProfileInterface;

/**
 * Base test case class for the Predis test suite.
 */
abstract class PredisTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Verifies that a Redis command is a valid Predis\Command\CommandInterface
     * instance with the specified ID and command arguments.
     *
     * @param  string|CommandInterface $command   Expected command or command ID.
     * @param  array                   $arguments Expected command arguments.
     * @return RedisCommandConstraint
     */
    public function isRedisCommand($command = null, array $arguments = null)
    {
        return new RedisCommandConstraint($command, $arguments);
    }

    /**
     * Verifies that a Redis command is a valid Predis\Command\CommandInterface
     * instance with the specified ID and command arguments. The comparison does
     * not check for identity when passing a Predis\Command\CommandInterface
     * instance for $expected.
     *
     * @param array|string|CommandInterface $expected Expected command.
     * @param mixed                         $actual   Actual command.
     * @param string                        $message  Optional assertion message.
     */
    public function assertRedisCommand($expected, $actual, $message = '')
    {
        if (is_array($expected)) {
            @list($command, $arguments) = $expected;
        } else {
            $command = $expected;
            $arguments = null;
        }

        $this->assertThat($actual, new RedisCommandConstraint($command, $arguments), $message);
    }

    /**
     * Asserts that two arrays have the same values, even if with different order.
     *
     * @param array  $expected Expected array.
     * @param array  $actual   Actual array.
     * @param string $message  Optional assertion message.
     */
    public function assertSameValues(array $expected, array $actual, $message = '')
    {
        $this->assertThat($actual, new ArrayHasSameValuesConstraint($expected), $message);
    }

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultParametersArray()
    {
        return array(
            'scheme' => 'tcp',
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
        );
    }

    /**
     * Returns a named array with the default client options and their values.
     *
     * @return array Default connection parameters.
     */
    protected function getDefaultOptionsArray()
    {
        return array(
            'profile' => REDIS_SERVER_VERSION,
        );
    }

    /**
     * Returns a named array with the default connection parameters merged with
     * the specified additional parameters.
     *
     * @param  array $additional Additional connection parameters.
     * @return array Connection parameters.
     */
    protected function getParametersArray(array $additional)
    {
        return array_merge($this->getDefaultParametersArray(), $additional);
    }

    /**
     * Returns a new instance of connection parameters.
     *
     * @param  array                           $additional Additional connection parameters.
     * @return Connection\ConnectionParameters Default connection parameters.
     */
    protected function getParameters($additional = array())
    {
        $parameters = array_merge($this->getDefaultParametersArray(), $additional);
        $parameters = new ConnectionParameters($parameters);

        return $parameters;
    }

    /**
     * Returns a new instance of server profile.
     *
     * @param  string                 $version Redis profile.
     * @return ServerProfileInterface
     */
    protected function getProfile($version = null)
    {
        return ServerProfile::get($version ?: REDIS_SERVER_VERSION);
    }

    /**
     * Returns a new client instance.
     *
     * @param  array  $parameters Additional connection parameters.
     * @param  array  $options    Additional client options.
     * @param  bool   $flushdb    Flush selected database before returning the client.
     * @return Client
     */
    protected function createClient(array $parameters = null, array $options = null, $flushdb = true)
    {
        $parameters = array_merge(
            $this->getDefaultParametersArray(),
            $parameters ?: array()
        );

        $options = array_merge(
            array(
                'profile' => $this->getProfile(),
            ),
            $options ?: array()
        );

        $client = new Client($parameters, $options);
        $client->connect();

        if ($flushdb) {
            $client->flushdb();
        }

        return $client;
    }

    /**
     * @param  string                             $expectedVersion Expected redis version.
     * @param  string                             $operator        Comparison operator.
     * @param  callable                           $callback        Callback for matching version.
     * @throws PHPUnit_Framework_SkippedTestError When expected redis version is not met.
     */
    protected function executeOnRedisVersion($expectedVersion, $operator, $callback)
    {
        $client = $this->createClient(null, null, true);
        $info = array_change_key_case($client->info());

        if (isset($info['server']['redis_version'])) {
            // Redis >= 2.6
            $version = $info['server']['redis_version'];
        } elseif (isset($info['redis_version'])) {
            // Redis < 2.6
            $version = $info['redis_version'];
        } else {
            throw new RuntimeException('Unable to retrieve server info');
        }

        $comparation = version_compare($version, $expectedVersion);

        if ($match = eval("return $comparation $operator 0;")) {
            call_user_func($callback, $this, $version);
        }

        return $match;
    }

    /**
     * @param  string                             $expectedVersion Expected redis version.
     * @param  string                             $operator        Comparison operator.
     * @param  callable                           $callback        Callback for matching version.
     * @throws PHPUnit_Framework_SkippedTestError When expected redis version is not met.
     */
    protected function executeOnProfileVersion($expectedVersion, $operator, $callback)
    {
        $profile = $this->getProfile();
        $comparation = version_compare($version = $profile->getVersion(), $expectedVersion);

        if ($match = eval("return $comparation $operator 0;")) {
            call_user_func($callback, $this, $version);
        }

        return $match;
    }

    /**
     * @param  string                              $expectedVersion Expected redis version.
     * @param  string                              $message         Optional message.
     * @param  bool                                $remote          Based on local profile or remote redis version.
     * @throws RuntimeException                    when unable to retrieve server info or redis version
     * @throws \PHPUnit_Framework_SkippedTestError when expected redis version is not met
     */
    public function markTestSkippedOnRedisVersionBelow($expectedVersion, $message = '', $remote = true)
    {
        $callback = function ($test, $version) use ($message, $expectedVersion) {
            $test->markTestSkipped($message ?: "Test requires Redis $expectedVersion, current is $version.");
        };

        $method = $remote ? 'executeOnRedisVersion' : 'executeOnProfileVersion';
        $this->$method($expectedVersion, '<', $callback);
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
}
