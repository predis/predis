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

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\SET;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
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

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters());

        $strategy = new ReplicationConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertEquals('OK', $strategy->executeCommand($this->mockCommand));
    }

    /**
     * @return void
     */
    public function testCommandsAfterMultiGoOnlyToTheConnectionHoldingTheTransaction(): void
    {
        $master = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $master
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(static function (CommandInterface $command) {
                return $command->getId() === 'EXEC' ? ['OK'] : 'QUEUED';
            });

        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->callback(static function (CommandInterface $command) {
                return $command->getId() === 'MULTI';
            }))
            ->willReturn('OK');

        $this->mockConnection
            ->expects($this->once())
            ->method('getConnectionByCommand')
            ->willReturn($master);

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters());

        $strategy = new ReplicationConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertTrue($strategy->initializeTransaction());
        $this->assertSame('QUEUED', $strategy->executeCommand(new SET()));
        $this->assertSame(['OK'], $strategy->executeTransaction());
    }
}
