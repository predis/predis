<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2025 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Connection\Replication\ReplicationInterface;
use Predis\Transaction\MultiExecState;

class ReplicationConnectionStrategyTest extends TestCase
{
    /**
     * @var ReplicationInterface
     */
    private $mockConnection;

    /**
     * @var CommandInterface
     */
    private $mockCommand;

    protected function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(ReplicationInterface::class)->getMock();
        $this->mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();
    }

    /**
     * @return void
     */
    public function testExecuteCommand(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->mockCommand)
            ->willReturn('OK');

        $strategy = new ReplicationConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertEquals('OK', $strategy->executeCommand($this->mockCommand));
    }
}
