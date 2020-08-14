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
class ReplicationOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ReplicationOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Aggregate\MasterSlaveReplication', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsValuesThatCanBeInterpretedAsBooleans()
    {
        $option = new ReplicationOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertNull($option->filter($options, null));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->filter($options, true));
        $this->assertNull($option->filter($options, false));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->filter($options, 1));
        $this->assertNull($option->filter($options, 0));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->filter($options, 'true'));
        $this->assertNull($option->filter($options, 'false'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->filter($options, 'on'));
        $this->assertNull($option->filter($options, 'off'));
    }

    /**
     * @group disconnected
     */
    public function testConfiguresAutomaticDiscoveryWhenAutodiscoveryOptionIsPresent()
    {
        $option = new ReplicationOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $connFactory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $options->expects($this->at(0))
                ->method('__get')
                ->with('autodiscovery')
                ->will($this->returnValue(true));
        $options->expects($this->at(1))
                ->method('__get')
                ->with('connections')
                ->will($this->returnValue($connFactory));

        $replication = $option->getDefault($options);

        // TODO: I know, I know...
        $reflection = new \ReflectionProperty($replication, 'autoDiscovery');
        $reflection->setAccessible(true);

        $this->assertTrue($reflection->getValue($replication));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidInstanceType()
    {
        $this->expectException('InvalidArgumentException');

        $option = new ReplicationOption();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $value = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();

        $option->filter($options, $value);
    }
}
