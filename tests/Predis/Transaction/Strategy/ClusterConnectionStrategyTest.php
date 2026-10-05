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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\DISCARD;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\GET;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\SET;
use Predis\Command\Redis\UNWATCH;
use Predis\Command\Redis\WATCH;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Connection\Cluster\RedisCluster;
use Predis\Connection\NodeConnectionInterface;
use Predis\Response\Error;
use Predis\Response\ErrorInterface;
use Predis\Response\ServerException;
use Predis\Response\Status;
use Predis\Transaction\Exception\TransactionException;
use Predis\Transaction\MultiExecState;
use Predis\Transaction\Response\BypassTransactionResponse;
use RuntimeException;

class ClusterConnectionStrategyTest extends TestCase
{
    /**
     * @var ClusterInterface
     */
    private $mockConnection;

    /**
     * @var \Predis\Cluster\StrategyInterface
     */
    private $mockStrategy;

    /**
     * @var NodeConnectionInterface
     */
    private $mockNodeConnection;

    /**
     * @var CommandInterface
     */
    private $mockCommand;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockConnection = $this->getMockBuilder(ClusterInterface::class)->getMock();
        $this->mockStrategy = $this->getMockBuilder(\Predis\Cluster\StrategyInterface::class)->getMock();
        $this->mockNodeConnection = $this->getMockBuilder(NodeConnectionInterface::class)->getMock();
        $this->mockConnection->method('getConnectionByCommand')->willReturn($this->mockNodeConnection);
        $this->mockConnection->expects($this->never())->method('executeCommand');
        $this->mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();

        $this->mockConnection
            ->method('getClusterStrategy')
            ->willReturn($this->mockStrategy);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testExecuteCommandThrowsExceptionOnNonInitializedTransactionContext(): void
    {
        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Transaction context should be initialized first');

        $strategy->executeCommand($this->mockCommand);
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteCommandAssignsHashSlotToSlotlessCommand(): void
    {
        $anotherCommand = new SET();

        $this->mockStrategy
            ->expects($this->exactly(2))
            ->method('getSlot')
            ->withConsecutive([$this->mockCommand], [$anotherCommand])
            ->willReturnOnConsecutiveCalls(10, null);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        $this->assertEquals('QUEUED', $strategy->executeCommand($this->mockCommand));
        $this->assertEquals('QUEUED', $strategy->executeCommand($anotherCommand));
        $this->assertEquals(10, $anotherCommand->getSlot());
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteCommandReturnsErrorOnMissMatchingSlots(): void
    {
        $this->mockStrategy
            ->expects($this->exactly(2))
            ->method('getSlot')
            ->with($this->mockCommand)
            ->willReturnOnConsecutiveCalls(123, 124);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        $strategy->executeCommand($this->mockCommand);
        $response = $strategy->executeCommand($this->mockCommand);

        $this->assertInstanceOf(ErrorInterface::class, $response);
        $this->assertSame(
            'To be able to execute a transaction against cluster, all commands should operate on the same hash slot',
            $response->getMessage()
        );
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteCommandQueueCommandsMappedToTheSameSlot(): void
    {
        $anotherMockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();

        $this->mockStrategy
            ->expects($this->exactly(2))
            ->method('getSlot')
            ->withConsecutive([$this->mockCommand], [$anotherMockCommand])
            ->willReturnOnConsecutiveCalls([123], [123]);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();

        $resp1 = $strategy->executeCommand($this->mockCommand);
        $resp2 = $strategy->executeCommand($this->mockCommand);

        $this->assertEquals('QUEUED', $resp1);
        $this->assertEquals('QUEUED', $resp2);
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testInitializeTransactionContext(): void
    {
        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertTrue($strategy->initializeTransaction());
        $this->assertTrue($strategy->initializeTransaction());
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteTransactionReturnsNullOnInitializeError(): void
    {
        $this->mockNodeConnection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive([new MULTI()], [new DISCARD()])
            ->willReturnOnConsecutiveCalls('ERR', 'OK');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteTransactionReturnsNullOnQueueingError(): void
    {
        $this->mockNodeConnection
            ->expects($this->exactly(4))
            ->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new SET()], [new DISCARD()])
            ->willReturnOnConsecutiveCalls('OK', 'QUEUED', 'ERR', 'OK');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());
        $strategy->executeCommand(new SET());
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testExecuteTransactionReturnsResultOnSuccessfulTransaction(): void
    {
        $command1 = new SET();
        $command2 = new SET();
        $command3 = new SET();
        $command1->setArguments(['{foo}bar', 'value']);
        $command2->setArguments(['{foo}baz', 'value']);
        $command3->setArguments(['{foo}foo', 'value']);

        $this->mockNodeConnection
            ->expects($this->exactly(5))
            ->method('executeCommand')
            ->withConsecutive(
                [new MULTI()],
                [$command1],
                [$command2],
                [$command3],
                [new EXEC()]
            )
            ->willReturnOnConsecutiveCalls(
                'OK',
                'QUEUED',
                'QUEUED',
                'QUEUED',
                [new Status('OK'), new Status('OK'), new Status('OK')]
            );

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand($command1);
        $strategy->executeCommand($command2);
        $strategy->executeCommand($command3);

        $this->assertEquals(
            [new Status('OK'), new Status('OK'), new Status('OK')],
            $strategy->executeTransaction()
        );
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testMultiInitializesTransactionWithoutSendingMulti(): void
    {
        $this->mockNodeConnection->expects($this->never())->method('executeCommand');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('OK', $strategy->multi());
        $this->assertEquals('QUEUED', $strategy->executeCommand(new SET()));
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testWatchThrowsExceptionOnKeysPointingToDifferentSlots(): void
    {
        $this->mockStrategy
            ->expects($this->once())
            ->method('checkSameSlotForKeys')
            ->with(['key1', 'key2', 'key3'])
            ->willReturn(false);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('WATCHed keys should point to the same hash slot');

        $strategy->watch(['key1', 'key2', 'key3']);
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testWatchReturnCorrectResponse(): void
    {
        $this->mockStrategy
            ->expects($this->once())
            ->method('checkSameSlotForKeys')
            ->with(['key1', 'key2', 'key3'])
            ->willReturn(true);

        $this->mockStrategy
            ->expects($this->once())
            ->method('getSlotByKey')
            ->with('key1')
            ->willReturn(10);

        $this->mockNodeConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->withAnyParameters()
            ->willReturn(new Status('OK'));

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertTrue($strategy->watch(['key1', 'key2', 'key3']));
    }

    /**
     * @group disconnected
     * @return void
     * @throws TransactionException
     */
    public function testWatchAllowsImmediateReadsInCASTransaction(): void
    {
        $this->mockStrategy
            ->expects($this->once())
            ->method('checkSameSlotForKeys')
            ->with(['key1', 'key2', 'key3'])
            ->willReturn(true);

        $this->mockStrategy
            ->expects($this->once())
            ->method('getSlotByKey')
            ->with('key1')
            ->willReturn(10);

        $this->mockNodeConnection
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->willReturnOnConsecutiveCalls(new Status('OK'), 'value');

        $state = new MultiExecState();
        $state->set(MultiExecState::CAS);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, $state);
        $this->assertTrue($strategy->watch(['key1', 'key2', 'key3']));
        $response = $strategy->executeCommand(new GET());
        $this->assertInstanceOf(BypassTransactionResponse::class, $response);
        $this->assertSame('value', $response->getResponse());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDiscard(): void
    {
        $this->mockNodeConnection->expects($this->never())->method('executeCommand');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('OK', $strategy->discard());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testUnwatch(): void
    {
        $this->mockNodeConnection->expects($this->never())->method('executeCommand');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('OK', $strategy->unwatch());
    }

    /**
     * @group disconnected
     * @dataProvider clusterQueueErrors
     */
    public function testDiscardUsesTheNodeAndSlotThatReceivedMulti(string $error): void
    {
        $this->mockStrategy->method('getSlot')->willReturn(123);
        $this->mockConnection->expects($this->once())->method('getConnectionByCommand');
        $this->mockNodeConnection->expects($this->exactly(3))->method('executeCommand')
            ->withConsecutive([$this->callback(static function ($command) {
                return $command instanceof MULTI && $command->getSlot() === 123;
            })], [$this->callback(static function ($command) {
                return $command instanceof SET && $command->getSlot() === 123;
            })], [$this->callback(static function ($command) {
                return $command instanceof DISCARD && $command->getSlot() === 123;
            })])
            ->willReturnOnConsecutiveCalls('OK', new Error($error), 'OK');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    public function clusterQueueErrors(): array
    {
        return [
            ['OOM command not allowed'],
            ['MOVED 123 127.0.0.1:6380'],
            ['ASK 123 127.0.0.1:6380'],
            ['READONLY You cannot write against a read only replica'],
        ];
    }

    /**
     * @group disconnected
     */
    public function testFailedDiscardDisconnectsTheNode(): void
    {
        $this->mockNodeConnection->expects($this->exactly(3))->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new DISCARD()])
            ->willReturnOnConsecutiveCalls('OK', new Error('OOM'), new Error('NOPERM'));
        $this->mockNodeConnection->expects($this->once())->method('disconnect');
        $this->mockConnection->expects($this->never())->method('remove');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    /**
     * @group disconnected
     * @dataProvider transactionFailureStages
     */
    public function testExceptionDisconnectsTheNodeWithoutReplayingCommands(string $stage): void
    {
        $exception = new RuntimeException('Connection failed');
        $this->mockConnection->expects($this->once())->method('getConnectionByCommand');
        $this->mockNodeConnection->method('executeCommand')->willReturnCallback(
            static function ($command) use ($stage, $exception) {
                if ($command->getId() === $stage) {
                    throw $exception;
                }

                if ($command->getId() === 'MULTI') {
                    return 'OK';
                }

                return $stage === 'DISCARD' ? new Error('OOM') : 'QUEUED';
            }
        );
        $this->mockNodeConnection->expects($this->once())->method('disconnect');
        $this->mockConnection->expects($this->never())->method('remove');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        try {
            $strategy->executeTransaction();
            $this->fail('Expected the connection failure to be rethrown.');
        } catch (RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function transactionFailureStages(): array
    {
        return [['MULTI'], ['SET'], ['EXEC'], ['DISCARD']];
    }

    /**
     * @group disconnected
     */
    public function testAbortClearsPendingCommandsBeforeReinitializing(): void
    {
        $this->mockNodeConnection->expects($this->exactly(6))->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new DISCARD()], [new MULTI()], [new GET()], [new EXEC()])
            ->willReturnOnConsecutiveCalls('OK', new Error('OOM'), 'OK', 'OK', 'QUEUED', ['value']);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());
        $strategy->executeCommand(new SET());
        $this->assertNull($strategy->executeTransaction());

        $strategy->initializeTransaction();
        $strategy->executeCommand(new GET());
        $this->assertSame(['value'], $strategy->executeTransaction());
    }

    /**
     * @group disconnected
     */
    public function testCasWithoutWatchReadsImmediatelyAndSendsMultiOnlyOnce(): void
    {
        $this->mockNodeConnection->expects($this->exactly(4))->method('executeCommand')
            ->withConsecutive([new GET()], [new MULTI()], [new SET()], [new EXEC()])
            ->willReturnOnConsecutiveCalls('7', 'OK', 'QUEUED', [new Status('OK')]);
        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new ClusterConnectionStrategy($this->mockConnection, $state);

        $this->assertSame('7', $strategy->executeCommand(new GET())->getResponse());
        $state->unflag(MultiExecState::CAS);
        $this->assertEquals('OK', $strategy->multi());
        $strategy->executeCommand(new SET());
        $this->assertEquals([new Status('OK')], $strategy->executeTransaction());
    }

    /**
     * @group disconnected
     */
    public function testExecuteTransactionThrowsExceptionOnNonInitializedTransactionContext(): void
    {
        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Transaction context should be initialized first');

        $strategy->executeTransaction();
    }

    /**
     * @group disconnected
     */
    public function testExecuteTransactionReturnsNullOnExecError(): void
    {
        $this->mockNodeConnection->expects($this->exactly(4))->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new EXEC()], [new DISCARD()])
            ->willReturnOnConsecutiveCalls(
                'OK',
                'QUEUED',
                new Error('EXECABORT Transaction discarded because of previous errors.'),
                new Error('ERR DISCARD without MULTI')
            );
        $this->mockNodeConnection->expects($this->once())->method('disconnect');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    /**
     * @group disconnected
     */
    public function testWatchErrorUnwatchesTheNodeAndThrows(): void
    {
        $watch = new WATCH();
        $watch->setArguments(['key1', 'key2']);

        $this->mockStrategy->method('checkSameSlotForKeys')->willReturn(true);
        $this->mockNodeConnection->expects($this->exactly(2))->method('executeCommand')
            ->withConsecutive([$watch], [new UNWATCH()])
            ->willReturnOnConsecutiveCalls(new Error('NOPERM this user has no permissions'), 'OK');
        $this->mockNodeConnection->expects($this->never())->method('disconnect');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('NOPERM this user has no permissions');

        $strategy->watch(['key1', 'key2']);
    }

    /**
     * @group disconnected
     */
    public function testCasReadErrorUnwatchesTheNodeAndThrows(): void
    {
        $this->mockNodeConnection->expects($this->exactly(2))->method('executeCommand')
            ->withConsecutive([new GET()], [new UNWATCH()])
            ->willReturnOnConsecutiveCalls(new Error('WRONGTYPE Operation against a key'), 'OK');
        $this->mockNodeConnection->expects($this->never())->method('disconnect');

        $state = new MultiExecState();
        $state->flag(MultiExecState::CAS);
        $strategy = new ClusterConnectionStrategy($this->mockConnection, $state);

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('WRONGTYPE Operation against a key');

        $strategy->executeCommand(new GET());
    }

    /**
     * @group disconnected
     * @dataProvider cleanupMethods
     */
    public function testCleanupUnwatchesTheNodeThatReceivedWatch(string $method): void
    {
        $this->mockStrategy->method('checkSameSlotForKeys')->willReturn(true);
        $this->mockStrategy->method('getSlotByKey')->willReturn(123);
        $this->mockConnection->expects($this->once())->method('getConnectionByCommand');
        $this->mockNodeConnection->expects($this->exactly(2))->method('executeCommand')
            ->withConsecutive([$this->isInstanceOf(WATCH::class)], [$this->callback(static function ($command) {
                return $command instanceof UNWATCH && $command->getSlot() === 123;
            })])
            ->willReturn('OK');
        $this->mockNodeConnection->expects($this->never())->method('disconnect');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->watch(['key1']);

        $this->assertEquals('OK', $strategy->$method());
        $this->assertEquals('OK', $strategy->$method());
    }

    public function cleanupMethods(): array
    {
        return [['discard'], ['unwatch']];
    }

    /**
     * @group disconnected
     */
    public function testFailedUnwatchDisconnectsTheNode(): void
    {
        $this->mockStrategy->method('checkSameSlotForKeys')->willReturn(true);
        $this->mockNodeConnection->expects($this->exactly(2))->method('executeCommand')
            ->withConsecutive([$this->isInstanceOf(WATCH::class)], [new UNWATCH()])
            ->willReturnOnConsecutiveCalls('OK', new Error('NOPERM'));
        $this->mockNodeConnection->expects($this->once())->method('disconnect');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->watch(['key1']);

        $this->assertInstanceOf(ErrorInterface::class, $strategy->discard());
    }

    /**
     * @group disconnected
     */
    public function testDiscardClearsQueuedCommandsWithoutContactingTheNode(): void
    {
        $this->mockNodeConnection->expects($this->exactly(3))->method('executeCommand')
            ->withConsecutive([new MULTI()], [new GET()], [new EXEC()])
            ->willReturnOnConsecutiveCalls('OK', 'QUEUED', ['value']);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());
        $this->assertEquals('OK', $strategy->discard());

        $strategy->initializeTransaction();
        $strategy->executeCommand(new GET());
        $this->assertSame(['value'], $strategy->executeTransaction());
    }

    /**
     * @group disconnected
     * @dataProvider movedStages
     */
    public function testMovedResponseUpdatesSlotMapAfterReleasingTheNode(string $stage, array $expected): void
    {
        $events = [];
        $cluster = $this->getMockRedisCluster();
        $cluster->expects($this->once())->method('applyMovedResponse')->with('123 127.0.0.1:6380')
            ->willReturnCallback(static function () use (&$events) {
                $events[] = 'applyMovedResponse';
            });
        $this->mockNodeConnection->method('executeCommand')->willReturnCallback(
            static function ($command) use ($stage, &$events) {
                $events[] = $id = $command->getId();

                if ($id === $stage) {
                    return new Error('MOVED 123 127.0.0.1:6380');
                }

                return $id === 'SET' ? 'QUEUED' : 'OK';
            }
        );

        $strategy = new ClusterConnectionStrategy($cluster, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
        $this->assertSame($expected, $events);
    }

    public function movedStages(): array
    {
        return [
            'queueing' => ['SET', ['MULTI', 'SET', 'DISCARD', 'applyMovedResponse']],
            'execution' => ['EXEC', ['MULTI', 'SET', 'EXEC', 'DISCARD', 'applyMovedResponse']],
        ];
    }

    /**
     * @group disconnected
     */
    public function testMovedResponseToWatchUpdatesSlotMapAndThrows(): void
    {
        $this->mockStrategy->method('checkSameSlotForKeys')->willReturn(true);
        $cluster = $this->getMockRedisCluster();
        $cluster->expects($this->once())->method('applyMovedResponse')->with('123 127.0.0.1:6380');
        $this->mockNodeConnection->expects($this->exactly(2))->method('executeCommand')
            ->withConsecutive([$this->isInstanceOf(WATCH::class)], [new UNWATCH()])
            ->willReturnOnConsecutiveCalls(new Error('MOVED 123 127.0.0.1:6380'), 'OK');

        $strategy = new ClusterConnectionStrategy($cluster, new MultiExecState());

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('MOVED 123 127.0.0.1:6380');

        $strategy->watch(['key1']);
    }

    /**
     * @group disconnected
     * @dataProvider errorsWithoutSlotOwnership
     */
    public function testOtherErrorsLeaveTheSlotMapUntouched(string $error): void
    {
        $cluster = $this->getMockRedisCluster();
        $cluster->expects($this->never())->method('applyMovedResponse');
        $this->mockNodeConnection->expects($this->exactly(3))->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new DISCARD()])
            ->willReturnOnConsecutiveCalls('OK', new Error($error), 'OK');

        $strategy = new ClusterConnectionStrategy($cluster, new MultiExecState());
        $strategy->initializeTransaction();
        $strategy->executeCommand(new SET());

        $this->assertNull($strategy->executeTransaction());
    }

    public function errorsWithoutSlotOwnership(): array
    {
        return [
            ['OOM command not allowed'],
            ['ASK 123 127.0.0.1:6380'],
            ['READONLY You cannot write against a read only replica'],
            ['MOVED'],
        ];
    }

    /**
     * Returns a mocked Redis cluster routing every command to the mocked node.
     *
     * @return RedisCluster|MockObject
     */
    private function getMockRedisCluster()
    {
        $cluster = $this->getMockBuilder(RedisCluster::class)->disableOriginalConstructor()->getMock();
        $cluster->method('getClusterStrategy')->willReturn($this->mockStrategy);
        $cluster->method('getConnectionByCommand')->willReturn($this->mockNodeConnection);
        $cluster->expects($this->never())->method('executeCommand');

        return $cluster;
    }
}
