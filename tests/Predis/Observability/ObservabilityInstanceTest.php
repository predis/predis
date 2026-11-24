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

use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Predis\NotSupportedException;

class ObservabilityInstanceTest extends TestCase
{
    protected function tearDown(): void
    {
        ObservabilityInstance::getInstance()->init(new OTelConfig());
    }

    /**
     * @group disconnected
     */
    public function testIsSingletonAndDisabledByDefault(): void
    {
        $instance = ObservabilityInstance::getInstance();

        $this->assertSame($instance, ObservabilityInstance::getInstance());
        $this->assertFalse($instance->isEnabled());
        $this->assertNull($instance->getCollector());
        $this->assertFalse($instance->forceFlush());
    }

    /**
     * @group disconnected
     */
    public function testInitWithDisabledMetricsKeepsObservabilityDisabled(): void
    {
        $instance = ObservabilityInstance::getInstance();

        $this->assertSame($instance, $instance->init(new OTelConfig()));
        $this->assertFalse($instance->isEnabled());
    }

    /**
     * @group disconnected
     */
    public function testInitThrowsWhenOpenTelemetryApiIsMissing(): void
    {
        if (interface_exists(MeterProviderInterface::class)) {
            $this->markTestSkipped('The OpenTelemetry API is installed.');
        }

        $this->expectException(NotSupportedException::class);

        ObservabilityInstance::getInstance()->init(new OTelConfig(new MetricsConfig(['enabled' => true])));
    }

    /**
     * @group disconnected
     */
    public function testInitCreatesCollectorFromGivenMeterProvider(): void
    {
        $this->skipWithoutOpenTelemetryApi();

        $metrics = new MetricsConfig(['enabled' => true]);
        $provider = $this->createMock(MeterProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getMeter')
            ->with('predis', Client::VERSION)
            ->willReturn($this->createMock(MeterInterface::class));

        $instance = ObservabilityInstance::getInstance()->init(new OTelConfig($metrics, $provider));

        $this->assertTrue($instance->isEnabled());
        $this->assertSame($metrics, $instance->getCollector()->getConfig());
    }

    /**
     * @group disconnected
     */
    public function testInitFallsBackToGlobalMeterProvider(): void
    {
        $this->skipWithoutOpenTelemetryApi();

        $instance = ObservabilityInstance::getInstance()->init(new OTelConfig(new MetricsConfig(['enabled' => true])));

        // No SDK is registered: the client records on the no-op provider of the API.
        $this->assertTrue($instance->isEnabled());
        $this->assertFalse($instance->forceFlush());
    }

    /**
     * @group disconnected
     */
    public function testInitReplacesPreviousConfiguration(): void
    {
        $this->skipWithoutOpenTelemetryApi();

        $instance = ObservabilityInstance::getInstance();
        $instance->init(new OTelConfig(new MetricsConfig(['enabled' => true])));
        $first = $instance->getCollector();
        $instance->init(new OTelConfig(new MetricsConfig(['enabled' => true])));

        $this->assertNotSame($first, $instance->getCollector());

        $instance->init(new OTelConfig());

        $this->assertFalse($instance->isEnabled());
    }

    /**
     * @group disconnected
     */
    public function testShutdownFlushesProviderAndDisablesObservability(): void
    {
        $this->skipWithoutOpenTelemetryApi();

        // forceFlush() is offered by SDK providers only, it is not part of the API interface.
        $provider = $this->getMockBuilder(MeterProviderInterface::class)
            ->onlyMethods(['getMeter'])
            ->addMethods(['forceFlush'])
            ->getMock();
        $provider->method('getMeter')->willReturn($this->createMock(MeterInterface::class));
        $provider->expects($this->exactly(2))->method('forceFlush')->willReturn(true);

        $instance = ObservabilityInstance::getInstance();
        $instance->init(new OTelConfig(new MetricsConfig(['enabled' => true]), $provider));

        $this->assertTrue($instance->forceFlush());
        $this->assertTrue($instance->shutdown());
        $this->assertFalse($instance->isEnabled());
        $this->assertFalse($instance->shutdown());
    }

    private function skipWithoutOpenTelemetryApi(): void
    {
        if (!interface_exists(MeterProviderInterface::class)) {
            $this->markTestSkipped('The OpenTelemetry API is not installed.');
        }
    }
}
