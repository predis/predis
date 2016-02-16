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
class ExceptionsOptionTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue()
    {
        $option = new ExceptionsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertTrue($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDifferentValuesAndFiltersThemAsBooleans()
    {
        $option = new ExceptionsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertFalse($option->filter($options, null));

        $this->assertTrue($option->filter($options, true));
        $this->assertFalse($option->filter($options, false));

        $this->assertTrue($option->filter($options, 1));
        $this->assertFalse($option->filter($options, 0));

        $this->assertTrue($option->filter($options, 'true'));
        $this->assertFalse($option->filter($options, 'false'));

        $this->assertTrue($option->filter($options, 'on'));
        $this->assertFalse($option->filter($options, 'off'));
    }

    /**
     * @group disconnected
     */
    public function testReturnsFalesOnValuesNotParsableAsBooleans()
    {
        $option = new ExceptionsOption();
        $options = $this->getMock('Predis\Configuration\OptionsInterface');

        $this->assertFalse($option->filter($options, new \stdClass()));
        $this->assertFalse($option->filter($options, 'invalid'));
    }
}
