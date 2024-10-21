<?php

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Replication\ReplicationInterface;

class ConnectionStrategyResolverTest extends TestCase
{
    /**
     * @dataProvider connectionProvider
     * @param $connection
     * @param string $expectedStrategy
     * @return void
     */
    public function testResolve($connection, string $expectedStrategy): void
    {
        $resolver = new ConnectionStrategyResolver();

        $this->assertInstanceOf($expectedStrategy, $resolver->resolve($connection));
    }

    public function connectionProvider(): array
    {
        return [
            'with cluster connection' => [
                $this->getMockBuilder(ClusterInterface::class)->getMock(),
                ClusterConnectionStrategy::class
            ],
            'with node connection' => [
                $this->getMockBuilder(NodeConnectionInterface::class)->getMock(),
                NodeConnectionStrategy::class
            ],
            'with replication connection' => [
                $this->getMockBuilder(ReplicationInterface::class)->getMock(),
                ReplicationConnectionStrategy::class
            ],
        ];
    }
}
