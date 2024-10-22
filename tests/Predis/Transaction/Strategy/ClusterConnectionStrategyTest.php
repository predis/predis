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

namespace Predis\Transaction\Strategy;

use PHPUnit\Framework\TestCase;
use Predis\Command\CommandInterface;
use Predis\Command\Redis\EXEC;
use Predis\Command\Redis\MULTI;
use Predis\Command\Redis\SET;
use Predis\Connection\Cluster\ClusterInterface;
use Predis\Response\ErrorInterface;
use Predis\Response\Status;
use Predis\Transaction\Exception\TransactionException;
use Predis\Transaction\MultiExecState;

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
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with(new MULTI())
            ->willReturn('ERR');

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
        $this->mockConnection
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->withConsecutive([new MULTI()], [new SET()], [new SET()])
            ->willReturnOnConsecutiveCalls('OK', 'QUEUED', 'ERR');

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

        $this->mockConnection
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
    public function testMulti(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->with(new MULTI())
            ->willReturnOnConsecutiveCalls('ERR', 'OK');

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('ERR', $strategy->multi());

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Transaction context should be initialized first');

        $strategy->executeCommand(new SET());

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

        $this->mockConnection
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
    public function testWatchAdditionallyInitializeTransactionContextOnCASTransaction(): void
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

        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->withAnyParameters()
            ->willReturn(new Status('OK'));

        $state = new MultiExecState();
        $state->set(MultiExecState::CAS);

        $strategy = new ClusterConnectionStrategy($this->mockConnection, $state);
        $this->assertTrue($strategy->watch(['key1', 'key2', 'key3']));
        $this->assertEquals('QUEUED', $strategy->executeCommand(new SET()));
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testDiscard(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->withAnyParameters()
            ->willReturn(new Status('OK'));

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('OK', $strategy->discard());
    }

    /**
     * @group disconnected
     * @return void
     */
    public function testUnwatch(): void
    {
        $this->mockConnection
            ->expects($this->once())
            ->method('executeCommand')
            ->withAnyParameters()
            ->willReturn(new Status('OK'));

        $strategy = new ClusterConnectionStrategy($this->mockConnection, new MultiExecState());
        $this->assertEquals('OK', $strategy->unwatch());
    }
}
