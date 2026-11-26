<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Observability;

use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use Predis\Client;
use Predis\ClientException;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\ConnectionException;
use Predis\Connection\FactoryInterface;
use Predis\Connection\Parameters;
use Predis\PredisException;
use Predis\Response\Error;
use Predis\Response\ServerException;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\NoBackoff;
use PredisTestCase;
use RuntimeException;

/**
 * Covers the redis.client.errors metric of the resiliency group.
 */
class ErrorCounterTest extends PredisTestCase
{
    /**
     * Attributes of the errors added to the counter.
     *
     * @var array
     */
    private $errors = [];

    protected function setUp(): void
    {
        if (!interface_exists(MeterProviderInterface::class)) {
            $this->markTestSkipped('The OpenTelemetry API is not installed.');
        }
    }

    protected function tearDown(): void
    {
        ObservabilityInstance::getInstance()->init(new OTelConfig());
    }

    /**
     * @group disconnected
     */
    public function testCreatesCounterOnlyWhenResiliencyGroupIsEnabled(): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter
            ->expects($this->once())
            ->method('createCounter')
            ->with('redis.client.errors', '{error}', $this->isType('string'))
            ->willReturn($this->createMock(CounterInterface::class));

        new MetricsCollector($meter, new MetricsConfig(['enabled_metric_groups' => [MetricGroup::RESILIENCY]]));

        $meter = $this->createMock(MeterInterface::class);
        $meter->expects($this->never())->method('createCounter');

        new MetricsCollector($meter, new MetricsConfig(['enabled_metric_groups' => [MetricGroup::COMMAND]]));
    }

    /**
     * @group disconnected
     */
    public function testCountsServerErrorReturnedToApplication(): void
    {
        $this->enableErrorMetrics();

        $connection = $this->getMockConnection('tcp://redis.example:6380?database=5');
        $connection->method('executeCommand')->willReturn(new Error('WRONGTYPE Operation against a key'));

        try {
            (new Client($connection))->get('foo');
            $this->fail('Expected a ServerException');
        } catch (ServerException $exception) {
            // Expected.
        }

        $this->assertEquals([[
            'db.system.name' => 'redis',
            'redis.client.library' => 'predis:' . Client::VERSION,
            'server.address' => 'redis.example',
            'server.port' => 6380,
            'error.type' => 'ServerException',
            'db.response.status_code' => 'WRONGTYPE',
            'redis.client.errors.category' => 'server',
            'redis.client.errors.internal' => false,
            'redis.client.operation.retry_attempts' => 0,
        ]], $this->errors);
    }

    /**
     * @group disconnected
     */
    public function testCountsErrorReturnedAsResponseAndIgnoresCommandFilters(): void
    {
        $this->enableErrorMetrics(['exclude_commands' => ['GET']]);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn(new Error('NOAUTH Authentication required.'));

        (new Client($connection, ['exceptions' => false]))->get('foo');

        $this->assertCount(1, $this->errors);
        $this->assertSame('auth', $this->errors[0]['redis.client.errors.category']);
        $this->assertFalse($this->errors[0]['redis.client.errors.internal']);
    }

    /**
     * @group disconnected
     */
    public function testDoesNotCountSuccessfulOperations(): void
    {
        $this->enableErrorMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn('bar');

        $this->assertSame('bar', (new Client($connection))->get('foo'));
        $this->assertSame([], $this->errors);
    }

    /**
     * @group disconnected
     */
    public function testCountsRetriedFailuresAsInternalErrors(): void
    {
        $this->enableErrorMetrics();

        $connection = $this->getMockConnection(['retry' => new Retry(new NoBackoff(), 3)]);
        $connection
            ->method('executeCommand')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ConnectionException($connection, 'boom')),
                $this->throwException(new ConnectionException($connection, 'boom')),
                'bar'
            );

        $this->assertSame('bar', (new Client($connection))->get('foo'));

        $this->assertCount(2, $this->errors);

        foreach ($this->errors as $attempt => $attributes) {
            $this->assertSame('ConnectionException', $attributes['error.type']);
            $this->assertSame('network', $attributes['redis.client.errors.category']);
            $this->assertTrue($attributes['redis.client.errors.internal']);
            $this->assertSame($attempt, $attributes['redis.client.operation.retry_attempts']);
            $this->assertSame('127.0.0.1', $attributes['server.address']);
        }
    }

    /**
     * @group disconnected
     */
    public function testCountsFinalErrorAfterRetriesAreExhausted(): void
    {
        $this->enableErrorMetrics();

        $connection = $this->getMockConnection(['retry' => new Retry(new NoBackoff(), 1)]);
        $connection->method('executeCommand')->willThrowException(new ConnectionException($connection, 'boom'));

        try {
            (new Client($connection))->get('foo');
            $this->fail('Expected a ConnectionException');
        } catch (ConnectionException $exception) {
            // Expected.
        }

        $this->assertSame([true, false], array_column($this->errors, 'redis.client.errors.internal'));
        $this->assertSame([0, 1], array_column($this->errors, 'redis.client.operation.retry_attempts'));
    }

    /**
     * @group disconnected
     * @dataProvider errorsProvider
     *
     * @param mixed       $error      Error response returned, or exception thrown, by the connection.
     * @param string      $type       Expected error.type.
     * @param string      $category   Expected redis.client.errors.category.
     * @param string|null $statusCode Expected db.response.status_code.
     */
    public function testClassifiesErrorsLikeRedisPy($error, string $type, string $category, ?string $statusCode): void
    {
        $this->enableErrorMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');

        if ($error instanceof Error) {
            $connection->method('executeCommand')->willReturn($error);
        } else {
            $connection->method('executeCommand')->willThrowException($error);
        }

        try {
            (new Client($connection))->get('foo');
            $this->fail('Expected an exception');
        } catch (PredisException|RuntimeException $exception) {
            // Expected.
        }

        $this->assertSame($type, $this->errors[0]['error.type']);
        $this->assertSame($category, $this->errors[0]['redis.client.errors.category']);
        $this->assertSame($statusCode, $this->errors[0]['db.response.status_code'] ?? null);
    }

    public function errorsProvider(): array
    {
        return [
            'NOAUTH' => [new Error('NOAUTH Authentication required.'), 'ServerException', 'auth', 'NOAUTH'],
            'WRONGPASS' => [new Error('WRONGPASS invalid username-password pair'), 'ServerException', 'auth', 'WRONGPASS'],
            'NOPERM' => [new Error('NOPERM this user has no permissions'), 'ServerException', 'auth', 'NOPERM'],
            'invalid password' => [new Error('ERR invalid password'), 'ServerException', 'auth', 'ERR'],
            'AUTH arguments' => [new Error("ERR wrong number of arguments for 'AUTH' command"), 'ServerException', 'auth', 'ERR'],
            'no password set' => [new Error('ERR Client sent AUTH, but no password is set'), 'ServerException', 'auth', 'ERR'],
            'no password configured' => [
                new Error('ERR AUTH <password> called without any password configured for the default user. Are you sure your configuration is correct?'),
                'ServerException', 'auth', 'ERR',
            ],
            'LOADING' => [new Error('LOADING Redis is loading the dataset in memory'), 'ServerException', 'network', 'LOADING'],
            'max clients' => [new Error('ERR max number of clients reached'), 'ServerException', 'network', 'ERR'],
            'LDAP' => [new Error('ERR problem with LDAP service'), 'ServerException', 'network', 'ERR'],
            'generic ERR' => [new Error('ERR unknown command'), 'ServerException', 'server', 'ERR'],
            'CLUSTERDOWN' => [new Error('CLUSTERDOWN The cluster is down'), 'ServerException', 'server', 'CLUSTERDOWN'],
            'module code' => [new Error('SEARCH_SYNTAX bad query'), 'ServerException', 'server', 'SEARCH_SYNTAX'],
            'no error code' => [new Error('Syntax error at offset 3'), 'ServerException', 'server', null],
            'client error' => [new ClientException('invalid usage'), 'ClientException', 'server', null],
            'foreign error' => [new RuntimeException('boom'), 'RuntimeException', 'other', null],
        ];
    }

    /**
     * @group disconnected
     */
    public function testCountsClusterRedirectionAsInternalError(): void
    {
        $this->enableErrorMetrics();

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->method('executeCommand')->willReturn(new Error('MOVED 1970 127.0.0.1:6380'));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->method('executeCommand')->willReturn('bar');

        $cluster = new RedisCluster($this->createMock(FactoryInterface::class), new Parameters());
        $cluster->useClusterSlots(false);
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('bar', $cluster->executeCommand($command));

        $this->assertCount(1, $this->errors);
        $this->assertSame('ServerException', $this->errors[0]['error.type']);
        $this->assertSame('MOVED', $this->errors[0]['db.response.status_code']);
        $this->assertTrue($this->errors[0]['redis.client.errors.internal']);
        $this->assertSame(6379, $this->errors[0]['server.port']);
    }

    /**
     * @group disconnected
     */
    public function testRecordingFailureDoesNotBreakCommand(): void
    {
        $counter = $this->createMock(CounterInterface::class);
        $counter->method('isEnabled')->willReturn(true);
        $counter->method('add')->willThrowException(new RuntimeException('exporter failure'));
        $this->initObservability($counter, []);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn(new Error('ERR failure'));

        $this->expectException(ServerException::class);

        (new Client($connection))->get('foo');
    }

    /**
     * Enables the resiliency group with a counter that stores the attributes of what is added to it.
     *
     * @param array $options Additional metrics options.
     */
    private function enableErrorMetrics(array $options = []): void
    {
        $counter = $this->createMock(CounterInterface::class);
        $counter->method('isEnabled')->willReturn(true);
        $counter->method('add')->willReturnCallback(function ($amount, $attributes) {
            $this->errors[] = $attributes;
        });

        $this->initObservability($counter, $options);
    }

    private function initObservability(CounterInterface $counter, array $options): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter->method('createCounter')->willReturn($counter);
        $provider = $this->createMock(MeterProviderInterface::class);
        $provider->method('getMeter')->willReturn($meter);

        ObservabilityInstance::getInstance()->init(new OTelConfig(
            new MetricsConfig($options + ['enabled' => true, 'enabled_metric_groups' => [MetricGroup::RESILIENCY]]),
            $provider
        ));
    }
}
