<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Configuration\OptionsInterface;
use Predis\Connection\RelayFactory;
use PredisTestCase;
use stdClass;

class ConnectionsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Connections();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertInstanceOf('Predis\Connection\Factory', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsNamedArrayWithSchemeToConnectionClassMappings(): void
    {
        /** @var \Predis\Configuration\OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $class = get_class($this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock());
        $value = ['tcp' => $class, 'redis' => $class];

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $default
            ->expects($this->exactly(2))
            ->method('define')
            ->with($this->matchesRegularExpression('/^tcp|redis$/'), $class);

        /** @var \Predis\Configuration\OptionInterface */
        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->onlyMethods(['getDefault'])
            ->getMock();
        $option
            ->expects($this->once())
            ->method('getDefault')
            ->with($options)
            ->willReturn($default);

        $this->assertInstanceOf('Predis\Connection\FactoryInterface', $factory = $option->filter($options, $value));
        $this->assertSame($default, $factory);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringToConfigureRelayBackendWithoutParameters()
    {
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $options
            ->expects($this->once())
            ->method('defined')
            ->with('parameters')
            ->willReturn(false);

        $option = new Connections();
        $factory = $option->filter($options, 'relay');

        $this->assertInstanceOf(RelayFactory::class, $factory);
        $this->assertEmpty($factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringToConfigureRelayBackendWithParameters()
    {
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $options
            ->expects($this->once())
            ->method('defined')
            ->with('parameters')
            ->willReturn(true);

        $options->parameters = ['foo' => 'bar'];

        $option = new Connections();
        $factory = $option->filter($options, 'relay');

        $this->assertInstanceOf(RelayFactory::class, $factory);
        $this->assertSame(['foo' => 'bar'], $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringDefaultToReturnConnectionFactoryWithDefaultConfiguration()
    {
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $default = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $default
            ->expects($this->never())
            ->method('define');

        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
        ->setMethods(['getDefault'])
        ->getMock();
        $option
            ->expects($this->once())
            ->method('getDefault')
            ->with($options)
            ->will($this->returnValue($default));

        $factory = $option->filter($options, 'default');

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
    public function testUsesParametersOptionToSetDefaultParameters(): void
    {
        $parameters = ['database' => 5, 'password' => 'mypassword'];

        /** @var \Predis\Configuration\OptionsInterface|\PHPUnit\Framework\MockObject\MockObject\MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('defined')
            ->with('parameters')
            ->willReturn(true);
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('parameters')
            ->willReturn($parameters);

        $option = new Connections();
        $factory = $option->getDefault($options);

        $this->assertSame($parameters, $factory->getDefaultParameters());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsConnectionFactoryInstance(): void
    {
        /** @var \Predis\Configuration\OptionInterface */
        $option = $this->getMockBuilder('Predis\Configuration\Option\Connections')
            ->onlyMethods(['getDefault'])
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
    public function testAcceptsCallableReturningConnectionFactoryInstance(): void
    {
        $option = new Connections();

        /** @var \Predis\Configuration\OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn(
                $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock()
            );

        $this->assertSame($factory, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidArguments(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Connections expects a valid connection factory');

        $option = new Connections();

        /** @var \Predis\Configuration\OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, new stdClass());
    }
}
