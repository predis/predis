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

/**
 * Configuration of the metrics emitted by the client.
 */
class MetricsConfig
{
    public const DEFAULT_BUCKETS_OPERATION_DURATION = [
        0.0001, 0.00025, 0.0005, 0.001, 0.0025, 0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5,
    ];

    public const DEFAULT_BUCKETS = [0.0001, 0.0005, 0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1, 5, 10];

    /**
     * @var array
     */
    protected $options = [
        'enabled' => false,
        'enabled_metric_groups' => [MetricGroup::CONNECTION_BASIC, MetricGroup::RESILIENCY],
        'include_commands' => [],
        'exclude_commands' => [],
        'hide_pubsub_channel_names' => false,
        'hide_stream_names' => false,
        'buckets_operation_duration' => self::DEFAULT_BUCKETS_OPERATION_DURATION,
        'buckets_stream_processing_duration' => self::DEFAULT_BUCKETS,
        'buckets_connection_create_time' => self::DEFAULT_BUCKETS,
        'custom_attributes' => [],
    ];

    /**
     * Supported options:
     *
     *  - enabled (bool): emit metrics, default false.
     *  - enabled_metric_groups (string[]): MetricGroup constants, default connection-basic and resiliency.
     *  - include_commands (string[]): only these commands are tracked by command metrics.
     *  - exclude_commands (string[]): these commands are not tracked by command metrics.
     *  - hide_pubsub_channel_names (bool): omit the channel name from Pub/Sub metrics.
     *  - hide_stream_names (bool): omit the stream name from stream metrics.
     *  - buckets_operation_duration (float[]): bucket boundaries in seconds.
     *  - buckets_stream_processing_duration (float[]): bucket boundaries in seconds.
     *  - buckets_connection_create_time (float[]): bucket boundaries in seconds.
     *  - custom_attributes (array<string, mixed>): attributes added to every metric.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if ($unknown = array_diff_key($options, $this->options)) {
            throw new InvalidArgumentException('Unknown metrics options: ' . implode(', ', array_keys($unknown)));
        }

        $options += $this->options;

        if ($unknown = array_diff($options['enabled_metric_groups'], MetricGroup::ALL)) {
            throw new InvalidArgumentException('Unknown metric groups: ' . implode(', ', $unknown));
        }

        $options['include_commands'] = array_map('strtoupper', $options['include_commands']);
        $options['exclude_commands'] = array_map('strtoupper', $options['exclude_commands']);

        $this->options = $options;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->options['enabled'];
    }

    /**
     * @param  string $group One of the MetricGroup constants.
     * @return bool
     */
    public function isGroupEnabled(string $group): bool
    {
        return in_array($group, $this->options['enabled_metric_groups'], true);
    }

    /**
     * Applies the allow and deny lists, the allow list takes precedence.
     *
     * @param  string $commandId
     * @return bool
     */
    public function shouldTrackCommand(string $commandId): bool
    {
        $commandId = strtoupper($commandId);

        if ($this->options['include_commands']) {
            return in_array($commandId, $this->options['include_commands'], true);
        }

        return !in_array($commandId, $this->options['exclude_commands'], true);
    }

    /**
     * @return bool
     */
    public function hidePubSubChannelNames(): bool
    {
        return (bool) $this->options['hide_pubsub_channel_names'];
    }

    /**
     * @return bool
     */
    public function hideStreamNames(): bool
    {
        return (bool) $this->options['hide_stream_names'];
    }

    /**
     * @return float[]
     */
    public function getBucketsOperationDuration(): array
    {
        return $this->options['buckets_operation_duration'];
    }

    /**
     * @return float[]
     */
    public function getBucketsStreamProcessingDuration(): array
    {
        return $this->options['buckets_stream_processing_duration'];
    }

    /**
     * @return float[]
     */
    public function getBucketsConnectionCreateTime(): array
    {
        return $this->options['buckets_connection_create_time'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomAttributes(): array
    {
        return $this->options['custom_attributes'];
    }
}
