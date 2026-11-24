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
use PHPUnit\Framework\TestCase;
use Predis\Client;

class MetricsCollectorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!interface_exists(MeterInterface::class)) {
            $this->markTestSkipped('The OpenTelemetry API is not installed.');
        }
    }

    /**
     * @group disconnected
     */
    public function testBuildAttributesAddsBaseAttributes(): void
    {
        $collector = new MetricsCollector($this->createMock(MeterInterface::class), new MetricsConfig());

        $this->assertSame(
            ['db.namespace' => '0', 'db.system.name' => 'redis', 'redis.client.library' => 'predis:' . Client::VERSION],
            $collector->buildAttributes(['db.namespace' => '0'])
        );
    }

    /**
     * @group disconnected
     */
    public function testCustomAttributesWinOverBuiltInOnes(): void
    {
        $config = new MetricsConfig(['custom_attributes' => ['env' => 'test', 'db.system.name' => 'other']]);
        $collector = new MetricsCollector($this->createMock(MeterInterface::class), $config);

        $attributes = $collector->buildAttributes(['db.namespace' => '0']);

        $this->assertSame('test', $attributes['env']);
        $this->assertSame('other', $attributes['db.system.name']);
        $this->assertSame('0', $attributes['db.namespace']);
        $this->assertSame('predis:' . Client::VERSION, $attributes['redis.client.library']);
    }
}
