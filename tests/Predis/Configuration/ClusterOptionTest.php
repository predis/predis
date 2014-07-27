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
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsInstanceOfClusterInterface()
    {
        $option = new ClusterOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $cluster = $this->getMock('Predis\Connection\Aggregate\ClusterInterface');

        $this->assertSame($cluster, $option->filter($options, $cluster));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsPredefinedShortNameString()
    {
        $option = new ClusterOption();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $options->expects($this->any())
                ->method('__get')
                ->with('connections')
                ->will($this->returnValue(
                    $this->getMock('Predis\Connection\FactoryInterface')
                ));

        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->filter($options, 'predis'));
        $this->assertInstanceOf('Predis\Connection\Aggregate\PredisCluster', $option->filter($options, 'predis-cluster'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\RedisCluster', $option->filter($options, 'redis'));
        $this->assertInstanceOf('Predis\Connection\Aggregate\RedisCluster', $option->filter($options, 'redis-cluster'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidInstanceType()
    {
        $option = new ClusterOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $class = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $option->filter($options, $class);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidShortNameString()
    {
        $option = new ClusterOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, 'unknown');
    }
}
