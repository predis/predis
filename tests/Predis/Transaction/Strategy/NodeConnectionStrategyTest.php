<?php

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Connection\NodeConnectionInterface;

class NodeConnectionStrategyTest extends TestCase
{
    /**
     * @var NodeConnectionInterface
     */
    private $mockConnection;

    /**
     * @var CommandInterface
     */
    private $mockCommand;

    protected function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
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

        $strategy = new NodeConnectionStrategy($this->mockConnection);

        $this->assertEquals('OK', $strategy->executeCommand($this->mockCommand));
    }
}
