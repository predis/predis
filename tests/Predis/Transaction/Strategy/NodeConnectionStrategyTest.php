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
use Predis\Command\Redis\GET;
use Predis\Command\Redis\SET;
use Predis\Connection\ConnectionException;
use Predis\Connection\NodeConnectionInterface;
use Predis\Connection\Parameters;
use Predis\Connection\Resource\Exception\StreamInitException;
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

    /**
     * @var bool
     */
    private $connected = true;

    protected function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->mockConnection
            ->method('isConnected')
            ->willReturnCallback(function () {
                return $this->connected;
            });
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
            ->with($this->callback(static function ($command) {
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
            ->with($this->callback(static function ($command) {
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

    /**
     * @return void
     */
    public function testCommandsAfterMultiAreNotRetried(): void
    {
        $sent = [];
        $this->mockConnection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$sent) {
                $sent[] = $command->getId();

                if ($command->getId() === 'MULTI') {
                    return 'OK';
                }

                throw new ConnectionException($this->mockConnection, 'Connection lost');
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $this->mockConnection
            ->expects($this->once())
            ->method('disconnect');

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertTrue($strategy->initializeTransaction());

        try {
            $strategy->executeCommand(new SET());
            $this->fail('Expected the connection error to reach the caller.');
        } catch (ConnectionException $exception) {
            $this->assertSame(['MULTI', 'SET'], $sent);
        }
    }

    /**
     * @return void
     */
    public function testCommandsAfterWatchAreNotRetried(): void
    {
        $sent = [];
        $this->mockConnection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$sent) {
                $sent[] = $command->getId();

                if ($command->getId() === 'WATCH') {
                    return 'OK';
                }

                throw new TimeoutException($this->mockConnection);
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $this->mockConnection
            ->expects($this->once())
            ->method('disconnect');

        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new NodeConnectionStrategy($this->mockConnection, $state);
        $strategy->watch(['foo']);

        try {
            $strategy->executeCommand(new GET());
            $this->fail('Expected the connection error to reach the caller.');
        } catch (TimeoutException $exception) {
            $this->assertSame(['WATCH', 'GET'], $sent);
        }
    }

    /**
     * @return void
     */
    public function testMultiIsRetriedUntilTheTransactionReachesTheConnection(): void
    {
        $failures = 0;
        $this->mockConnection
            ->expects($this->exactly(5))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$failures) {
                if ($command->getId() === 'MULTI' && $failures++ % 2 === 0) {
                    throw new TimeoutException($this->mockConnection);
                }

                return $command->getId() === 'EXEC' ? [] : 'OK';
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->assertTrue($strategy->initializeTransaction());
        $this->assertSame([], $strategy->executeTransaction());
        $this->assertTrue($strategy->initializeTransaction());
    }

    /**
     * @dataProvider transactionEnds
     * @return void
     */
    public function testMultiIsRetriedAgainOnceTheTransactionEnds(string $end): void
    {
        $failMulti = false;
        $this->mockConnection
            ->expects($this->any())
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$failMulti) {
                if ($command->getId() === 'MULTI' && $failMulti) {
                    $failMulti = false;

                    throw new TimeoutException($this->mockConnection);
                }

                if ($command->getId() === 'SET') {
                    throw new ConnectionException($this->mockConnection, 'Connection lost');
                }

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        switch ($end) {
            case 'DISCARD':
                $strategy->discard();
                break;
            case 'UNWATCH':
                $strategy->unwatch();
                break;
            case 'connection error':
                try {
                    $strategy->executeCommand(new SET());
                } catch (ConnectionException $exception) {
                    // NOOP
                }
                break;
        }

        $failMulti = true;
        $this->assertTrue($strategy->initializeTransaction());
        $this->assertFalse($failMulti);
    }

    public function transactionEnds(): array
    {
        return [
            'DISCARD' => ['DISCARD'],
            'UNWATCH' => ['UNWATCH'],
            'connection error' => ['connection error'],
        ];
    }

    /**
     * @return void
     */
    public function testCommandsAfterWatchFailWhenTheConnectionIsNoLongerOpen(): void
    {
        $sent = [];
        $this->mockConnection
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$sent) {
                $sent[] = $command->getId();

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new NodeConnectionStrategy($this->mockConnection, $state);
        $strategy->watch(['foo']);

        // e.g. after MultiExec::onProtocolError() disconnected it
        $this->connected = false;

        try {
            $strategy->executeCommand(new GET());
            $this->fail('Expected the lost connection to reach the caller.');
        } catch (ConnectionException $exception) {
            $this->assertSame(['WATCH'], $sent);
        }

        // The transaction no longer holds the connection, so commands go through retries again.
        $this->connected = true;
        $this->assertSame('OK', $strategy->executeCommand(new GET())->getResponse());
        $this->assertSame('OK', $strategy->executeCommand(new GET())->getResponse());
        $this->assertSame(['WATCH', 'GET', 'GET'], $sent);
    }

    /**
     * @return void
     */
    public function testAFailedReconnectReleasesTheConnection(): void
    {
        $calls = 0;
        $this->mockConnection
            ->expects($this->exactly(4))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$calls) {
                ++$calls;

                if ($calls === 2) {
                    throw new StreamInitException('Connection refused');
                }

                // Only a released connection retries this one.
                if ($calls === 3) {
                    throw new TimeoutException($this->mockConnection);
                }

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new NodeConnectionStrategy($this->mockConnection, $state);
        $strategy->watch(['foo']);

        try {
            $strategy->executeCommand(new GET());
            $this->fail('Expected the stream error to reach the caller.');
        } catch (StreamInitException $exception) {
        }

        $this->assertSame('OK', $strategy->executeCommand(new GET())->getResponse());
    }
}
