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
use Predis\Connection\Parameters;
use Predis\Connection\Resource\Exception\StreamInitException;
use Predis\Connection\Resource\StreamFactoryInterface;
use Predis\Connection\StreamConnection;
use PredisTestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Covers the db.client.connection.create_time metric of the connection-basic group.
 */
class ConnectionMetricsTest extends PredisTestCase
{
    /**
     * Attributes of the measurements recorded on db.client.connection.create_time.
     *
     * @var array
     */
    private $createTimes = [];

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
    public function testCreatesHistogramOnlyWhenConnectionBasicGroupIsEnabled(): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter
            ->expects($this->once())
            ->method('createHistogram')
            ->with('db.client.connection.create_time', 's', $this->isType('string'), ['ExplicitBucketBoundaries' => [0.1, 1]])
            ->willReturn($this->createMock(HistogramInterface::class));

        new MetricsCollector($meter, new MetricsConfig([
            'enabled_metric_groups' => [MetricGroup::CONNECTION_BASIC],
            'buckets_connection_create_time' => [0.1, 1],
        ]));

        $meter = $this->createMock(MeterInterface::class);
        $meter->expects($this->never())->method('createHistogram');

        $collector = new MetricsCollector($meter, new MetricsConfig(['enabled_metric_groups' => [MetricGroup::RESILIENCY]]));

        $this->assertNull($collector->startConnectionTimer());
    }

    /**
     * @group disconnected
     */
    public function testRecordsCreateTimeEachTimeConnectionIsOpened(): void
    {
        $this->enableConnectionMetrics();

        $connection = $this->createConnection('tcp://redis.example:6380?database=5');
        $connection->connect();
        $connection->connect();

        $attributes = [
            'db.system.name' => 'redis',
            'redis.client.library' => 'predis:' . Client::VERSION,
            'server.address' => 'redis.example',
            'server.port' => 6380,
            'db.namespace' => '5',
        ];

        $this->assertEquals([$attributes], $this->createTimes);

        $connection->disconnect();
        $connection->connect();

        $this->assertEquals([$attributes, $attributes], $this->createTimes);
    }

    /**
     * @group disconnected
     */
    public function testIgnoresConnectionThatFailedToOpen(): void
    {
        $this->enableConnectionMetrics();

        $factory = $this->createMock(StreamFactoryInterface::class);
        $factory->method('createStream')->willThrowException(new StreamInitException('refused'));
        $connection = new StreamConnection($this->getParameters(), $factory);

        try {
            $connection->connect();
            $this->fail('Expected a StreamInitException');
        } catch (StreamInitException $exception) {
            // Expected.
        }

        $this->assertSame([], $this->createTimes);
    }

    /**
     * @group disconnected
     */
    public function testIgnoresConnectionsWhenGroupIsDisabled(): void
    {
        $this->enableConnectionMetrics([MetricGroup::RESILIENCY]);

        $this->createConnection('tcp://127.0.0.1:6379')->connect();

        $this->assertSame([], $this->createTimes);
    }

    /**
     * @group disconnected
     */
    public function testRecordingFailureDoesNotBreakConnection(): void
    {
        $histogram = $this->createMock(HistogramInterface::class);
        $histogram->method('isEnabled')->willReturn(true);
        $histogram->expects($this->once())->method('record')->willThrowException(new RuntimeException('exporter failure'));
        $this->initObservability($histogram, [MetricGroup::CONNECTION_BASIC]);

        $connection = $this->createConnection('tcp://127.0.0.1:6379');
        $connection->connect();

        $this->assertTrue($connection->isConnected());
    }

    /**
     * Returns a connection whose stream never reaches a server.
     */
    private function createConnection(string $uri): StreamConnection
    {
        $factory = $this->createMock(StreamFactoryInterface::class);
        $factory->method('createStream')->willReturnCallback(function () {
            return $this->createMock(StreamInterface::class);
        });

        return new StreamConnection(Parameters::create($uri), $factory);
    }

    /**
     * Enables metrics with a histogram that stores what is recorded on it.
     *
     * @param string[] $groups
     */
    private function enableConnectionMetrics(array $groups = [MetricGroup::CONNECTION_BASIC]): void
    {
        $histogram = $this->createMock(HistogramInterface::class);
        $histogram->method('isEnabled')->willReturn(true);
        $histogram->method('record')->willReturnCallback(function ($amount, $attributes) {
            $this->createTimes[] = $attributes;
        });

        $this->initObservability($histogram, $groups);
    }

    private function initObservability(HistogramInterface $histogram, array $groups): void
    {
        $meter = $this->createMock(MeterInterface::class);
        $meter->method('createHistogram')->willReturn($histogram);
        $provider = $this->createMock(MeterProviderInterface::class);
        $provider->method('getMeter')->willReturn($meter);

        ObservabilityInstance::getInstance()->init(new OTelConfig(
            new MetricsConfig(['enabled' => true, 'enabled_metric_groups' => $groups]),
            $provider
        ));
    }
}
