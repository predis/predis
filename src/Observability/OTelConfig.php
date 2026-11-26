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

use OpenTelemetry\API\Metrics\MeterProviderInterface;

/**
 * Configuration of the OpenTelemetry signals emitted by the client.
 */
class OTelConfig
{
    /**
     * @var MetricsConfig
     */
    protected $metrics;

    /**
     * @var MeterProviderInterface|null
     */
    protected $meterProvider;

    /**
     * @param MetricsConfig|null          $metrics       Metrics configuration, disabled when omitted.
     * @param MeterProviderInterface|null $meterProvider Provider to use instead of the globally registered one.
     */
    public function __construct(?MetricsConfig $metrics = null, ?MeterProviderInterface $meterProvider = null)
    {
        $this->metrics = $metrics ?? new MetricsConfig();
        $this->meterProvider = $meterProvider;
    }

    /**
     * @return MetricsConfig
     */
    public function getMetrics(): MetricsConfig
    {
        return $this->metrics;
    }

    /**
     * @return MeterProviderInterface|null
     */
    public function getMeterProvider(): ?MeterProviderInterface
    {
        return $this->meterProvider;
    }
}
