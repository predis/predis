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

use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use Predis\Response\ServerException;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\NoBackoff;
use PredisTestCase;

/**
 * Exports metrics with the OpenTelemetry SDK over OTLP to a collector, then reads them back
 * from the Prometheus endpoint of the collector (see .github/otel-collector.yml).
 *
 * @group otel
 */
class OTelIntegrationTest extends PredisTestCase
{
    /**
     * @var string
     */
    private $runId;

    protected function setUp(): void
    {
        if (!class_exists(MetricExporter::class)) {
            $this->markTestSkipped('The OpenTelemetry SDK and OTLP exporter are not installed.');
        }

        // Applies @requiresRedisVersion.
        parent::setUp();

        // The collector keeps the series of previous runs, this label tells ours apart.
        $this->runId = uniqid('predis');
    }

    protected function tearDown(): void
    {
        ObservabilityInstance::getInstance()->init(new OTelConfig());
    }

    /**
     * @group connected
     */
    public function testExportsOperationDurationThroughCollector(): void
    {
        $client = $this->createClient();

        $this->initObservability([MetricGroup::COMMAND, MetricGroup::RESILIENCY]);

        $client->set('otel:string', 'bar');
        $client->get('otel:string');

        try {
            $client->lpush('otel:string', 'x');
            $this->fail('LPUSH on a string key should fail');
        } catch (ServerException $exception) {
            // Recorded as a failed operation.
        }

        $client->pipeline(function ($pipeline) {
            $pipeline->set('otel:counter', 1);
            $pipeline->incr('otel:counter');
            $pipeline->get('otel:counter');
        });

        $client->transaction(function ($transaction) {
            $transaction->incr('otel:counter');
        });

        $this->assertTrue(ObservabilityInstance::getInstance()->forceFlush());

        $name = 'db_client_operation_duration_seconds';
        $series = $this->scrape($name . '_count', 5);

        $node = [
            'db_system_name' => 'redis',
            'redis_client_library' => 'predis:' . \Predis\Client::VERSION,
            'db_namespace' => (string) constant('REDIS_SERVER_DBNUM'),
            'server_address' => constant('REDIS_SERVER_HOST'),
            'server_port' => (string) constant('REDIS_SERVER_PORT'),
            'network_peer_address' => constant('REDIS_SERVER_HOST'),
            'network_peer_port' => (string) constant('REDIS_SERVER_PORT'),
            'redis_client_operation_retry_attempts' => '0',
        ];

        $this->assertSeries($series, $name . '_count', ['db_operation_name' => 'SET'] + $node);
        $this->assertSeries($series, $name . '_count', ['db_operation_name' => 'GET'] + $node);
        $this->assertSeries($series, $name . '_count', [
            'db_operation_name' => 'LPUSH',
            'error_type' => 'ServerException',
            'db_response_status_code' => 'WRONGTYPE',
            'redis_client_errors_category' => 'server',
        ] + $node);
        $this->assertSeries($series, $name . '_count', ['db_operation_name' => 'PIPELINE', 'db_operation_batch_size' => '3'] + $node);
        $this->assertSeries($series, $name . '_count', ['db_operation_name' => 'MULTI', 'db_operation_batch_size' => '1'] + $node);

        // The bucket boundaries advised by Predis reach the backend, not the SDK defaults (0, 5, 10, ...).
        $this->assertSeries($series, $name . '_bucket', ['db_operation_name' => 'SET', 'le' => '0.0001']);
        $this->assertSeries($series, $name . '_bucket', ['db_operation_name' => 'SET', 'le' => '2.5']);

        // Only the failed LPUSH is counted as an error.
        $this->assertSeries($series, 'redis_client_errors_total', [
            'error_type' => 'ServerException',
            'db_response_status_code' => 'WRONGTYPE',
            'redis_client_errors_category' => 'server',
            'redis_client_errors_internal' => 'false',
            'server_address' => constant('REDIS_SERVER_HOST'),
        ]);
        $this->assertSeries($series, 'redis_client_errors_total', []);
    }

    /**
     * @group connected
     * @group relay-incompatible
     * @requiresRedisVersion >= 5.0.0
     */
    public function testExportsRetryAttemptsThroughCollector(): void
    {
        $client = $this->createClient(['retry' => new Retry(new NoBackoff(), 3)]);

        // Closes the connection server-side: the next command fails once, then succeeds on retry.
        $this->createClient()->executeRaw(['CLIENT', 'KILL', 'ID', $client->executeRaw(['CLIENT', 'ID'])]);

        $this->initObservability([MetricGroup::COMMAND, MetricGroup::RESILIENCY]);

        $this->assertNull($client->get('otel:missing'));
        $this->assertTrue(ObservabilityInstance::getInstance()->forceFlush());

        $name = 'db_client_operation_duration_seconds_count';
        $series = $this->scrape($name, 1);

        $this->assertSeries($series, $name, ['db_operation_name' => 'GET', 'redis_client_operation_retry_attempts' => '1']);
        $this->assertStringNotContainsString('error_type', implode("\n", preg_grep('/^db_client_operation_duration/', $series)));

        // The failure that was retried is counted as handled by the client.
        $this->assertSeries($series, 'redis_client_errors_total', [
            'redis_client_errors_category' => 'network',
            'redis_client_errors_internal' => 'true',
            'redis_client_operation_retry_attempts' => '0',
        ]);
    }

    /**
     * @group connected
     */
    public function testExportsConnectionMetricsThroughCollector(): void
    {
        $this->initObservability([MetricGroup::CONNECTION_BASIC]);

        $client = $this->createClient();
        $client->ping();

        $server = [
            'server_address' => constant('REDIS_SERVER_HOST'),
            'server_port' => (string) constant('REDIS_SERVER_PORT'),
            'db_namespace' => (string) constant('REDIS_SERVER_DBNUM'),
        ];

        $this->assertTrue(ObservabilityInstance::getInstance()->forceFlush());
        $series = $this->scrape('db_client_connection_create_time_seconds_count', 1);

        $this->assertSeries($series, 'db_client_connection_create_time_seconds_count', $server);
        // The bucket boundaries advised by Predis reach the backend.
        $this->assertSeries($series, 'db_client_connection_create_time_seconds_bucket', $server + ['le' => '0.0001']);
    }

    /**
     * Enables the given metric groups with a provider exporting to the collector.
     */
    private function initObservability(array $groups): void
    {
        $endpoint = getenv('OTEL_COLLECTOR_ENDPOINT') ?: 'http://127.0.0.1:4318';
        $transport = (new OtlpHttpTransportFactory())->create($endpoint . '/v1/metrics', 'application/x-protobuf');

        $provider = MeterProvider::builder()
            ->setResource(ResourceInfo::create(Attributes::create(['service.name' => 'predis-tests'])))
            ->addReader(new ExportingReader(new MetricExporter($transport)))
            ->build();

        ObservabilityInstance::getInstance()->init(new OTelConfig(
            new MetricsConfig([
                'enabled' => true,
                'enabled_metric_groups' => $groups,
                'custom_attributes' => ['test.run' => $this->runId],
            ]),
            $provider
        ));
    }

    /**
     * Returns the Prometheus series of this run, waiting for the collector to publish them.
     *
     * @return string[]
     */
    private function scrape(string $metric, int $expected): array
    {
        $endpoint = getenv('OTEL_PROMETHEUS_ENDPOINT') ?: 'http://127.0.0.1:8889/metrics';

        for ($attempt = 0; $attempt < 50; ++$attempt) {
            $series = array_values(array_filter(explode("\n", (string) @file_get_contents($endpoint)), function (string $line) {
                return strpos($line, 'test_run="' . $this->runId . '"') !== false;
            }));

            $found = array_filter($series, function (string $line) use ($metric) {
                return strpos($line, $metric . '{') === 0;
            });

            if (count($found) >= $expected) {
                return $series;
            }

            usleep(200000);
        }

        $this->fail("The collector did not publish $expected '$metric' series:\n" . implode("\n", $series));
    }

    /**
     * Asserts that exactly one series has the given name and labels.
     *
     * @param string[]              $series
     * @param array<string, string> $labels
     */
    private function assertSeries(array $series, string $name, array $labels): void
    {
        $matches = array_filter($series, function (string $line) use ($name, $labels) {
            if (strpos($line, $name . '{') !== 0) {
                return false;
            }

            foreach ($labels as $label => $value) {
                if (!preg_match('/[{,]' . $label . '="' . preg_quote($value, '/') . '"/', $line)) {
                    return false;
                }
            }

            return true;
        });

        $this->assertCount(1, $matches, "$name " . json_encode($labels) . " not found in:\n" . implode("\n", $series));
    }
}
