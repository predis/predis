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

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MetricsConfigTest extends TestCase
{
    /**
     * @group disconnected
     */
    public function testDefaults(): void
    {
        $config = new MetricsConfig();

        $this->assertFalse($config->isEnabled());
        $this->assertTrue($config->isGroupEnabled(MetricGroup::CONNECTION_BASIC));
        $this->assertTrue($config->isGroupEnabled(MetricGroup::RESILIENCY));
        $this->assertFalse($config->isGroupEnabled(MetricGroup::COMMAND));
        $this->assertFalse($config->hidePubSubChannelNames());
        $this->assertFalse($config->hideStreamNames());
        $this->assertSame(MetricsConfig::DEFAULT_BUCKETS_OPERATION_DURATION, $config->getBucketsOperationDuration());
        $this->assertSame(MetricsConfig::DEFAULT_BUCKETS, $config->getBucketsStreamProcessingDuration());
        $this->assertSame(MetricsConfig::DEFAULT_BUCKETS, $config->getBucketsConnectionCreateTime());
        $this->assertSame([], $config->getCustomAttributes());
        $this->assertTrue($config->shouldTrackCommand('GET'));
    }

    /**
     * @group disconnected
     */
    public function testOverridesDefaults(): void
    {
        $config = new MetricsConfig([
            'enabled' => true,
            'enabled_metric_groups' => [MetricGroup::COMMAND],
            'hide_pubsub_channel_names' => true,
            'hide_stream_names' => true,
            'buckets_operation_duration' => [0.1, 1],
            'custom_attributes' => ['env' => 'test'],
        ]);

        $this->assertTrue($config->isEnabled());
        $this->assertTrue($config->isGroupEnabled(MetricGroup::COMMAND));
        $this->assertFalse($config->isGroupEnabled(MetricGroup::RESILIENCY));
        $this->assertTrue($config->hidePubSubChannelNames());
        $this->assertTrue($config->hideStreamNames());
        $this->assertSame([0.1, 1], $config->getBucketsOperationDuration());
        $this->assertSame(['env' => 'test'], $config->getCustomAttributes());
    }

    /**
     * @group disconnected
     */
    public function testIncludeCommandsIsCaseInsensitiveAndWinsOverExclude(): void
    {
        $config = new MetricsConfig(['include_commands' => ['get'], 'exclude_commands' => ['GET', 'SET']]);

        $this->assertTrue($config->shouldTrackCommand('GET'));
        $this->assertTrue($config->shouldTrackCommand('get'));
        $this->assertFalse($config->shouldTrackCommand('SET'));
        $this->assertFalse($config->shouldTrackCommand('DEL'));
    }

    /**
     * @group disconnected
     */
    public function testExcludeCommands(): void
    {
        $config = new MetricsConfig(['exclude_commands' => ['debug']]);

        $this->assertFalse($config->shouldTrackCommand('DEBUG'));
        $this->assertTrue($config->shouldTrackCommand('GET'));
    }

    /**
     * @group disconnected
     */
    public function testThrowsOnUnknownOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown metrics options: enable');

        new MetricsConfig(['enable' => true]);
    }

    /**
     * @group disconnected
     */
    public function testThrowsOnUnknownMetricGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown metric groups: client-side-caching');

        new MetricsConfig(['enabled_metric_groups' => [MetricGroup::COMMAND, 'client-side-caching']]);
    }
}
