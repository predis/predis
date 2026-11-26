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

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\MeterProviderInterface;
use Predis\Client;
use Predis\NotSupportedException;

/**
 * Process-wide entry point of the client observability.
 *
 * Applications call init() once during bootstrap, every client created in the
 * same process then emits telemetry according to the given configuration.
 */
final class ObservabilityInstance
{
    /**
     * @var self|null
     */
    private static $instance;

    /**
     * @var MeterProviderInterface|null
     */
    private $meterProvider;

    /**
     * @var MetricsCollector|null
     */
    private $collector;

    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    /**
     * Initializes observability for every client of the process, replacing any previous configuration.
     *
     * The client never creates an OpenTelemetry SDK on its own: metrics are recorded on the meter
     * provider given in the configuration or, by default, on the one registered globally by the application.
     *
     * @param  OTelConfig            $config
     * @return $this
     * @throws NotSupportedException when metrics are enabled without the OpenTelemetry API installed.
     */
    public function init(OTelConfig $config): self
    {
        $this->meterProvider = $this->collector = null;
        $metrics = $config->getMetrics();

        if (!$metrics->isEnabled()) {
            return $this;
        }

        if (!interface_exists(MeterProviderInterface::class)) {
            throw new NotSupportedException(
                'Metrics require the "open-telemetry/api" package (>= 1.1), available for PHP >= 8.1.'
            );
        }

        $this->meterProvider = $config->getMeterProvider() ?? Globals::meterProvider();
        $this->collector = new MetricsCollector(
            $this->meterProvider->getMeter(MetricsCollector::METER_NAME, Client::VERSION),
            $metrics
        );

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->collector !== null;
    }

    /**
     * Returns the collector used by the client to record metrics, NULL when metrics are disabled.
     *
     * @return MetricsCollector|null
     */
    public function getCollector(): ?MetricsCollector
    {
        return $this->collector;
    }

    /**
     * Exports the metrics recorded so far.
     *
     * PHP has no background exporter: metrics leave the process only when the meter provider is
     * flushed or shut down, so long-running workers should call this method periodically.
     *
     * @return bool
     */
    public function forceFlush(): bool
    {
        // Flushing is offered by SDK providers only, it is not part of the API interface.
        if ($this->meterProvider !== null && method_exists($this->meterProvider, 'forceFlush')) {
            return $this->meterProvider->forceFlush();
        }

        return false;
    }

    /**
     * Exports the metrics recorded so far and stops recording new ones.
     *
     * The meter provider belongs to the application and is left running.
     *
     * @return bool
     */
    public function shutdown(): bool
    {
        $flushed = $this->forceFlush();
        $this->meterProvider = $this->collector = null;

        return $flushed;
    }
}
