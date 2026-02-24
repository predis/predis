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
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Retry\Retry;
use Predis\Retry\Strategy\ExponentialBackoff;
use Predis\TimeoutException;
use Predis\Transaction\MultiExecState;

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

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters());

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertEquals('OK', $strategy->executeCommand($this->mockCommand));
    }

    /**
     * @return void
     */
    public function testUnwatch(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->callback(function ($command) {
                return $command->getId() === 'UNWATCH';
            }))
            ->willReturn('OK');

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters());

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertEquals('OK', $strategy->unwatch());
    }

    /**
     * @return void
     */
    public function testUnwatchWithRetries(): void
    {
        $parameters = new Parameters([
            'retry' => new Retry(new ExponentialBackoff(1000, 10000), 3),
        ]);

        $this->mockConnection
            ->expects($this->exactly(4))
            ->method('executeCommand')
            ->with($this->callback(function ($command) {
                return $command->getId() === 'UNWATCH';
            }))
            ->willReturnOnConsecutiveCalls(
                $this->throwException(new TimeoutException($this->mockConnection)),
                $this->throwException(new TimeoutException($this->mockConnection)),
                $this->throwException(new TimeoutException($this->mockConnection)),
                'OK'
            );

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn($parameters);

        $this->mockConnection
            ->expects($this->exactly(3))
            ->method('disconnect');

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertEquals('OK', $strategy->unwatch());
    }
}
