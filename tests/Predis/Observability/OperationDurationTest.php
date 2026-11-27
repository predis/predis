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

use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use Predis\Client;
use Predis\Command\CommandInterface;
use Predis\Connection\ConnectionException;
use Predis\Connection\RelayConnection;
use Predis\Connection\StreamConnection;
use Predis\Response\Error;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\NoBackoff;
use PredisTestCase;
use RuntimeException;

/**
 * Covers the db.client.operation.duration metric of the command group.
 */
class OperationDurationTest extends PredisTestCase
{
    /**
     * Measurements recorded on the histogram, as [duration, attributes] pairs.
     *
     * @var array
     */
    private $records = [];

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
    public function testCreatesHistogramWithConfiguredBuckets(): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter
            ->expects($this->once())
            ->method('createHistogram')
            ->with('db.client.operation.duration', 's', $this->isType('string'), ['ExplicitBucketBoundaries' => [0.1, 1]])
            ->willReturn($this->createMock(HistogramInterface::class));

        new MetricsCollector($meter, new MetricsConfig([
            'enabled_metric_groups' => [MetricGroup::COMMAND],
            'buckets_operation_duration' => [0.1, 1],
        ]));
    }

    /**
     * @group disconnected
     */
    public function testDoesNotCreateHistogramWhenCommandGroupIsDisabled(): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter->expects($this->never())->method('createHistogram');

        $collector = new MetricsCollector($meter, new MetricsConfig(['enabled_metric_groups' => [MetricGroup::RESILIENCY]]));

        $this->assertFalse($collector->tracksOperation('GET'));
    }

    /**
     * @group disconnected
     */
    public function testRecordsDurationOfSuccessfulCommand(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://redis.example:6380?database=5');
        $connection->expects($this->once())->method('executeCommand')->willReturn('bar');

        $this->assertSame('bar', (new Client($connection))->get('foo'));

        $this->assertCount(1, $this->records);
        [$duration, $attributes] = $this->records[0];

        $this->assertIsFloat($duration);
        $this->assertGreaterThanOrEqual(0, $duration);
        $this->assertEquals([
            'db.system.name' => 'redis',
            'redis.client.library' => 'predis:' . Client::VERSION,
            'db.operation.name' => 'GET',
            'db.namespace' => '5',
            'server.address' => 'redis.example',
            'server.port' => 6380,
            'redis.client.operation.retry_attempts' => 0,
        ], $attributes);
    }

    /**
     * @group disconnected
     */
    public function testRecordsServerErrorThrownAsException(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn(new Error('WRONGTYPE Operation against a key'));

        try {
            (new Client($connection))->get('foo');
            $this->fail('Expected a ServerException');
        } catch (ServerException $exception) {
            // Expected.
        }

        $attributes = $this->records[0][1];

        $this->assertSame('ServerException', $attributes['error.type']);
        $this->assertSame('WRONGTYPE', $attributes['db.response.status_code']);
        $this->assertSame('server', $attributes['redis.client.errors.category']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsServerErrorReturnedAsResponse(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn(new Error('NOAUTH Authentication required.'));

        $response = (new Client($connection, ['exceptions' => false]))->get('foo');

        $this->assertInstanceOf(Error::class, $response);
        $this->assertSame('NOAUTH', $this->records[0][1]['db.response.status_code']);
        $this->assertSame('auth', $this->records[0][1]['redis.client.errors.category']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsRetryAttemptsOfCommandThatEventuallySucceeds(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection(['retry' => new Retry(new NoBackoff(), 3)]);
        $connection
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ConnectionException($connection, 'boom')),
                $this->throwException(new ConnectionException($connection, 'boom')),
                'bar'
            );

        $this->assertSame('bar', (new Client($connection))->get('foo'));

        $this->assertCount(1, $this->records);
        $this->assertSame(2, $this->records[0][1]['redis.client.operation.retry_attempts']);
        $this->assertArrayNotHasKey('error.type', $this->records[0][1]);
    }

    /**
     * @group disconnected
     */
    public function testRecordsNetworkErrorAndResetsRetryAttemptsBetweenOperations(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection(['retry' => new Retry(new NoBackoff(), 1)]);
        $connection
            ->method('executeCommand')
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new ConnectionException($connection, 'boom')),
                $this->throwException(new ConnectionException($connection, 'boom')),
                'bar'
            );
        $client = new Client($connection);

        try {
            $client->get('foo');
            $this->fail('Expected a ConnectionException');
        } catch (ConnectionException $exception) {
            // Expected.
        }

        $client->get('foo');

        $failed = $this->records[0][1];
        $this->assertSame('ConnectionException', $failed['error.type']);
        $this->assertSame('network', $failed['redis.client.errors.category']);
        $this->assertSame(1, $failed['redis.client.operation.retry_attempts']);
        $this->assertArrayNotHasKey('db.response.status_code', $failed);
        $this->assertSame(0, $this->records[1][1]['redis.client.operation.retry_attempts']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsOtherErrors(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willThrowException(new RuntimeException('boom'));

        try {
            (new Client($connection))->get('foo');
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $exception) {
            // Expected.
        }

        $this->assertSame('RuntimeException', $this->records[0][1]['error.type']);
        $this->assertSame('other', $this->records[0][1]['redis.client.errors.category']);
    }

    /**
     * @group disconnected
     */
    public function testHonorsCommandFilters(): void
    {
        $this->enableCommandMetrics(['exclude_commands' => ['get']]);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn('bar');
        $client = new Client($connection);

        $client->get('foo');
        $client->echo('foo');

        $this->assertCount(1, $this->records);
        $this->assertSame('ECHO', $this->records[0][1]['db.operation.name']);
    }

    /**
     * @group disconnected
     */
    public function testDoesNotRecordWhenInstrumentIsDisabled(): void
    {
        $this->enableCommandMetrics([], false);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn('bar');

        $this->assertSame('bar', (new Client($connection))->get('foo'));
        $this->assertSame([], $this->records);
    }

    /**
     * @group disconnected
     */
    public function testRecordingFailureDoesNotBreakCommand(): void
    {
        $histogram = $this->createMock(HistogramInterface::class);
        $histogram->method('isEnabled')->willReturn(true);
        $histogram->expects($this->once())->method('record')->willThrowException(new RuntimeException('boom'));
        $this->initObservability($histogram, []);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn('bar');

        $this->assertSame('bar', (new Client($connection))->get('foo'));
    }

    /**
     * @group disconnected
     */
    public function testRecordsRawCommand(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('executeCommand')->willReturn(new Error('ERR unknown command'));

        $response = (new Client($connection))->executeRaw(['FOO', 'bar'], $error);

        $this->assertTrue($error);
        $this->assertSame('ERR unknown command', $response);
        $this->assertSame('FOO', $this->records[0][1]['db.operation.name']);
        $this->assertSame('ERR', $this->records[0][1]['db.response.status_code']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsUnixSocketAndPeerAddress(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockBuilder(StreamConnection::class)->disableOriginalConstructor()->getMock();
        $connection->method('getParameters')->willReturn($this->getParameters(['scheme' => 'unix', 'path' => '/tmp/redis.sock']));
        $connection->method('getPeerName')->willReturn('[::1]:6379');
        $connection->method('executeCommand')->willReturn('bar');

        (new Client($connection))->get('foo');

        $attributes = $this->records[0][1];
        $this->assertSame('/tmp/redis.sock', $attributes['server.address']);
        $this->assertArrayNotHasKey('server.port', $attributes);
        $this->assertSame('::1', $attributes['network.peer.address']);
        $this->assertSame(6379, $attributes['network.peer.port']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsConfiguredAddressAsPeerWithRelay(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockBuilder(RelayConnection::class)->disableOriginalConstructor()->getMock();
        $connection->method('getParameters')->willReturn($this->getParameters(['host' => 'redis.local', 'port' => 6380]));
        $connection->method('executeCommand')->willReturn('bar');

        (new Client($connection))->get('foo');

        $attributes = $this->records[0][1];
        $this->assertSame('redis.local', $attributes['network.peer.address']);
        $this->assertSame(6380, $attributes['network.peer.port']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsPipelineAsSingleOperation(): void
    {
        $this->enableCommandMetrics();

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('readResponse')->willReturnOnConsecutiveCalls(new Status('OK'), 'bar');

        $responses = (new Client($connection))->pipeline(function ($pipe) {
            $pipe->set('foo', 'bar');
            $pipe->get('foo');
        });

        $this->assertCount(2, $responses);
        $this->assertCount(1, $this->records);
        $this->assertSame('PIPELINE', $this->records[0][1]['db.operation.name']);
        $this->assertSame(2, $this->records[0][1]['db.operation.batch.size']);
        $this->assertSame('127.0.0.1', $this->records[0][1]['server.address']);
    }

    /**
     * @group disconnected
     */
    public function testRecordsTransactionAsSingleOperation(): void
    {
        $this->enableCommandMetrics(['include_commands' => ['MULTI']]);

        $connection = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection->method('isConnected')->willReturn(true);
        $connection
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) {
                switch ($command->getId()) {
                    case 'MULTI':
                        return new Status('OK');
                    case 'EXEC':
                        return [new Status('OK'), 'bar'];
                    default:
                        return new Status('QUEUED');
                }
            });

        $responses = (new Client($connection))->transaction(function ($tx) {
            $tx->set('foo', 'bar');
            $tx->get('foo');
        });

        $this->assertCount(2, $responses);
        $this->assertCount(1, $this->records);
        $this->assertSame('MULTI', $this->records[0][1]['db.operation.name']);
        $this->assertSame(2, $this->records[0][1]['db.operation.batch.size']);
    }

    /**
     * Enables the command group with a histogram that stores what is recorded on it.
     *
     * @param array $options Additional metrics options.
     * @param bool  $enabled Value returned by the instrument's isEnabled().
     */
    private function enableCommandMetrics(array $options = [], bool $enabled = true): void
    {
        $histogram = $this->createMock(HistogramInterface::class);
        $histogram->method('isEnabled')->willReturn($enabled);
        $histogram->method('record')->willReturnCallback(function ($amount, $attributes) {
            $this->records[] = [$amount, $attributes];
        });

        $this->initObservability($histogram, $options);
    }

    private function initObservability(HistogramInterface $histogram, array $options): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter->method('createHistogram')->willReturn($histogram);
        $provider = $this->createMock(MeterProviderInterface::class);
        $provider->method('getMeter')->willReturn($meter);

        ObservabilityInstance::getInstance()->init(new OTelConfig(
            new MetricsConfig($options + ['enabled' => true, 'enabled_metric_groups' => [MetricGroup::COMMAND]]),
            $provider
        ));
    }
}
