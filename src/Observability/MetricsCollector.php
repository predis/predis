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
use Predis\Client;

/**
 * Owns the OpenTelemetry instruments of the enabled metric groups and records measurements on them.
 */
class MetricsCollector
{
    public const METER_NAME = 'predis';

    /**
     * @var MeterInterface
     */
    protected $meter;

    /**
     * @var MetricsConfig
     */
    protected $config;

    /**
     * @param MeterInterface $meter
     * @param MetricsConfig  $config
     */
    public function __construct(MeterInterface $meter, MetricsConfig $config)
    {
        $this->meter = $meter;
        $this->config = $config;
    }

    /**
     * @return MetricsConfig
     */
    public function getConfig(): MetricsConfig
    {
        return $this->config;
    }

    /**
     * Completes metric specific attributes with the ones shared by every metric.
     * Custom attributes are applied last, so they win over built-in ones.
     *
     * @param  array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function buildAttributes(array $attributes = []): array
    {
        return $this->config->getCustomAttributes() + $attributes + [
            'db.system.name' => 'redis',
            'redis.client.library' => 'predis:' . Client::VERSION,
        ];
    }
}
