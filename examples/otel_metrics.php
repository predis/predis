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

// Exports Predis metrics to an OpenTelemetry collector over OTLP/HTTP.
//
// Requires PHP 8.2+ and:
//   composer require --dev open-telemetry/sdk open-telemetry/exporter-otlp guzzlehttp/guzzle
//
// Usage:
//   php examples/otel_metrics.php [redis-uri] [collector-endpoint] [seconds]
//   php examples/otel_metrics.php tcp://127.0.0.1:6381 http://127.0.0.1:4318 120
//
// The workload repeats for the given number of seconds (60 by default): dashboards built on
// rate() show nothing for counters that are exported only once.

use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use Predis\Client;
use Predis\Connection\Parameters;
use Predis\Observability\MetricGroup;
use Predis\Observability\MetricsConfig;
use Predis\Observability\ObservabilityInstance;
use Predis\Observability\OTelConfig;
use Predis\Response\ServerException;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\ExponentialBackoff;

require __DIR__ . '/../vendor/autoload.php';

$redis = $argv[1] ?? 'tcp://127.0.0.1:6381';
$collector = $argv[2] ?? 'http://127.0.0.1:4318';
$seconds = (int) ($argv[3] ?? 60);

$transport = (new OtlpHttpTransportFactory())->create($collector . '/v1/metrics', 'application/x-protobuf');

$provider = MeterProvider::builder()
    ->setResource(ResourceInfo::create(Attributes::create(['service.name' => 'predis-example'])))
    ->addReader(new ExportingReader(new MetricExporter($transport)))
    ->build();

ObservabilityInstance::getInstance()->init(new OTelConfig(
    new MetricsConfig([
        'enabled' => true,
        'enabled_metric_groups' => [MetricGroup::COMMAND, MetricGroup::RESILIENCY, MetricGroup::CONNECTION_BASIC],
    ]),
    $provider
));

$client = new Client(Parameters::parse($redis) + ['retry' => new Retry(new ExponentialBackoff(1000, 10000), 3)]);
$admin = new Client($redis);

echo "Exporting to $collector for $seconds seconds as service 'predis-example'", PHP_EOL;

$deadline = time() + $seconds;

do {
    $client->set('otel:string', 'bar');
    $client->get('otel:string');

    try {
        $client->lpush('otel:string', 'x');
    } catch (ServerException $exception) {
        // Expected: recorded as a failed operation.
    }

    $client->pipeline(function ($pipeline) {
        $pipeline->set('otel:counter', 1);
        $pipeline->incr('otel:counter');
        $pipeline->get('otel:counter');
    });

    $client->transaction(function ($transaction) {
        $transaction->incr('otel:counter');
    });

    // Closes the connection server-side: the next command fails once, then succeeds on retry
    // and is recorded with redis.client.operation.retry_attempts = 1.
    $admin->executeRaw(['CLIENT', 'KILL', 'ID', $client->executeRaw(['CLIENT', 'ID'])]);
    $client->get('otel:string');

    $client->del('otel:string', 'otel:counter');

    // PHP has no background exporter: nothing leaves the process until the provider is flushed.
    if (!ObservabilityInstance::getInstance()->forceFlush()) {
        echo 'Failed to export', PHP_EOL;
    }

    usleep(200000);
} while (time() < $deadline);

echo 'Done', PHP_EOL;
