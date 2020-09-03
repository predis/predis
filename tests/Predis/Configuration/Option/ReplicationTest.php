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

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($options));
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

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $connection = $initializer($options));

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

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($connection);

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid connection type returned by callable initializer');

        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($connection);

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));

        $initializer($parameters = array());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringPredis(): void
    {
        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis(): void
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

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'sentinel'));
        $this->assertInstanceOf('Predis\Connection\Replication\SentinelReplication', $connection = $initializer($parameters));

        $this->assertSame($parameters[0], $connection->getSentinelConnection());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('String value for the replication option must be either `predis` or `sentinel`');

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
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid callable');

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
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid callable');

        $option = new Replication();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();

        $option->filter($options, $connection);
    }
}
