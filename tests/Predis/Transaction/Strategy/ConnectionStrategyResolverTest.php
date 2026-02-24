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

namespace Predis\Transaction\Strategy;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\ConnectionInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Transaction\MultiExecState;

class ConnectionStrategyResolverTest extends TestCase
{
    /**
     * @dataProvider connectionProvider
     * @param         $connection
     * @param  string $expectedStrategy
     * @return void
     */
    public function testResolve($connection, string $expectedStrategy): void
    {
        $resolver = new ConnectionStrategyResolver();

        $this->assertInstanceOf($expectedStrategy, $resolver->resolve($connection, new MultiExecState()));
    }

    /**
     * @return void
     */
    public function testResolveThrowsExceptionOnNonExistingCOnnection(): void
    {
        $connection = $this->getMockBuilder(ConnectionInterface::class)->getMock();
        $resolver = new ConnectionStrategyResolver();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve strategy associated with this connection type');

        $resolver->resolve($connection, new MultiExecState());
    }

    public function connectionProvider(): array
    {
        return [
            'with cluster connection' => [
                $this->getMockBuilder(ClusterInterface::class)->getMock(),
                ClusterConnectionStrategy::class,
            ],
            'with node connection' => [
                $this->getMockBuilder(NodeConnectionInterface::class)->getMock(),
                NodeConnectionStrategy::class,
            ],
            'with replication connection' => [
                $this->getMockBuilder(ReplicationInterface::class)->getMock(),
                ReplicationConnectionStrategy::class,
            ],
        ];
    }
}
