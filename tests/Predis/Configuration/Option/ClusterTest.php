<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use PHPUnit\Framework\MockObject\MockObject;
use Predis\Cluster\Hash\HashGeneratorInterface;
use Predis\Configuration\OptionsInterface;
use Predis\Connection\AggregateConnectionInterface;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\Cluster\PredisCluster;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\FactoryInterface;
use Predis\Connection\NodeConnectionInterface;
use PredisTestCase;

class ClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf(PredisCluster::class, $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $connection = $this->getMockBuilder(AggregateConnectionInterface::class)->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperTriggersAggregationByDefault(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$parameters[0]],
                [$parameters[1]]
            )
            ->willReturnOnConsecutiveCalls(
                $nodeConnection1 = $this->getMockConnection($parameters[0]),
                $nodeConnection2 = $this->getMockConnection($parameters[1])
            );

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('connections')
            ->willReturn($factory);

        $connection = $this->getMockBuilder(AggregateConnectionInterface::class)->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperDoesNotTriggerAggregationWhenSecondArgumentIsFalse(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $options
            ->expects($this->never())
            ->method('__get')
            ->with('connections');

        $connection = $this->getMockBuilder(AggregateConnectionInterface::class)->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, false));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperTriggersAggregationWhenSecondArgumentIsTrue(): void
    {
        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [$parameters[0]],
                [$parameters[1]]
            )
            ->willReturnOnConsecutiveCalls(
                $nodeConnection1 = $this->getMockConnection($parameters[0]),
                $nodeConnection2 = $this->getMockConnection($parameters[1])
            );

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('connections')
            ->willReturn($factory);

        $connection = $this->getMockBuilder(AggregateConnectionInterface::class)->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperDoesNotTriggerAggregationWhenFirstArgumentIsEmptyAndSecondArgumentIsTrue(): void
    {
        $option = new Cluster();
        $parameters = [];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $options
            ->expects($this->never())
            ->method('__get')
            ->with('connections');

        $connection = $this->getMockBuilder(AggregateConnectionInterface::class)->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters, true));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches(
            '/^Predis\\\Configuration\\\Option\\\Cluster expects the supplied callable to return an instance of Predis\\\Connection\\\AggregateConnectionInterface, but .* was returned$/'
        );

        $option = new Cluster();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $connection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($parameters, $options, $option)
            ->willReturn($connection);

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, $callable));

        $initializer($parameters);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringPredis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf(PredisCluster::class, $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $options
            ->expects($this->exactly(4))
            ->method('__get')
            ->withConsecutive(
                ['loadBalancing'],
                ['connections'],
                ['crc16'],
                ['readTimeout']
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $this->getMockBuilder(FactoryInterface::class)->getMock(),
                $this->getMockBuilder(HashGeneratorInterface::class)->getMock(),
                1000
            );

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'redis'));
        $this->assertInstanceOf(RedisCluster::class, $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedisCluster(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $options
            ->expects($this->exactly(4))
            ->method('__get')
            ->withConsecutive(
                ['loadBalancing'],
                ['connections'],
                ['crc16'],
                ['readTimeout']
            )
            ->willReturnOnConsecutiveCalls(
                null,
                $this->getMockBuilder(FactoryInterface::class)->getMock(),
                $this->getMockBuilder(HashGeneratorInterface::class)->getMock(),
                1000
            );

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'redis-cluster'));
        $this->assertInstanceOf(RedisCluster::class, $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Predis\Configuration\Option\Cluster expects either `predis`, `redis` or `redis-cluster` as valid string values, `unknown` given'
        );

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfClusterInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches(
            '/Predis\\\Configuration\\\Option\\\Cluster expects either a string or a callable value, .* given/'
        );

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();
        $connection = $this->getMockBuilder(ClusterInterface::class)->getMock();

        $option->filter($options, $connection);
    }
}
