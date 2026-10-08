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

namespace Predis\Pipeline;

use Predis\Client;
use Predis\Command\Redis\ECHO_;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use PredisTestCase;
use RuntimeException;
use Throwable;
use TypeError;

class PipelineCleanupTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @dataProvider providePipelineFailures
     */
    public function testDisconnectsAfterFailureAndDiscardsRemainingCommands(
        string $connectionClass,
        string $method,
        Throwable $failure
    ): void {
        $connection = $this->getMockBuilder($connectionClass)->getMock();
        $connection->method('getParameters')->willReturn(new Parameters());
        $connection->method('isConnected')->willReturn(true);
        $connection->expects($this->once())->method('disconnect');
        $connection->expects($this->once())->method($method)->willThrowException($failure);

        $pipeline = new Pipeline(new Client($connection));
        $pipeline->echo('first');
        $pipeline->exists('missing');

        try {
            $pipeline->execute();
            $this->fail('Expected pipeline execution to fail.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }

        // Reusing the context must not replay commands already sent to Redis.
        $this->assertSame([], $pipeline->execute());
    }

    public function providePipelineFailures(): array
    {
        $failures = [];

        foreach ([RuntimeException::class, TypeError::class] as $failureClass) {
            foreach (['write', 'readResponse'] as $method) {
                $failures[] = [NodeConnectionInterface::class, $method, new $failureClass('Interrupted pipeline')];
            }

            $failures[] = [ClusterInterface::class, 'getConnectionByCommand', new $failureClass('Interrupted pipeline')];
        }

        return $failures;
    }

    /**
     * @group disconnected
     */
    public function testPreservesOriginalFailureWhenDisconnectFails(): void
    {
        $failure = new RuntimeException('Interrupted pipeline');
        $connection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $connection->method('getParameters')->willReturn(new Parameters());
        $connection->method('isConnected')->willReturn(true);
        $connection->method('write')->willThrowException($failure);
        $connection->expects($this->once())->method('disconnect')
            ->willThrowException(new RuntimeException('Disconnect failed'));

        $pipeline = new Pipeline(new Client($connection));
        $pipeline->ping();

        try {
            $pipeline->flushPipeline();
            $this->fail('Expected pipeline execution to fail.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }
    }

    /**
     * @group disconnected
     */
    public function testResetsRunningStateAfterErrorInCallback(): void
    {
        $failure = new TypeError('Callback failed');
        $connection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $connection->expects($this->never())->method('disconnect');
        $connection->expects($this->never())->method('write');
        $pipeline = new Pipeline(new Client($connection));

        try {
            $pipeline->execute(static function () use ($failure) {
                throw $failure;
            });
            $this->fail('Expected callback to fail.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertSame([], $pipeline->execute());
    }

    /**
     * @group connected
     * @requiresRedisVersion >= 2.0.0
     * @dataProvider providePipelineConnections
     */
    public function testClientReceivesCorrectRepliesAfterParserFailure(string $pipelineClass, bool $persistent): void
    {
        $parameters = $this->getParameters(['persistent' => $persistent]);
        $client = new Client($parameters, ['connections' => 'default']);
        $prefix = 'pipeline-cleanup:' . bin2hex(random_bytes(8)) . ':';
        $hash = $prefix . 'hash';
        $sorted = $prefix . 'sorted';
        $client->hmset($hash, ['field' => 'value']);
        $client->zadd($sorted, ['member' => 1]);
        $failure = new TypeError('Response parser failed');

        $command = $this->getMockBuilder(ECHO_::class)->onlyMethods(['parseResponse'])->getMock();
        $command->setArguments(['first']);
        $command->expects($this->once())->method('parseResponse')->willThrowException($failure);

        $pipeline = new $pipelineClass($client);
        $pipeline->executeCommand($command);
        $pipeline->exists($prefix . 'missing');

        try {
            $pipeline->execute();
            $this->fail('Expected response parsing to fail.');
        } catch (Throwable $exception) {
            $this->assertSame($failure, $exception);
        }

        $this->assertFalse($client->isConnected());
        $this->assertSame(['field' => 'value'], $client->hgetall($hash));
        $this->assertSame(['member' => '1'], $client->zrange($sorted, 0, -1, ['withscores' => true]));
        $pipeline->echo('next');
        $this->assertSame(['next'], $pipeline->execute());
        $client->del([$hash, $sorted]);
        $client->disconnect();
    }

    public function providePipelineConnections(): array
    {
        return [
            [Pipeline::class, false],
            [Pipeline::class, true],
            [Atomic::class, false],
            [Atomic::class, true],
        ];
    }
}
