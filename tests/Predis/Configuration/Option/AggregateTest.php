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

use PHPUnit\Framework\MockObject\MockObject;
use PredisTestCase;
use Predis\Configuration\OptionsInterface;

use function PHPSTORM_META\expectedArguments;

/**
 *
 */
class AggregateTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Aggregate();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer(): void
    {
        $option = new Aggregate();
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
    public function testReturnedCallableWrapperDoesNotTriggerAggregationByDefault(): void
    {
        $option = new Aggregate();
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
        $this->assertSame($connection, $initializer($parameters));
    }

    /**
     * @group disconnected
     */
    public function testReturnedCallableWrapperDoesNotTriggerAggregationWhenSecondArgumentIsFalse(): void
    {
        $option = new Aggregate();
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
        $option = new Aggregate();
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

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->getMock();
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
        $option = new Aggregate();
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
            '/^Predis\\\Configuration\\\Option\\\Aggregate expects the supplied callable to return an instance of Predis\\\Connection\\\AggregateConnectionInterface, but .* was returned$/'
        );

        $option = new Aggregate();
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
    public function testThrowsExceptionOnInstanceOfAggregateConnectionInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Aggregate expects a callable object acting as an aggregate connection initializer');

        $option = new Aggregate();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $option->filter($options, $connection);
    }

    /**
     * @group disconnected
     */
    public function ___AggregateConnectionSkipCreationOnConnectionInstance(): void
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        /** @var ClusterInterface|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
        $cluster
            ->expects($this->exactly(2))
            ->method('add')
            ->with($this->isInstanceOf('Predis\Connection\NodeConnectionInterface'));

        /** @var Factory|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\Factory')
        ->onlyMethods(array('create'))
        ->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $factory->aggregate($cluster, array(new $connectionClass(), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function ___AggregateConnectionWithMixedParameters(): void
    {
        list(, $connectionClass) = $this->getMockConnectionClass();

        /** @var ClusterInterface|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
        $cluster
            ->expects($this->exactly(4))
            ->method('add')
            ->with($this->isInstanceOf('Predis\Connection\NodeConnectionInterface'));

        /** @var Factory|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\Factory')
        ->onlyMethods(array('create'))
        ->getMock();
        $factory
            ->expects($this->exactly(3))
            ->method('create')
            ->willReturnCallback(function () use ($connectionClass) {
                return new $connectionClass();
            });

        $factory->aggregate($cluster, array(null, 'tcp://127.0.0.1', array('scheme' => 'tcp'), new $connectionClass()));
    }

    /**
     * @group disconnected
     */
    public function ___AggregateConnectionWithEmptyListOfParameters(): void
    {
        /** @var ClusterInterface|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();
        $cluster
            ->expects($this->never())
            ->method('add');

        /** @var Factory|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\Factory')
        ->onlyMethods(array('create'))
        ->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $factory->aggregate($cluster, array());
    }
}
