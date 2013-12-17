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
class PrefixOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new PrefixOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsStringAndReturnsCommandProcessor()
    {
        $option = new PrefixOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $return = $option->filter($options, $value = 'prefix:');

        $this->assertInstanceOf('Predis\Command\Processor\ProcessorInterface', $return);
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $return);
        $this->assertSame($value, $return->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCommandProcessorInstance()
    {
        $option = new PrefixOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');

        $return = $option->filter($options, $processor);

        $this->assertSame($processor, $return);
    }
}
