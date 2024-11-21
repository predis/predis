<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Configuration\OptionsInterface;
use PredisTestCase;

class PrefixTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringAndReturnsCommandProcessor(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $prefix = $option->filter($options, $value = 'prefix:');

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame($value, $prefix->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandProcessorInstance(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();
        $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock();

        $prefix = $option->filter($options, $processor);

        $this->assertSame($processor, $prefix);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningProcessorInterface(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn(
                $processor = $this->getMockBuilder('Predis\Command\Processor\ProcessorInterface')->getMock()
            );

        $prefix = $option->filter($options, $callable);

        $this->assertSame($processor, $prefix);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableReturningStringPrefix(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->willReturn('pfx:');

        $prefix = $option->filter($options, $callable);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame('pfx:', $prefix->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsObjectAsPrefixAndCastsToString(): void
    {
        $option = new Prefix();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $input = $this->getMockBuilder('stdClass')
            ->addMethods(['__toString'])
            ->getMock();
        $input
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('pfx:');

        $prefix = $option->filter($options, $input);

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $prefix);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $prefix);
        $this->assertSame('pfx:', $prefix->getPrefix());
    }
}
