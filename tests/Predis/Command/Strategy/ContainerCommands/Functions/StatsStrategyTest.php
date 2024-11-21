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

namespace Predis\Command\Strategy\ContainerCommands\Functions;

use PredisTestCase;

class StatsStrategyTest extends PredisTestCase
{
    /**
     * @var StatsStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new StatsStrategy();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testProcessArguments(): void
    {
        $this->assertSame(['arg1', 'arg2'], $this->strategy->processArguments(['arg1', 'arg2']));
    }
}
