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
use Predis\Configuration\OptionsInterface;

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
            ->will($this->returnValue($connection));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));
        $this->assertSame($connection, $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidReturnTypeOfConnectionInitializer(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Aggregate expects a valid connection type returned by callable initializer');

        $option = new Aggregate();

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
            ->will($this->returnValue($connection));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, $callable));

        $initializer($parameters = array());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfAggregateConnectionInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Aggregate expects a valid callable');

        $option = new Aggregate();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $cluster = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $option->filter($options, $cluster);
    }
}
