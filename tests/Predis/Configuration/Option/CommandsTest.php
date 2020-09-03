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
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Configuration\OptionsInterface;
use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Command\RedisFactory;

/**
 *
 */
class CommandsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAppliesPrefixOnDefaultOptionValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__isset')
            ->with('prefix')
            ->willReturn(true);
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('prefix')
            ->willReturn(
                new KeyPrefixProcessor('prefix:')
            );

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $commands->getProcessor());
        $this->assertSame('prefix:', $commands->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandFactoryInstanceAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $input = new RedisFactory();

        $commands = $option->filter($options, $input);

        $this->assertSame($commands, $input);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDictionaryOfCommandsAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $input = array(
            'FOO' => 'Predis\Command\RawCommand',
            'BAR' => 'Predis\Command\RawCommand',
        );

        $commands = $option->filter($options, $input);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('FOO'));
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('BAR'));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDictionaryOfCommandsWithNullsToUndefineCommandsAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $input = array(
            'ECHO' => null,
            'EVAL' => null,
            'FOO'  => null,
        );

        $commands = $option->filter($options, $input);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertNull($commands->getCommandClass('ECHO'));
        $this->assertNull($commands->getCommandClass('EVAL'));
        $this->assertNull($commands->getCommandClass('FOO'));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningCommandFactoryInstance(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($commands);

        $this->assertSame($commands, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningDictionaryOfCommandsAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $dictionary = array(
            'FOO' => 'Predis\Command\RawCommand',
            'BAR' => 'Predis\Command\RawCommand',
        );

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn($dictionary);

        $commands = $option->filter($options, $callable);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('FOO'));
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('BAR'));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringPredisAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $commands = $option->filter($options, 'predis');

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\RedisFactory', $commands);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringRawAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $commands = $option->filter($options, 'raw');

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\RawFactory', $commands);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringDefaultAsValue(): void
    {
        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $commands = $option->filter($options, 'default');

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\RedisFactory', $commands);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidStringAsValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Commands does not recognize `unknown` as a supported configuration string');

        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 'unknown');
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidTypeReturnedByCallable(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Commands expects a valid command factory');

        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn(
                new \stdClass()
            );

        $option->filter($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Configuration\Option\Commands expects a valid command factory');

        $option = new Commands();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnPrefixWithRawFactory(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Predis\Command\RawFactory does not support key prefixing');

        $option = new Commands();

        /** @var OptionsInterface|MockObject */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $options
            ->expects($this->once())
            ->method('__isset')
            ->with('prefix')
            ->willReturn(true);

        $option->filter($options, 'raw');
    }
}
