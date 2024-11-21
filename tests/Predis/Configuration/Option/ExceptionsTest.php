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
use stdClass;

class ExceptionsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new Exceptions();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertTrue($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDifferentValuesAndFiltersThemAsBooleans(): void
    {
        $option = new Exceptions();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

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
    public function testReturnsFalesOnValuesNotParsableAsBooleans(): void
    {
        $option = new Exceptions();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertFalse($option->filter($options, new stdClass()));
        $this->assertFalse($option->filter($options, 'invalid'));
    }
}
