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
class PrefixTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringAndReturnsCommandProcessor()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $prefix = $option->filter($options, $value = 'prefix:');

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame($value, $prefix->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandProcessorInstance()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');

        $prefix = $option->filter($options, $processor);

        $this->assertSame($processor, $prefix);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningProcessorInterface()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue(
                $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface')
            ));

        $prefix = $option->filter($options, $callable);

        $this->assertSame($processor, $prefix);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningStringPrefix()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue('pfx:'));

        $prefix = $option->filter($options, $callable);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame('pfx:', $prefix->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsObjectAsPrefixAndCastsToString()
    {
        $option = new Prefix();

        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $input = $this->getMock('stdClass', array('__toString'));
        $input
            ->expects($this->once())
            ->method('__toString')
            ->will($this->returnValue('pfx:'));

        $prefix = $option->filter($options, $input);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame('pfx:', $prefix->getPrefix());
    }
}
