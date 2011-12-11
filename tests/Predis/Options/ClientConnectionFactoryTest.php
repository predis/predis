<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Options;

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\ConnectionFactory;

/**
 *
 */
class ClientConnectionFactoryTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testValidationReturnsDefaultFactoryWithSchemeDefinitionsArray()
    {
        $connectionClass = get_class($this->getMock('Predis\Network\IConnectionSingle'));
        $value = array('tcp' => $connectionClass, 'redis' => $connectionClass);

        $options = $this->getMock('Predis\Options\IClientOptions');

        $default = $this->getMock('Predis\IConnectionFactory');
        $default->expects($this->exactly(2))
                ->method('define')
                ->with($this->matchesRegularExpression('/^tcp|redis$/'), $connectionClass);

        $option = $this->getMock('Predis\Options\ClientConnectionFactory', array('getDefault'));
        $option->expects($this->once())
               ->method('getDefault')
               ->with($options)
               ->will($this->returnValue($default));

        $factory = $option->filter($options, $value);

        $this->assertInstanceOf('Predis\IConnectionFactory', $factory);
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testValidationAcceptsFactoryInstancesAsValue()
    {
        $value = $this->getMock('Predis\IConnectionFactory');
        $options = $this->getMock('Predis\Options\IClientOptions');

        $option = $this->getMock('Predis\Options\ClientConnectionFactory', array('getDefault'));
        $option->expects($this->never())->method('getDefault');

        $this->assertSame($value, $option->filter($options, $value));
    }

    /**
     * @group disconnected
     */
    public function testValidationAcceptsStringAsValue()
    {
        $factory = 'Predis\ConnectionFactory';
        $options = $this->getMock('Predis\Options\IClientOptions');

        $option = $this->getMock('Predis\Options\ClientConnectionFactory', array('getDefault'));
        $option->expects($this->never())->method('getDefault');

        $this->assertInstanceOf($factory, $option->filter($options, $factory));
    }

    /**
     * @group disconnected
     */
    public function testValidationThrowsExceptionOnWrongInvalidArguments()
    {
        $this->setExpectedException('InvalidArgumentException');

        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new ClientConnectionFactory();

        $option->filter($options, new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testInvokeReturnsSpecifiedFactoryOrDefault()
    {
        $value = $this->getMock('Predis\IConnectionFactory');
        $options = $this->getMock('Predis\Options\IClientOptions');

        $option = $this->getMock('Predis\Options\ClientConnectionFactory', array('filter', 'getDefault'));
        $option->expects($this->once())
               ->method('filter')
               ->with($options, $value)
               ->will($this->returnValue($value));
        $option->expects($this->never())->method('getDefault');

        $this->assertInstanceOf('Predis\IConnectionFactory', $option($options, $value));

        $option = $this->getMock('Predis\Options\ClientConnectionFactory', array('filter', 'getDefault'));
        $option->expects($this->never())->method('filter');
        $option->expects($this->once())
               ->method('getDefault')
               ->with($options)
               ->will($this->returnValue($value));

        $this->assertInstanceOf('Predis\IConnectionFactory', $option($options, null));
    }
}
