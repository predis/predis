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

use Predis\Command\Processor\KeyPrefixProcessor;
use Predis\Command\RedisFactory;
use PredisTestCase;

/**
 *
 */
class CommandsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAppliesPrefixOnDefaultOptionValue()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $options
            ->expects($this->once())
            ->method('__isset')
            ->with('prefix')
            ->will($this->returnValue(true));
        $options
            ->expects($this->once())
            ->method('__get')
            ->with('prefix')
            ->will($this->returnValue(
                new KeyPrefixProcessor('prefix:')
            ));

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $commands->getProcessor());
        $this->assertSame('prefix:', $commands->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandFactoryInstanceAsValue()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $input = new RedisFactory();

        $commands = $option->filter($options, $input);

        $this->assertSame($commands, $input);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDictionaryOfCommandsAsValue()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

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
    public function testAcceptsCallableReturningCommandFactoryInstance()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $commands = $this->getMock('Predis\Command\FactoryInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($commands));

        $this->assertSame($commands, $option->filter($options, $callable));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningDictionaryOfCommandsAsValue()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $dictionary = array(
            'FOO' => 'Predis\Command\RawCommand',
            'BAR' => 'Predis\Command\RawCommand',
        );

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($dictionary));

        $commands = $option->filter($options, $callable);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('FOO'));
        $this->assertSame('Predis\Command\RawCommand', $commands->getCommandClass('BAR'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Commands expects a valid command factory
     */
    public function testThrowsExceptionOnInvalidTypeReturnedByCallable()
    {
        $option = new Commands();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue(
                new \stdClass()
            ));

        $option->filter($options, $callable);
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Predis\Configuration\Option\Commands expects a valid command factory
     */
    public function testThrowsExceptionOnInvalidValue()
    {
        $option = new Commands();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, new \stdClass());
    }
}
