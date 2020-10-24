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
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $option->getDefault($options));
        $this->assertInstanceOf('Predis\Connection\Aggregate\MasterSlaveReplication', $option->getDefault($options));
    }

    /**
     * @return array
     */
    public function provideValuesEvaluatingTrue()
    {
        return array(array(true), array(1), array('true'), array('on'));
    }

    /**
     * @group disconnected
     * @dataProvider provideValuesEvaluatingTrue
     */
    public function testAcceptsValuesThatCanBeInterpretedAsBooleanTrue($value)
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\Aggregate\MasterSlaveReplication', $option->filter($options, $value));
    }

    /**
     * @return array
     */
    public function provideValuesEvaluatingFalse()
    {
        return array(array(false), array(0), array('false'), array('off'));
    }

    /**
     * @group disconnected
     * @dataProvider provideValuesEvaluatingFalse
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Values evaluating to FALSE are not accepted for `replication`
     */
    public function testDoesNotAcceptValuesThatCanBeInterpretedAsBooleanFalse($value)
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, $value);
    }

    /**
     * @group disconnected
     */
    public function testConfiguresAutomaticDiscoveryWhenAutodiscoveryOptionIsPresent()
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $connFactory = $this->getMock('Predis\Connection\FactoryInterface');

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
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidInstanceType()
    {
        $option = new ReplicationOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $value = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $option->filter($options, $value);
    }
}
