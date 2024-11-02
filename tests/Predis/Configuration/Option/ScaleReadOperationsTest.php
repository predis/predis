<?php

declare(strict_types=1);

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

use InvalidArgumentException;
use Predis\Cluster\ReadConnectionSelector;
use Predis\Cluster\ReadConnectionSelectorInterface;
use Predis\Configuration\OptionsInterface;
use PredisTestCase;

class ScaleReadOperationsTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new ScaleReadOperations();
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDifferentModes(): void
    {
        $option = new ScaleReadOperations();
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertInstanceOf(ReadConnectionSelectorInterface::class, $option->filter($options, ReadConnectionSelector::MODE_RANDOM));
        $this->assertInstanceOf(ReadConnectionSelectorInterface::class, $option->filter($options, ReadConnectionSelector::MODE_REPLICAS));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnInvalidStringValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String value for the scale read operations option must be one of: replicas, random');

        $option = new ScaleReadOperations();
        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $option->filter($options, 'unknown');
    }
}
