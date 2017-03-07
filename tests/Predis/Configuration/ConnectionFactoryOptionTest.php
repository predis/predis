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
        $options = $this->createMock('Predis\Configuration\OptionsInterface');

        $this->assertInstanceOf('Predis\Connection\Factory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings()
    {
        $options = $this->createMock('Predis\Configuration\OptionsInterface');

        $class = get_class($this->createMock('Predis\Connection\NodeConnectionInterface'));
        $value = array('tcp' => $class, 'redis' => $class);

        $default = $this->createMock('Predis\Connection\FactoryInterface');
        $default->expects($this->exactly(2))
                ->method('define')
                ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        $option = $this->getMockBuilder('Predis\Configuration\ConnectionFactoryOption')->setMethods(array('getDefault'))->getMock();
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

        $default = $this->createMock('Predis\Connection\Factory');
        $options = $this->createMock('Predis\Configuration\OptionsInterface');

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
        $options = $this->createMock('Predis\Configuration\OptionsInterface');
        $value = $this->createMock('Predis\Connection\FactoryInterface');

        $option = $this->getMockBuilder('Predis\Configuration\ConnectionFactoryOption')->setMethods(array('getDefault'))->getMock();
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
        $options = $this->createMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, new \stdClass());
    }
}
