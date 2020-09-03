<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use PredisTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Configuration\OptionsInterface;

/**
 *
 */
class ReplicationTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($parameters = []));
    }

    /**
     * @group disconnected
     */
    public function testConfiguresAutomaticDiscoveryWhenAutodiscoveryOptionIsPresent(): void
    {
        $option = new Replication();

        $connectionFactory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(
                array('autodiscovery'),
                array('connections')
            )
            ->willReturnOnConsecutiveCalls(
                true,
                $connectionFactory
            );

        $this->assertInstanceOf('closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $connection = $initializer([]));

        // TODO: I know, I know...
        $reflection = new \ReflectionProperty($connection, 'autoDiscovery');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->getValue($connection));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer(): void
    {
        $option = new Replication();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
        $option = new Replication();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
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
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('connections')
            ->willReturn($factory);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
        $option = new Replication();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->never())
            ->method('__get')
            ->with('connections');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
        $option = new Replication();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
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
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('connections')
            ->willReturn($factory);

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                [$nodeConnection1],
                [$nodeConnection2]
            );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
        $option = new Replication();
        $parameters = [];

        /** @var MockObject|OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->never())
            ->method('__get')
            ->with('connections');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $connection
            ->expects($this->never())
            ->method('add');

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
            '/^Predis\\\Configuration\\\Option\\\Replication expects the supplied callable to return an instance of Predis\\\Connection\\\AggregateConnectionInterface, but .* was returned$/'
        );

        $option = new Replication();
        $parameters = ['127.0.0.1:6379', '127.0.0.1:6380'];

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
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
        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringSentinel(): void
    {
        $option = new Replication();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->exactly(2))
            ->method('__get')
            ->withConsecutive(
                array('service'),
                array('connections')
            )
            ->willReturnOnConsecutiveCalls(
                'mymaster',
                $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            );

        $parameters = array(
            $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock(),
        );

        $this->assertInstanceOf('closure', $initializer = $option->filter($options, 'sentinel'));
        $this->assertInstanceOf('Predis\Connection\Replication\SentinelReplication', $connection = $initializer($parameters));

        $this->assertSame($parameters[0], $connection->getSentinelConnection());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Predis\Configuration\Option\Replication expects either `predis`, `sentinel` or `redis-sentinel` as valid string values, `unknown` given'
        );

        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnBooleanValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'Predis\Configuration\Option\Replication expects either a string or a callable value, boolean given'
        );

        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, true);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfReplicationInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches(
            '/Predis\\\Configuration\\\Option\\\Replication expects either a string or a callable value, .* given/'
        );

        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();

        $option->filter($options, $connection);
    }
}
