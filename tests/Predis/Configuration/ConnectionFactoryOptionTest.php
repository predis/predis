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
class ConnectionFactoryOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\Factory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings()
    {
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $class = get_class($this->getMock('Predis\Connection\NodeConnectionInterface'));
        $value = array('tcp' => $class, 'redis' => $class);

        $default = $this->getMock('Predis\Connection\FactoryInterface');
        $default->expects($this->exactly(2))
                ->method('define')
                ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        $option = $this->getMock('Predis\Configuration\ConnectionFactoryOption', array('getDefault'));
        $option->expects($this->once())
               ->method('getDefault')
               ->with($options)
               ->will($this->returnValue($default));

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory = $option->filter($options, $value));
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testUsesParametersOptionToSetDefaultParameters()
    {
        $parameters = array('database' => 5, 'password' => 'mypassword');

        $default = $this->getMock('Predis\Connection\Factory');
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $options->expects($this->once())
                ->method('defined')
                ->with('parameters')
                ->will($this->returnValue(true));

        $options->expects($this->once())
                ->method('__get')
                ->with('parameters')
                ->will($this->returnValue($parameters));

        $option = new ConnectionFactoryOption();
        $factory = $option->getDefault($options);

        $this->assertSame($parameters, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsConnectionFactoryInstance()
    {
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $value = $this->getMock('Predis\Connection\FactoryInterface');

        $option = $this->getMock('Predis\Configuration\ConnectionFactoryOption', array('getDefault'));
        $option->expects($this->never())->method('getDefault');

        $this->assertSame($value, $option->filter($options, $value));
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidArguments()
    {
        $option = new ConnectionFactoryOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, new \stdClass());
    }
}
