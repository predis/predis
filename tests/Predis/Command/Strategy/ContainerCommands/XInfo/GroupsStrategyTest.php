<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command\Strategy\ContainerCommands\XInfo;

use PredisTestCase;

class GroupsStrategyTest extends PredisTestCase
{
    /**
     * @var GroupsStrategy
     */
    private $strategy;

    protected function setUp(): void
    {
        $this->strategy = new GroupsStrategy();
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testProcessArguments(): void
    {
        $this->assertSame(['arg1', 'arg2'], $this->strategy->processArguments(['arg1', 'arg2']));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testParseResponse(): void
    {
        $actualResponse = [['name', 'group', 'consumers', 0, 'pending', 0, 'last-delivered-id', 3]];
        $expectedResponse = [['name' => 'group', 'consumers' => 0, 'pending' => 0, 'last-delivered-id' => 3]];

        $this->assertSame($expectedResponse, $this->strategy->parseResponse($actualResponse));
    }
}
