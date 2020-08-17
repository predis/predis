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
class AggregateTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Aggregate();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer()
    {
        $option = new Aggregate();

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
     * @expectedExceptionMessage Predis\Configuration\Option\Aggregate expects a valid connection type returned by callable initializer
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer()
    {
        $option = new Aggregate();

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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Aggregate expects a valid callable
     */
    public function testThrowsExceptionOnInstanceOfAggregateConnectionInterface()
    {
        $option = new Aggregate();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $cluster = $this->getMock('Predis\Connection\AggregateConnectionInterface');

        $option->filter($options, $cluster);
    }
}
