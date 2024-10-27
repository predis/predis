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

use Predis\Cluster\ReplicasSelectorInterface;
use Predis\Configuration\OptionsInterface;
use PredisTestCase;

class ReadonlyReplicasTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testDefaultOptionValue(): void
    {
        $option = new ReadonlyReplicas();

        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertNull($option->getDefault($options));
    }

    /**
     * @group disconnected
     */
    public function testAcceptsDifferentValuesAndFiltersThemAsBooleans(): void
    {
        $option = new ReadonlyReplicas();

        $options = $this->getMockBuilder(OptionsInterface::class)->getMock();

        $this->assertNull($option->filter($options, null));
        $this->assertNull($option->filter($options, false));
        $this->assertNull($option->filter($options, ''));

        $this->assertInstanceOf(ReplicasSelectorInterface::class, $option->filter($options, true));
        $this->assertInstanceOf(ReplicasSelectorInterface::class, $option->filter($options, 'true'));
        $this->assertInstanceOf(ReplicasSelectorInterface::class, $option->filter($options, 'on'));
    }
}
