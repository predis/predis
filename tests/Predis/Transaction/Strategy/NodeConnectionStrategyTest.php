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
use Predis\Connection\RelayConnection;
use Predis\Response\ServerException;
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
        ];
    }

    /**
     * @return void
     */
    public function testCommandsAfterWatchFailUntilUnwatchOnceTheConnectionIsLost(): void
    {
        $sent = [];
        $this->mockConnection
            ->expects($this->exactly(4))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$sent) {
                $sent[] = $command->getId();

                if ($sent === ['WATCH', 'GET']) {
                    throw new TimeoutException($this->mockConnection);
                }

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->once())
            ->method('disconnect')
            ->willReturnCallback(function () {
                $this->connected = false;
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
            $this->fail('Expected the connection error to reach the caller.');
        } catch (TimeoutException $exception) {
            // NOOP
        }

        try {
            $strategy->executeCommand(new GET());
            $this->fail('Expected the lost connection to reach the caller.');
        } catch (ConnectionException $exception) {
            $this->assertSame('The connection holding the transaction was lost.', $exception->getMessage());
        }

        $this->assertSame(['WATCH', 'GET'], $sent);

        $strategy->unwatch();

        $this->assertSame('OK', $strategy->executeCommand(new GET())->getResponse());
        $this->assertSame(['WATCH', 'GET', 'UNWATCH', 'GET'], $sent);
    }

    /**
     * @return void
     */
    public function testDiscardEndsATransactionWhoseConnectionWasLost(): void
    {
        $sent = [];
        $this->mockConnection
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->willReturnCallback(function (CommandInterface $command) use (&$sent) {
                $sent[] = $command->getId();

                if ($command->getId() === 'SET') {
                    throw new ConnectionException($this->mockConnection, 'Connection lost');
                }

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->once())
            ->method('disconnect')
            ->willReturnCallback(function () {
                $this->connected = false;
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters(['retry' => new Retry(new ExponentialBackoff(1, 10), 3)]));

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        try {
            $strategy->executeCommand(new SET());
            $this->fail('Expected the connection error to reach the caller.');
        } catch (ConnectionException $exception) {
            // NOOP
        }

        try {
            $strategy->executeTransaction();
            $this->fail('Expected EXEC to fail on the lost connection.');
        } catch (ConnectionException $exception) {
            $this->assertSame('The connection holding the transaction was lost.', $exception->getMessage());
        }

        $this->assertEquals('OK', $strategy->discard());
        $this->assertSame(['MULTI', 'SET'], $sent);

        $this->assertTrue($strategy->initializeTransaction());
        $this->assertSame(['MULTI', 'SET', 'MULTI'], $sent);
    }

    /**
     * @return void
     */
    public function testServerExceptionFromNodeConnectionIsRethrown(): void
    {
        $this->mockConnection
            ->method('executeCommand')
            ->willReturnCallback(static function (CommandInterface $command) {
                if ($command->getId() === 'EXEC') {
                    throw new ServerException('ERR RELAY_ERR_REDIS simulated');
                }

                return 'OK';
            });

        $this->mockConnection
            ->expects($this->any())
            ->method('getParameters')
            ->willReturn(new Parameters());

        $strategy = new NodeConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('ERR RELAY_ERR_REDIS simulated');

        $strategy->executeTransaction();
    }

    /**
     * @requires extension relay
     * @dataProvider relayServerErrors
     * @return void
     */
    public function testServerExceptionFromRelay(string $failing, string $message, bool $aborted): void
    {
        $relay = $this->getMockBuilder(RelayConnection::class)->disableOriginalConstructor()->getMock();
        $relay
            ->method('executeCommand')
            ->willReturnCallback(static function (CommandInterface $command) use ($failing, $message) {
                if ($command->getId() === $failing) {
                    throw new ServerException($message);
                }

                return 'OK';
            });
        $relay
            ->method('isConnected')
            ->willReturn(true);
        $relay
            ->method('getParameters')
            ->willReturn(new Parameters());

        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new NodeConnectionStrategy($relay, $state);
        $strategy->watch(['foo']);
        $strategy->multi();

        if ($aborted) {
            $this->assertNull($strategy->executeTransaction());

            return;
        }

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage($message);

        $failing === 'EXEC' ? $strategy->executeTransaction() : $strategy->executeCommand(new GET());
    }

    public function relayServerErrors(): array
    {
        return [
            'aborted EXEC' => ['EXEC', 'ERR RELAY_ERR_REDIS simulated', true],
            'other EXEC error' => ['EXEC', 'ERR simulated', false],
            'error on another command' => ['GET', 'ERR RELAY_ERR_REDIS simulated', false],
        ];
    }
}
