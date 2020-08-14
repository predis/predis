<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration;

use PredisTestCase;

/**
 *
 */
class ClusterOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ClusterOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsInstanceOfClusterInterface()
    {
        $option = new ClusterOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $cluster = $this->getMockBuilder('Predis\Connection\Aggregate\ClusterInterface')->getMock();

        $this->assertSame($cluster, $option->filter($options, $cluster));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsPredefinedShortNameString()
    {
        $option = new ClusterOption();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options->expects($this->any())
                ->method('__get')
                ->with('connections')
                ->will($this->returnValue(
                    $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
                ));

        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->filter($options, 'predis-cluster'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\RedisCluster', $option->filter($options, 'redis'));
        $this->assertInstanceOf('Predis\Connection\Aggregate\RedisCluster', $option->filter($options, 'redis-cluster'));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidInstanceType()
    {
        $this->expectException('InvalidArgumentException');

        $option = new ClusterOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $class = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $option->filter($options, $class);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $this->expectException('InvalidArgumentException');

        $option = new ClusterOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }
}
