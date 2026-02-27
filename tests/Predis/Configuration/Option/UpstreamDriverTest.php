<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2026 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Configuration\Option;

use Predis\Configuration\OptionsInterface;
use PredisTestCase;

class UpstreamDriverTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new UpstreamDriver();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertSame('', $option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsString(): void
    {
        $option = new UpstreamDriver();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $value = 'laravel_v11.0.0';
        $filtered = $option->filter($options, $value);

        $this->assertSame('laravel_v11.0.0', $filtered);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsArrayAndNormalizesToString(): void
    {
        $option = new UpstreamDriver();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $value = ['laravel_v11.0.0', 'my-app_v1.0.0'];
        $filtered = $option->filter($options, $value);

        $this->assertSame('laravel_v11.0.0;my-app_v1.0.0', $filtered);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsEmptyArray(): void
    {
        $option = new UpstreamDriver();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $this->assertSame('', $option->filter($options, []));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidValue(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('UpstreamDriver option expects a string or an array of strings');

        $option = new UpstreamDriver();

        /** @var OptionsInterface */
        $options = $this->getMockBuilder('Predis\Configuration\OptionsInterface')->getMock();

        $option->filter($options, 123);
    }
}
