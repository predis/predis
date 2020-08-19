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

/**
 *
 */
class ReplicationTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($options));
    }

    /**
     * @group disconnected
     */
    public function testConfiguresAutomaticDiscoveryWhenAutodiscoveryOptionIsPresent()
    {
        $option = new Replication();

        $connectionFactory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->at(0))
            ->method('__get')
            ->with('autodiscovery')
            ->will($this->returnValue(true));
        $options
            ->expects($this->at(1))
            ->method('__get')
            ->with('connections')
            ->will($this->returnValue($connectionFactory));

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
    public function testAcceptsCallableAsConnectionInitializer()
    {
        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($connection));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid connection type returned by callable initializer');

        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($connection));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));

        $initializer($parameters = array());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringPredis()
    {
        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis()
    {
        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->at(0))
            ->method('__get')
            ->with('service')
            ->will($this->returnValue('mymaster'));
        $options
            ->expects($this->at(1))
            ->method('__get')
            ->with('connections')
            ->will($this->returnValue(
                $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            ));

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
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('String value for the replication option must be either `predis` or `sentinel`');

        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnBooleanValue()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid callable');

        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, true);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfReplicationInterface()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Replication expects a valid callable');

        $option = new Replication();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();

        $option->filter($options, $connection);
    }
}
