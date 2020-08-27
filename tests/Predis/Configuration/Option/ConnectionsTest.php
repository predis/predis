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

/**
 *
 */
class ConnectionsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Connections();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Connection\Factory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings()
    {
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $class = get_class($this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock());
        $value = array('tcp' => $class, 'redis' => $class);

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $default
            ->expects($this->exactly(2))
            ->method('define')
            ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->setMethods(array('getDefault'))
            ->getMock();
        $option
            ->expects($this->once())
            ->method('getDefault')
            ->with($options)
            ->will($this->returnValue($default));

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory = $option->filter($options, $value));
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     * @dataProvider provideSupportedStringValuesForOption
     */
    public function testAcceptsStringToConfigurePhpiredisStreamBackend($value, $classFQCN)
    {
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $default
            ->expects($this->exactly(2))
            ->method('define')
            ->with($this->matchesRegularExpression('/^tcp|unix$/'), $classFQCN);

        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
        ->setMethods(array('getDefault'))
        ->getMock();
        $option
            ->expects($this->once())
            ->method('getDefault')
            ->with($options)
            ->will($this->returnValue($default));

        $factory = $option->filter($options, $value);

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory);
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNotSupportedStringValue()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches('/^.* does not recognize `unsupported` as a supported configuration string$/');

        $option = new Connections();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unsupported');
    }

    /**
     * @group disconnected
     */
    public function testUsesParametersOptionToSetDefaultParameters()
    {
        $parameters = array('database' => 5, 'password' => 'mypassword');

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('defined')
            ->with('parameters')
            ->will($this->returnValue(true));
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('parameters')
            ->will($this->returnValue($parameters));

        $option = new Connections();
        $factory = $option->getDefault($options);

        $this->assertSame($parameters, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsConnectionFactoryInstance()
    {
        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->setMethods(array('getDefault'))
            ->getMock();
        $option
            ->expects($this->never())
            ->method('getDefault');

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $this->assertSame($factory, $option->filter($options, $factory));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningConnectionFactoryInstance()
    {
        $option = new Connections();

        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue(
                $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            ));

        $this->assertSame($factory, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidArguments()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Connections expects a valid connection factory');

        $option = new Connections();
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, new \stdClass());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Test provider for string values supported by this client option.
     *
     * @return array
     */
    public function provideSupportedStringValuesForOption()
    {
        return array(
            array('phpiredis-stream', 'Predis\Connection\PhpiredisStreamConnection'),
            array('phpiredis-socket', 'Predis\Connection\PhpiredisSocketConnection'),
            array('phpiredis', 'Predis\Connection\PhpiredisStreamConnection'),
        );
    }
}
