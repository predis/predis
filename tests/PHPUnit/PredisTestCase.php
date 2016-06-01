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
use Predis\Command;
use Predis\Connection;
use Predis\Profile;

/**
 * Base test case class for the Predis test suite.
 */
abstract class PredisTestCase extends \PHPUnit_Framework_TestCase
{
    protected $redisServerVersion = null;

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
     * Returns if the current runtime is HHVM.
     *
     * @return bool
     */
    protected function isHHVM()
    {
        return defined('HHVM_VERSION');
    }

    /**
     * Verifies that a Redis command is a valid Predis\Command\CommandInterface
     * instance with the specified ID and command arguments.
     *
     * @param string|Command\CommandInterface $command   Expected command or command ID.
     * @param array                           $arguments Expected command arguments.
     *
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
     * @param array|string|Command\CommandInterface $expected Expected command.
     * @param mixed                                 $actual   Actual command.
     * @param string                                $message  Optional assertion message.
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
     * @param array $additional Additional connection parameters.
     *
     * @return array Connection parameters.
     */
    protected function getParametersArray(array $additional)
    {
        return array_merge($this->getDefaultParametersArray(), $additional);
    }

    /**
     * Returns a new instance of connection parameters.
     *
     * @param array $additional Additional connection parameters.
     *
     * @return Connection\Parameters
     */
    protected function getParameters($additional = array())
    {
        $parameters = array_merge($this->getDefaultParametersArray(), $additional);
        $parameters = new Connection\Parameters($parameters);

        return $parameters;
    }

    /**
     * Returns a new instance of server profile.
     *
     * @param string $version Redis profile.
     *
     * @return Profile\ProfileInterface
     */
    protected function getProfile($version = null)
    {
        return Profile\Factory::get($version ?: REDIS_SERVER_VERSION);
    }

    /**
     * Returns the current server profile in use by the test suite.
     *
     * @return Profile\ProfileInterface
     */
    protected function getCurrentProfile()
    {
        static $profile;

        $profile = $this->getProfile();

        return $profile;
    }

    /**
     * Returns a new client instance.
     *
     * @param array $parameters Additional connection parameters.
     * @param array $options    Additional client options.
     * @param bool  $flushdb    Flush selected database before returning the client.
     *
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
     * Returns a base mocked connection from Predis\Connection\NodeConnectionInterface.
     *
     * @param mixed $parameters Optional parameters.
     *
     * @return mixed
     */
    protected function getMockConnection($parameters = null)
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        if ($parameters) {
            $parameters = Connection\Parameters::create($parameters);
            $hash = "{$parameters->host}:{$parameters->port}";

            $connection->expects($this->any())
                       ->method('getParameters')
                       ->will($this->returnValue($parameters));
            $connection->expects($this->any())
                       ->method('__toString')
                       ->will($this->returnValue($hash));
        }

        return $connection;
    }

    /**
     * Returns the server version of the Redis instance used by the test suite.
     *
     * @throws RuntimeException When the client cannot retrieve the current server version
     *
     * @return string
     */
    protected function getRedisServerVersion()
    {
        if (isset($this->redisServerVersion)) {
            return $this->redisServerVersion;
        }

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

        $this->redisServerVersion = $version;

        return $version;
    }

    /**
     * Returns the Redis server version required to run a @connected test from
     * the @requiresRedisVersion annotation decorating a test method.
     *
     * @return string
     */
    protected function getRequiredRedisServerVersion()
    {
        $annotations = $this->getAnnotations();

        if (isset($annotations['method']['requiresRedisVersion'], $annotations['method']['group']) &&
            !empty($annotations['method']['requiresRedisVersion']) &&
            in_array('connected', $annotations['method']['group'])
        ) {
            return $annotations['method']['requiresRedisVersion'][0];
        }

        return;
    }

    /**
     * Compares the specified version string against the Redis server version in
     * use for integration tests.
     *
     * @param string $operator Comparison operator.
     * @param string $version  Version to compare.
     *
     * @return bool
     */
    public function isRedisServerVersion($operator, $version)
    {
        $serverVersion = $this->getRedisServerVersion();
        $comparation = version_compare($serverVersion, $version);

        return (bool) eval("return $comparation $operator 0;");
    }

    /**
     * Checks that the Redis server version used to run integration tests mets
     * the requirements specified with the @requiresRedisVersion annotation.
     *
     * @throws \PHPUnit_Framework_SkippedTestError When expected Redis server version is not met.
     */
    protected function checkRequiredRedisServerVersion()
    {
        if (!$requiredVersion = $this->getRequiredRedisServerVersion()) {
            return;
        }

        $requiredVersion = explode(' ', $requiredVersion, 2);

        if (count($requiredVersion) === 1) {
            $reqOperator = '>=';
            $reqVersion = $requiredVersion[0];
        } else {
            $reqOperator = $requiredVersion[0];
            $reqVersion = $requiredVersion[1];
        }

        if (!$this->isRedisServerVersion($reqOperator, $reqVersion)) {
            $serverVersion = $this->getRedisServerVersion();

            $this->markTestSkipped(
                "This test requires Redis $reqOperator $reqVersion but the current version is $serverVersion."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkRequirements()
    {
        parent::checkRequirements();

        $this->checkRequiredRedisServerVersion();
    }
}
