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

/**
 *
 */
class OptionTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testValidationReturnsTheSameObject()
    {
        $value = new \stdClass();
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new Option();

        $this->assertSame($value, $option->filter($options, $value));
    }

    /**
     * @group disconnected
     */
    public function testDefaultReturnsNull()
    {
        $options = $this->getMock('Predis\Options\IClientOptions');
        $option = new Option();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testInvokePerformsValidationWhenValueIsSet()
    {
        $value = new \stdClass();
        $options = $this->getMock('Predis\Options\IClientOptions');

        $option = $this->getMock('Predis\Options\Option', array('filter', 'getDefault'));
        $option->expects($this->once())
               ->method('filter')
               ->with($options, $value)
               ->will($this->returnValue($value));
        $option->expects($this->never())->method('getDefault');

        $this->assertSame($value, $option($options, $value));
    }

    /**
     * @group disconnected
     */
    public function testInvokeReturnsDefaultWhenValueIsNotSet()
    {
        $expected = new \stdClass();
        $options = $this->getMock('Predis\Options\IClientOptions');

        $option = $this->getMock('Predis\Options\Option', array('filter', 'getDefault'));
        $option->expects($this->never())->method('filter');
        $option->expects($this->once())
               ->method('getDefault')
               ->with($options)
               ->will($this->returnValue($expected));

        $this->assertSame($expected, $option($options, null));
    }
}
