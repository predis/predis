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
class ClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Closure', $initializer = $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Cluster\PredisCluster', $initializer($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableAsConnectionInitializer(): void
    {
        $option = new Cluster();

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
        $this->expectExceptionMessage('Predis\Configuration\Option\Cluster expects a valid connection type returned by callable initializer');

        $option = new Cluster();

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
    public function testAcceptsShortNameStringPredis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
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
    public function testAcceptsShortNameStringRedis(): void
    {
        $option = new Cluster();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->at(0))
            ->method('__get')
            ->with('connections')
            ->will($this->returnValue(
                $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            ));
        $options
            ->expects($this->at(1))
            ->method('__get')
            ->with('crc16')
            ->will($this->returnValue(
                $this->getMockBuilder('Predis\Cluster\Hash\HashGeneratorInterface')->getMock()
            ));

        $this->assertInstanceOf('Closure', $initializer = $option->filter($options, 'redis'));
        $this->assertInstanceOf('Predis\Connection\Cluster\RedisCluster', $initializer($parameters = array()));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('String value for the cluster option must be either `predis` or `redis`');

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInstanceOfClusterInterface(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Cluster expects a valid callable');

        $option = new Cluster();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connection = $this->getMockBuilder('Predis\Connection\Cluster\ClusterInterface')->getMock();

        $option->filter($options, $connection);
    }
}
