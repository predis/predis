<?php

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Connection\Replication\ReplicationInterface;

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

        $strategy = new ReplicationConnectionStrategy($this->mockConnection);

        $this->assertEquals('OK', $strategy->executeCommand($this->mockCommand));
    }
}
