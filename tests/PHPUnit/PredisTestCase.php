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

use PHPUnit\AssertSameWithPrecisionConstraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\OneOfConstraint;
use PHPUnit\Util\Test as TestUtil;
use Predis\Client;
use Predis\Command;
use Predis\Connection;

/**
 * Base test case class for the Predis test suite.
 */
abstract class PredisTestCase extends \PHPUnit\Framework\TestCase
{
    protected $redisServerVersion;
    protected $redisJsonVersion;

    /**
     * @var string[]
     */
    private $modulesMapping = [
        'json' => ['annotation' => 'requiresRedisJsonVersion', 'name' => 'ReJSON'],
        'bloomFilter' => ['annotation' => 'requiresRedisBfVersion', 'name' => 'bf'],
        'search' => ['annotation' => 'requiresRediSearchVersion', 'name' => 'search'],
        'timeSeries' => ['annotation' => 'requiresRedisTimeSeriesVersion', 'name' => 'timeseries'],
        'gears' => ['annotation' => 'requiresRedisGearsVersion', 'name' => 'redisgears_2'],
    ];

    /**
     * Info of current Redis instance.
     *
     * @var array
     */
    private $info;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->checkRequiredRedisServerVersion();

        foreach ($this->modulesMapping as $module => $config) {
            $this->checkRequiredRedisModuleVersion($module);
        }
    }

    /**
     * Pauses the test case for the specified amount of time in seconds.
     *
     * @param float $seconds Seconds to sleep
     */
    protected function sleep(float $seconds): void
    {
        usleep($seconds * 1000000);
    }

    /**
     * Verifies that a Redis command is a valid Predis\Command\CommandInterface
     * instance with the specified ID and command arguments.
     *
     * @param Command\CommandInterface|string $command   Expected command instance or command ID
     * @param ?array                          $arguments Expected command arguments
     *
     * @return RedisCommandConstraint
     */
    public function isRedisCommand($command = null, array $arguments = null): RedisCommandConstraint
    {
        return new RedisCommandConstraint($command, $arguments);
    }

    /**
     * Ensures that two Redis commands are similar.
     *
     * This method supports can test for different constraints by accepting a few
     * combinations of values as indicated below:
     *
     * - a string identifying a Redis command by its ID
     * - an instance of Predis\Command\CommandInterface
     * - an array of [(string) $commandID, (array) $commandArguments]
     *
     * Internally this method uses the RedisCommandConstraint class.
     *
     * @param Command\CommandInterface|string|array $expected Expected command instance or command ID
     * @param mixed                                 $actual   Actual command
     * @param string                                $message  Optional assertion message
     */
    public function assertRedisCommand($expected, $actual, string $message = ''): void
    {
        if (is_array($expected)) {
            @[$command, $arguments] = $expected;
        } else {
            $command = $expected;
            $arguments = null;
        }

        $this->assertThat($actual, new RedisCommandConstraint($command, $arguments), $message);
    }

    /**
     * Asserts that two arrays have the same values (even with different order).
     *
     * @param array  $expected Expected array
     * @param array  $actual   Actual array
     * @param string $message  Optional assertion message
     */
    public function assertSameValues(array $expected, array $actual, $message = ''): void
    {
        $this->assertThat($actual, new ArrayHasSameValuesConstraint($expected), $message);
    }

    /**
     * Asserts that actual value is one of the values from expected array.
     *
     * @param  mixed  $expected Expected array.
     * @param  mixed  $actual   Actual value. If array given searching for any matching value between two arrays.
     * @param  string $message  Optional assertion message
     * @return void
     */
    public function assertOneOf(array $expected, $actual, string $message = ''): void
    {
        $this->assertThat($actual, new OneOfConstraint($expected), $message);
    }

    /**
     * Asserts that two values (of the same type) have the same values with given precision.
     *
     * @param  mixed  $expected  Expected value
     * @param  mixed  $actual    Actual value
     * @param  int    $precision Precision value should be round to
     * @param  string $message   Optional assertion message
     * @return void
     */
    public function assertSameWithPrecision($expected, $actual, int $precision = 0, string $message = ''): void
    {
        $this->assertThat($actual, new AssertSameWithPrecisionConstraint($expected, $precision), $message);
    }

    /**
     * Asserts that a string matches a given regular expression.
     *
     * @throws ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, $message = ''): void
    {
        if (method_exists(get_parent_class(parent::class), __FUNCTION__)) {
            call_user_func([parent::class, __FUNCTION__], $pattern, $string, $message);
        } else {
            static::assertRegExp($pattern, $string, $message);
        }
    }

    /**
     * Returns a named array with default values for connection parameters.
     *
     * @return array Default connection parameters
     */
    protected function getDefaultParametersArray(): array
    {
        if ($this->isClusterTest()) {
            return $this->prepareClusterEndpoints();
        }

        return [
            'scheme' => 'tcp',
            'host' => constant('REDIS_SERVER_HOST'),
            'port' => constant('REDIS_SERVER_PORT'),
            'database' => constant('REDIS_SERVER_DBNUM'),
        ];
    }

    /**
     * Returns a named array with default values for client options.
     *
     * @return array Default connection parameters
     */
    protected function getDefaultOptionsArray(): array
    {
        return [
            'commands' => new Command\RedisFactory(),
        ];
    }

    /**
     * Merges a named array of connection parameters with current defaults.
     *
     * @param array $additional Additional connection parameters
     *
     * @return array
     */
    protected function getParametersArray(array $additional): array
    {
        return array_merge($this->getDefaultParametersArray(), $additional);
    }

    /**
     * Returns a new instance of connection parameters.
     *
     * Values in the optional $additional named array are merged with defaults.
     *
     * @param array $additional Additional connection parameters
     *
     * @return Connection\ParametersInterface
     */
    protected function getParameters($additional = []): Connection\ParametersInterface
    {
        $parameters = array_merge($this->getDefaultParametersArray(), $additional);
        $parameters = new Connection\Parameters($parameters);

        return $parameters;
    }

    /**
     * Returns a new instance of command factory.
     *
     * @return Command\Factory
     */
    protected function getCommandFactory(): Command\Factory
    {
        return new Command\RedisFactory();
    }

    /**
     * Returns a new instance of Predis\Client.
     *
     * Values in the optional $parameters named array are merged with defaults.
     * Values in the ottional $options named array are merged with defaults.
     *
     * @param array $parameters Additional connection parameters
     * @param array $options    Additional client options
     * @param bool  $flushdb    Flush selected database before returning the client
     *
     * @return Client
     */
    protected function createClient(array $parameters = null, array $options = null, ?bool $flushdb = true): Client
    {
        $parameters = array_merge(
            $this->getDefaultParametersArray(),
            $parameters ?: []
        );

        $options = array_merge(
            ['commands' => $this->getCommandFactory()],
            $options ?: [],
            getenv('USE_RELAY') ? ['connections' => 'relay'] : []
        );

        if ($this->isClusterTest()) {
            $options = array_merge(
                [
                    'cluster' => 'redis',
                ],
                $options
            );
        }

        $client = new Client($parameters, $options);
        $client->connect();

        if ($flushdb) {
            $client->flushdb();
        }

        return $client;
    }

    /**
     * Returns a basic mock object of a connection to a single Redis node.
     *
     * The specified target interface used for the mock object must implement
     * Predis\Connection\NodeConnectionInterface.
     *
     * The mock object responds to getParameters() and __toString() by returning
     * the default connection parameters used by Predis or a set of connection
     * parameters specified in the optional second argument.
     *
     * @param string       $interface  Fully-qualified name of the target interface
     * @param array|string $parameters Optional connection parameters
     *
     * @return MockObject|Connection\NodeConnectionInterface
     */
    protected function getMockConnectionOfType(string $interface, $parameters = null)
    {
        if (!is_a($interface, '\Predis\Connection\NodeConnectionInterface', true)) {
            $method = __METHOD__;

            throw new \InvalidArgumentException(
                "Argument `\$interface` for $method() expects a type implementing Predis\Connection\NodeConnectionInterface"
            );
        }

        $connection = $this->getMockBuilder($interface)->getMock();

        if ($parameters) {
            $parameters = Connection\Parameters::create($parameters);
            $hash = "{$parameters->host}:{$parameters->port}";

            $connection
                ->expects($this->any())
                ->method('getParameters')
                ->willReturn($parameters);
            $connection
                ->expects($this->any())
                ->method('__toString')
                ->willReturn($hash);
        }

        return $connection;
    }

    /**
     * Returns a basic mock object of a connection to a single Redis node.
     *
     * The mock object is based on Predis\Connection\NodeConnectionInterface.
     *
     * The mock object responds to getParameters() and __toString() by returning
     * the default connection parameters used by Predis or a set of connection
     * parameters specified in the optional second argument.
     *
     * @param array|string|null $parameters Optional connection parameters
     *
     * @return MockObject|Connection\NodeConnectionInterface
     */
    protected function getMockConnection($parameters = null)
    {
        return $this->getMockConnectionOfType('Predis\Connection\NodeConnectionInterface', $parameters);
    }

    /**
     * Returns the server version of the Redis instance used by the test suite.
     *
     * @return string
     * @throws RuntimeException When the client cannot retrieve the current server version
     */
    protected function getRedisServerVersion(): string
    {
        if (isset($this->redisServerVersion)) {
            return $this->redisServerVersion;
        }

        if (isset($this->info)) {
            $info = $this->info;
        } else {
            $client = $this->createClient(null, null, true);
            $info = array_change_key_case($client->info());
            $this->info = $info;
        }

        if (isset($info['server']['redis_version'])) {
            // Redis >= 2.6
            $version = $info['server']['redis_version'];
        } elseif (isset($info['redis_version'])) {
            // Redis < 2.6
            $version = $info['redis_version'];
        } else {
            $client = $this->createClient(null, null, true);
            $connection = $client->getConnection();
            throw new RuntimeException("Unable to retrieve a valid server info payload from $connection");
        }

        $this->redisServerVersion = $version;

        return $version;
    }

    /**
     * Returns the Redis server version required to run a @connected test.
     *
     * This value is retrieved from the @requiresRedisVersion annotation that
     * decorates the target test method.
     *
     * @return string
     */
    protected function getRequiredRedisServerVersion(): ?string
    {
        $annotations = TestUtil::parseTestMethodAnnotations(
            get_class($this),
            $this->getName(false)
        );

        if (isset($annotations['method']['requiresRedisVersion'], $annotations['method']['group'])
            && !empty($annotations['method']['requiresRedisVersion'])
            && in_array('connected', $annotations['method']['group'])
        ) {
            return $annotations['method']['requiresRedisVersion'][0];
        }

        return null;
    }

    /**
     * Compares the specified version string against the Redis server version in
     * use for integration tests.
     *
     * @param string $operator Comparison operator
     * @param string $version  Version to compare
     *
     * @return bool
     */
    public function isRedisServerVersion(string $operator, string $version): bool
    {
        $serverVersion = $this->getRedisServerVersion();
        $comparison = version_compare($serverVersion, $version);

        return (bool) eval("return $comparison $operator 0;");
    }

    /**
     * Ensures the current Redis server matches version requirements for tests.
     *
     * Requirements are retrieved from the @requiresRedisVersion annotation that
     * decorates test methods while the version of the Redis server used to run
     * integration tests is retrieved directly from the server by using `INFO`.
     *
     * @throws \PHPUnit\Framework\SkippedTestError When the required Redis server version is not met
     */
    protected function checkRequiredRedisServerVersion(): void
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
                "Test requires a Redis server instance $reqOperator $reqVersion but target server is $serverVersion"
            );
        }
    }

    /**
     * Ensures the current Redis JSON module matches version requirements for tests.
     *
     * @param  string $module
     * @return void
     */
    protected function checkRequiredRedisModuleVersion(string $module): void
    {
        if (null === $requiredVersion = $this->getRequiredModuleVersion($module)) {
            return;
        }

        if (version_compare($this->getRedisServerVersion(), '6.0.0', '<')) {
            $this->markTestSkipped(
                'Test skipped because Redis JSON module available since Redis 6.x'
            );
        }

        $requiredVersion = explode(' ', $requiredVersion, 2);

        if (count($requiredVersion) === 1) {
            $reqVersion = $requiredVersion[0];
        } else {
            $reqVersion = $requiredVersion[1];
        }

        if (!$this->isSatisfiedRedisModuleVersion($reqVersion, $module)) {
            $redisModuleVersion = $this->getRedisModuleVersion($this->modulesMapping[$module]['name']);
            $redisModuleVersion = str_replace('0', '.', $redisModuleVersion);

            $this->markTestSkipped(
                "Test requires a Redis $module module >= $reqVersion but target module is $redisModuleVersion"
            );
        }
    }

    /**
     * @param  string $versionToCheck
     * @param  string $module
     * @return bool
     */
    protected function isSatisfiedRedisModuleVersion(string $versionToCheck, string $module): bool
    {
        $currentVersion = $this->getRedisModuleVersion($this->modulesMapping[$module]['name']);
        $versionToCheck = str_replace('.', '0', $versionToCheck);

        return $currentVersion >= (int) $versionToCheck;
    }

    /**
     * Returns version of Redis JSON module if it's available.
     *
     * @param  string $module
     * @return string
     */
    protected function getRedisModuleVersion(string $module): string
    {
        if (isset($this->info)) {
            $info = $this->info;
        } else {
            $client = $this->createClient(null, null, true);
            $info = array_change_key_case($client->info());
            $this->info = $info;
        }

        return $info['modules'][$module]['ver'] ?? '0';
    }

    /**
     * Returns version of given module for current Redis instance.
     * Runs if command belong to one of modules and marked with appropriate annotation
     * Runs on @connected tests.
     *
     * @param  string $module
     * @return string
     */
    protected function getRequiredModuleVersion(string $module): ?string
    {
        if (!isset($this->modulesMapping[$module])) {
            throw new InvalidArgumentException('No existing annotation for given module');
        }

        $moduleAnnotation = $this->modulesMapping[$module]['annotation'];
        $annotations = TestUtil::parseTestMethodAnnotations(
            get_class($this),
            $this->getName(false)
        );

        if (isset($annotations['method'][$moduleAnnotation], $annotations['method']['group'])
            && !empty($annotations['method'][$moduleAnnotation])
            && in_array('connected', $annotations['method']['group'], true)
        ) {
            return $annotations['method'][$moduleAnnotation][0];
        }

        return null;
    }

    /**
     * Marks current test skipped when test suite is running on CI environments.
     *
     * @param string $message
     */
    protected function markTestSkippedOnCIEnvironment(string $message = 'Test skipped on CI environment'): void
    {
        if (getenv('GITHUB_ACTIONS') || getenv('TRAVIS')) {
            $this->markTestSkipped($message);
        }
    }

    /**
     * Check annotations if it's matches to cluster test scenario.
     *
     * @return bool
     */
    protected function isClusterTest(): bool
    {
        $annotations = TestUtil::parseTestMethodAnnotations(
            get_class($this),
            $this->getName(false)
        );

        $annotationExists = isset($annotations['method']['requiresRedisVersion']);

        if (!$annotationExists) {
            foreach ($this->modulesMapping as $module => $configuration) {
                if (isset($annotations['method'][$configuration['annotation']])) {
                    $annotationExists = true;
                }
            }
        }

        return $annotationExists
            && isset($annotations['method']['group'])
            && in_array('connected', $annotations['method']['group'], true)
            && in_array('cluster', $annotations['method']['group'], true);
    }

    /**
     * Parse comma-separated cluster endpoints and convert them into tcp strings.
     *
     * @return array
     */
    protected function prepareClusterEndpoints(): array
    {
        $endpoints = explode(',', constant('REDIS_CLUSTER_ENDPOINTS'));

        return array_map(static function (string $elem) {
            return 'tcp://' . $elem;
        }, $endpoints);
    }
}
