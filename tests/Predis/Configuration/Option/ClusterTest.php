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
class ClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Cluster();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Cluster\PredisCluster', $initializer($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer()
    {
        $option = new Cluster();

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
     * @expectedExceptionMessage Predis\Configuration\Option\Cluster expects a valid connection type returned by callable initializer
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer()
    {
        $option = new Cluster();

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
        $option = new Cluster();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $options
            ->expects($this->never())
            ->method('__get')
            ->with('connections');

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Cluster\PredisCluster', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsShortNameStringRedis()
    {
        $option = new Cluster();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('connections')
            ->will($this->returnValue(
                $this->getMock('Predis\Connection\FactoryInterface')
            ));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'redis'));
        $this->assertInstanceOf('Predis\Connection\Cluster\RedisCluster', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage String value for the cluster option must be either `predis` or `redis`
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $option = new Cluster();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Cluster expects a valid callable
     */
    public function testThrowsExceptionOnInstanceOfClusterInterface()
    {
        $option = new Cluster();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $connection = $this->getMock('Predis\Connection\Cluster\ClusterInterface');

        $option->filter($options, $connection);
    }
}
