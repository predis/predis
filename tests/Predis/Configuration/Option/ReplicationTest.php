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

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($options));
    }

    /**
     * @group disconnected
     */
    public function testConfiguresAutomaticDiscoveryWhenAutodiscoveryOptionIsPresent()
    {
        $option = new Replication();

        $connectionFactory = $this->getMock('Predis\Connection\FactoryInterface');

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
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

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $connection = $this->getMock('Predis\Connection\AggregateConnectionInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Replication expects a valid connection type returned by callable initializer
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer()
    {
        $option = new Replication();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
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

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Replication\MasterSlaveReplication', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis()
    {
        $option = new Replication();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
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
                $this->getMock('Predis\Connection\FactoryInterface')
            ));

        $parameters = array(
            $this->getMock('Predis\Connection\NodeConnectionInterface'),
        );

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'sentinel'));
        $this->assertInstanceOf('Predis\Connection\Replication\SentinelReplication', $connection = $initializer($parameters));

        $this->assertSame($parameters[0], $connection->getSentinelConnection());
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage String value for the replication option must be either `predis` or `sentinel`
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $option = new Replication();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Replication expects a valid callable
     */
    public function testThrowsExceptionOnBooleanValue()
    {
        $option = new Replication();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, true);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Replication expects a valid callable
     */
    public function testThrowsExceptionOnInstanceOfReplicationInterface()
    {
        $option = new Replication();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $connection = $this->getMock('Predis\Connection\Cluster\ClusterInterface');

        $option->filter($options, $connection);
    }
}
