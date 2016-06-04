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

use Predis\Command\RedisFactory;
use Predis\Command\Processor\KeyPrefixProcessor;
use PredisTestCase;

/**
 *
 */
class CommandsOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new CommandsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandFactoryInstanceAsValue()
    {
        $option = new CommandsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $value = new RedisFactory();

        $commands = $option->filter($options, $value);

        $this->assertSame($commands, $value);
        $this->assertNull($commands->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testAppliesPrefixOnDefaultOptionValue()
    {
        $option = new CommandsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $options->expects($this->once())
                ->method('__isset')
                ->with('prefix')
                ->will($this->returnValue(true));
        $options->expects($this->once())
                ->method('__get')
                ->with('prefix')
                ->will($this->returnValue(new KeyPrefixProcessor('prefix:')));

        $commands = $option->getDefault($options);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $commands->getProcessor());
        $this->assertSame('prefix:', $commands->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionOnStringValue()
    {
        $option = new CommandsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, '3.2');
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     */
    public function testThrowsExceptionOnInvalidValue()
    {
        $option = new CommandsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $option->filter($options, new \stdClass());
    }
}
