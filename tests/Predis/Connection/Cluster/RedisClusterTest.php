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

namespace Predis\Connection\Cluster;

use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Cluster\RedisStrategy;
use Predis\Cluster\StrategyInterface;
use Predis\Command\CommandInterface;
use Predis\Command\RawCommand;
use Predis\Connection\ConnectionException;
use Predis\Connection\Factory;
use Predis\Connection\FactoryInterface;
use Predis\Connection\Parameters;
use Predis\Connection\RelayConnection;
use Predis\Connection\RelayFactory;
use Predis\Replication\ReplicationStrategy;
use Predis\Response\Error;
use Predis\Response\Status;
use PredisTestCase;

class RedisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testAcceptsCustomConnectionFactory(): void
    {
        /** @var FactoryInterface */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $cluster = new RedisCluster($factory, new Parameters());

        $this->assertSame($factory, $cluster->getConnectionFactory());
    }

    /**
     * @group disconnected
     */
    public function testUsesRedisClusterStrategyByDefault(): void
    {
        $cluster = new RedisCluster(new Factory(), new Parameters());

        $this->assertInstanceOf(RedisStrategy::class, $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCustomClusterStrategy(): void
    {
        /** @var StrategyInterface */
        $strategy = $this->getMockBuilder(StrategyInterface::class)->getMock();

        $cluster = new RedisCluster(new Factory(), new Parameters(), $strategy);

        $this->assertSame($strategy, $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertCount(2, $cluster);
        $this->assertSame($connection1, $cluster->getConnectionById('127.0.0.1:6379'));
        $this->assertSame($connection2, $cluster->getConnectionById('127.0.0.1:6380'));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6371');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->remove($connection1));
        $this->assertFalse($cluster->remove($connection3));
        $this->assertCount(1, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromClusterByAlias(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->removeById('127.0.0.1:6380'));
        $this->assertFalse($cluster->removeById('127.0.0.1:6390'));
        $this->assertCount(1, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testCountReturnsNumberOfConnectionsInPool(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertCount(3, $cluster);

        $cluster->remove($connection3);

        $this->assertCount(2, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testConnectToEachNode(): void
    {
        $connect1 = false;
        $connect2 = false;

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('connect')
            ->willReturnCallback(function () use (&$connect1) {
                $connect1 = true;
            });
        $connection1
            ->expects($this->any())
            ->method('isConnected')
            ->willReturnCallback(function () use (&$connect1) {
                return $connect1;
            });

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('connect')
            ->willReturnCallback(function () use (&$connect2) {
                $connect2 = true;
            });
        $connection2
            ->expects($this->any())
            ->method('isConnected')
            ->willReturnCallback(function () use (&$connect2) {
                return $connect2;
            });

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->connect();

        $this->assertTrue($cluster->isConnected());
        $this->assertTrue($connect1);
        $this->assertTrue($connect2);
    }

    /**
     * @group disconnected
     */
    public function testDisconnectForcesAllConnectionsToDisconnect(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('disconnect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('disconnect');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsTrueIfAtLeastOneConnectionIsOpen(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsFalseIfAllConnectionsAreClosed(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertFalse($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorReturnsConnectionsMappedInSlotsMapWhenUseClusterSlotsIsDisabled(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection4 = $this->getMockConnection('tcp://127.0.0.1:6384');

        $cluster = new RedisCluster(new Factory(), new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);
        $cluster->add($connection4);

        $this->assertInstanceOf('Iterator', $iterator = $cluster->getIterator());
        $connections = iterator_to_array($iterator);

        $this->assertCount(3, $connections);
        $this->assertSame($connection1, $connections[0]);
        $this->assertSame($connection2, $connections[1]);
        $this->assertSame($connection3, $connections[2]);
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorReturnsConnectionsMappedInSlotsMapFetchedFromRedisCluster(): void
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 6381], []],
            [5461, 10922, ['127.0.0.1', 6383], []],
            [10923, 16383, ['127.0.0.1', 6384], []],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383');
        $connection4 = $this->getMockConnection('tcp://127.0.0.1:6384');

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '6383',
                    ],
                ],
                [
                    [
                        'host' => '127.0.0.1',
                        'port' => '6384',
                    ],
                ]
            )
            ->willReturnOnConsecutiveCalls(
                $connection3,
                $connection4
            );

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getRandomConnection'])
            ->setConstructorArgs([$factory, new Parameters()])
            ->getMock();
        $cluster
            ->expects($this->once())
            ->method('getRandomConnection')
            ->willReturn($connection1);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->useClusterSlots(true);

        $this->assertInstanceOf('Iterator', $iterator = $cluster->getIterator());
        $connections = iterator_to_array($iterator);

        $this->assertCount(3, $connections);
        $this->assertSame($connection1, $connections[0]);
        $this->assertSame($connection3, $connections[1]);
        $this->assertSame($connection4, $connections[2]);
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionResetsSlotsMap(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);

        $slotmap = $cluster->getSlotMap();
        $slotmap->setSlots(0, 5460, '127.0.0.1:6379');

        $this->assertSame(array_fill(0, 5461, '127.0.0.1:6379'), $slotmap->toArray());

        $cluster->add($connection2);

        $this->assertCount(0, $slotmap);
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionResetsSlotsMap(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $slotmap = $cluster->getSlotMap();
        $slotmap->setSlots(0, 5460, '127.0.0.1:6379');
        $slotmap->setSlots(5461, 10922, '127.0.0.1:6380');

        $expectedMap = array_merge(
            array_fill(0, 5461, '127.0.0.1:6379'),
            array_fill(5460, 5462, '127.0.0.1:6380')
        );

        $this->assertSame($expectedMap, $slotmap->toArray());

        $cluster->remove($connection1);

        $this->assertCount(0, $slotmap);
    }

    /**
     * @group disconnected
     */
    public function testCanAssignConnectionsToRangeOfSlotsFromParameters(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379?slots=0-5460');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=10923-16383');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->buildSlotMap();

        $expectedMap = array_merge(
            array_fill(0, 5461, '127.0.0.1:6379'),
            array_fill(5461, 5462, '127.0.0.1:6380'),
            array_fill(10923, 5461, '127.0.0.1:6381')
        );

        $actualMap = $cluster->getSlotMap()->toArray();

        ksort($actualMap);

        $this->assertSame($expectedMap, $actualMap);
    }

    /**
     * @group disconnected
     */
    public function testCanAssignConnectionsToSingleSlotOrRangesOfSlotsFromParameters(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379?slots=0-5460,5500-5600,11000');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380?slots=5461-5499,5600-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=10923-10999,11001-16383');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->buildSlotMap();

        $expectedMap = array_merge(
            array_fill(0, 5461, '127.0.0.1:6379'),
            array_fill(5460, 39, '127.0.0.1:6380'),
            array_fill(5499, 101, '127.0.0.1:6379'),
            array_fill(5599, 5322, '127.0.0.1:6380'),
            array_fill(10923, 77, '127.0.0.1:6381'),
            array_fill(11000, 1, '127.0.0.1:6379'),
            array_fill(11000, 5383, '127.0.0.1:6381')
        );

        $actualMap = $cluster->getSlotMap()->toArray();

        ksort($actualMap);

        $this->assertSame($expectedMap, $actualMap);
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingSlotID(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame($connection1, $cluster->getConnectionBySlot(0));
        $this->assertSame($connection2, $cluster->getConnectionBySlot(5461));
        $this->assertSame($connection3, $cluster->getConnectionBySlot(10923));

        $cluster->getSlotMap()->setSlots(5461, 7096, '127.0.0.1:6380');
        $this->assertSame($connection2, $cluster->getConnectionBySlot(5461));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingCommandInstance(): void
    {
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $set = $commands->create('set', ['node:1001', 'foobar']);
        $get = $commands->create('get', ['node:1001']);
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['node:1048', 'foobar']);
        $get = $commands->create('get', ['node:1048']);
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['node:1082', 'foobar']);
        $get = $commands->create('get', ['node:1082']);
        $this->assertSame($connection3, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection3, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('writeRequest')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('writeRequest');

        $cluster = new RedisCluster(new Factory(), new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->writeRequest($command);
    }

    /**
     * @group disconnected
     */
    public function testReadsCommandFromCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', ['node:1050']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->never())
            ->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('readResponse')
            ->with($command);

        $cluster = new RedisCluster(new Factory(), new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->readResponse($command);
    }

    /**
     * @group disconnected
     */
    public function testRetriesExecutingCommandOnConnectionFailureOnlyAfterFetchingNewSlotsMap(): void
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 9381], []],
            [5461, 10922, ['127.0.0.1', 6382], []],
            [10923, 16383, ['127.0.0.1', 6383], []],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', ['node:1001']
            ))
            ->willThrowException(
                new ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

        $connection4 = $this->getMockConnection('tcp://127.0.0.1:9381');
        $connection4
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('GET', ['node:1001'])],
                [$this->isRedisCommand('GET', ['node:5001'])]
            )
            ->willReturnOnConsecutiveCalls(
                'value:1001',
                'value:5001'
            );

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '127.0.0.1',
                'port' => '9381',
            ])
            ->willReturn($connection4);

        $cluster = new RedisCluster($factory, new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame('value:1001', $cluster->executeCommand(
            RawCommand::create('get', 'node:1001')
        ));

        $this->assertSame('value:5001', $cluster->executeCommand(
            RawCommand::create('get', 'node:5001')
        ));
    }

    /**
     * @group disconnected
     */
    public function testRetriesExecutingCommandOnConnectionFailureButDoNotAskSlotMapWhenDisabled(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5500');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', ['node:1001']
            ))
            ->willThrowException(
                new ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5501-11000');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', ['node:1001']
            ))
            ->willReturn(
                new Error('MOVED 1970 127.0.0.1:9381')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=11101-16383');
        $connection3
            ->expects($this->never())
            ->method('executeCommand');

        $connection4 = $this->getMockConnection('tcp://127.0.0.1:9381');
        $connection4
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', ['node:1001']
            ))
            ->willReturn('value:1001');

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '127.0.0.1',
                'port' => '9381',
            ])
            ->willReturn($connection4);

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getRandomConnection'])
            ->setConstructorArgs([$factory, new Parameters()])
            ->getMock();
        $cluster
            ->expects($this->never())
            ->method('getRandomConnection');

        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame('value:1001', $cluster->executeCommand(
            RawCommand::create('get', 'node:1001')
        ));
    }

    /**
     * @group disconnected
     * @group slow
     */
    public function testThrowsClientExceptionWhenExecutingCommandWithEmptyPool(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('No connections available in the pool');

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory, new Parameters());

        $cluster->executeCommand(
            RawCommand::create('get', 'node:1001')
        );
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapReturnEmptyArrayOnEmptyConnectionsPool(): void
    {
        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->askSlotMap();

        $this->assertCount(0, $cluster->getSlotMap());
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapRetriesOnDifferentNodeOnConnectionFailure(): void
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 9381], []],
            [5461, 10922, ['127.0.0.1', 6382], []],
            [10923, 16383, ['127.0.0.1', 6383], []],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6383]')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getRandomConnection'])
            ->setConstructorArgs([$factory, new Parameters()])
            ->getMock();
        $cluster
            ->expects($this->exactly(3))
            ->method('getRandomConnection')
            ->willReturnOnConsecutiveCalls($connection1, $connection2, $connection3);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->askSlotMap();

        $this->assertCount(16384, $cluster->getSlotMap());
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapHonorsRetryLimitOnMultipleConnectionFailures(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unknown connection error [127.0.0.1:6382]');

        $slotsmap = [
            [0, 5460, ['127.0.0.1', 9381], []],
            [5461, 10922, ['127.0.0.1', 6382], []],
            [10923, 16383, ['127.0.0.1', 6383], []],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6382]')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->never())
            ->method('executeCommand');

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getRandomConnection'])
            ->setConstructorArgs([$factory, new Parameters()])
            ->getMock();
        $cluster
            ->expects($this->exactly(2))
            ->method('getRandomConnection')
            ->willReturnOnConsecutiveCalls($connection1, $connection2);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->setRetryLimit(1);

        $cluster->askSlotMap();
    }

    /**
     * @group disconnected
     */
    public function testSupportsKeyHashTags(): void
    {
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $commands->create('set', ['{node:1001}:foo', 'foobar']);
        $get = $commands->create('get', ['{node:1001}:foo']);
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['{node:1001}:bar', 'foobar']);
        $get = $commands->create('get', ['{node:1001}:bar']);
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testAskResponseWithConnectionInPool(): void
    {
        $askResponse = new Error('ASK 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->willReturnOnConsecutiveCalls($askResponse, 'foobar');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('ASKING')],
                [$this->isRedisCommand($command)]
            )
            ->willReturnOnConsecutiveCalls(
                new Status('OK'),
                'foobar'
            );

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertCount(2, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testAskResponseWithConnectionNotInPool(): void
    {
        $askResponse = new Error('ASK 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->willReturnOnConsecutiveCalls($askResponse, 'foobar');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('ASKING')],
                [$this->isRedisCommand($command)]
            )
            ->willReturnOnConsecutiveCalls(
                new Status('OK'),
                'foobar'
            );

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '127.0.0.1',
                'port' => '6381',
            ])
            ->willReturn($connection3);

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertCount(2, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testMovedResponseWithConnectionInPool(): void
    {
        $movedResponse = new Error('MOVED 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(1))
            ->method('executeCommand')
            ->with($command)
            ->willReturn($movedResponse);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->willReturnOnConsecutiveCalls('foobar', 'foobar');

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory->expects($this->never())->method('create');

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertCount(2, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testMovedResponseWithConnectionNotInPool(): void
    {
        $movedResponse = new Error('MOVED 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->willReturn($movedResponse);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->willReturnOnConsecutiveCalls('foobar', 'foobar');

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '127.0.0.1',
                'port' => '6381',
            ])
            ->willReturn($connection3);

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertCount(3, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testParseIPv6AddresseAndPortPairInRedirectionPayload(): void
    {
        $movedResponse = new Error('MOVED 1970 2001:db8:0:f101::2:6379');

        $command = $this->getCommandFactory()->create('get', ['node:1001']);

        $connection1 = $this->getMockConnection('tcp://[2001:db8:0:f101::1]:6379');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->willReturn($movedResponse);

        $connection2 = $this->getMockConnection('tcp://[2001:db8:0:f101::2]:6379');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->willReturn('foobar');

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '2001:db8:0:f101::2',
                'port' => '6379',
            ])
            ->willReturn($connection2);

        $cluster = new RedisCluster($factory, new Parameters());
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testFetchSlotsMapFromClusterWithClusterSlotsCommand(): void
    {
        $response = [
            [12288, 13311, ['10.1.0.51', 6387], ['10.1.0.52', 6387]],
            [3072,  4095, ['10.1.0.52', 6392], ['10.1.0.51', 6392]],
            [6144,  7167, ['', 6384], ['10.1.0.52', 6384]],
            [14336, 15359, ['10.1.0.51', 6388], ['10.1.0.52', 6388]],
            [15360, 16383, ['10.1.0.52', 6398], ['10.1.0.51', 6398]],
            [1024,  2047, ['10.1.0.52', 6391], ['10.1.0.51', 6391]],
            [11264, 12287, ['10.1.0.52', 6396], ['10.1.0.51', 6396]],
            [5120,  6143, ['10.1.0.52', 6393], ['10.1.0.51', 6393]],
            [0,  1023, ['10.1.0.51', 6381], ['10.1.0.52', 6381]],
            [13312, 14335, ['10.1.0.52', 6397], ['10.1.0.51', 6397]],
            [4096,  5119, ['10.1.0.51', 6383], ['10.1.0.52', 6383]],
            [9216, 10239, ['10.1.0.52', 6395], ['10.1.0.51', 6395]],
            [8192,  9215, ['10.1.0.51', 6385], ['10.1.0.52', 6385]],
            [10240, 11263, ['10.1.0.51', 6386], ['10.1.0.52', 6386]],
            [2048,  3071, ['10.1.0.51', 6382], ['10.1.0.52', 6382]],
            [7168,  8191, ['10.1.0.52', 6394], ['10.1.0.51', 6394]],
        ];

        $connection1 = $this->getMockConnection('tcp://10.1.0.51:6384');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($response);

        /** @var FactoryInterface */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $cluster = new RedisCluster($factory, new Parameters());

        $cluster->add($connection1);

        $cluster->askSlotMap();

        $this->assertSame($cluster->getConnectionBySlot('6144'), $connection1);
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapToRedisClusterOnMovedResponseByDefault(): void
    {
        $cmdGET = RawCommand::create('GET', 'node:1001');
        $rspMOVED = new Error('MOVED 1970 127.0.0.1:6380');
        $rspSlotsArray = [
            [0,  8191, ['127.0.0.1', 6379]],
            [8192, 16383, ['127.0.0.1', 6380]],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdGET)
            ->willReturn($rspMOVED);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                [$this->isRedisCommand('CLUSTER', ['SLOTS'])],
                [$this->isRedisCommand($cmdGET)]
            )
            ->willReturnOnConsecutiveCalls(
                $rspSlotsArray,
                'foobar'
            );

        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with([
                'host' => '127.0.0.1',
                'port' => '6380',
            ])
            ->willReturn($connection2);

        $cluster = new RedisCluster($factory, new Parameters());

        $cluster->add($connection1);

        $this->assertSame('foobar', $cluster->executeCommand($cmdGET));
        $this->assertCount(2, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonSupportedCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("Cannot use 'PING' with redis-cluster");

        $ping = $this->getCommandFactory()->create('ping');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($this->getMockConnection('tcp://127.0.0.1:6379'));

        $cluster->getConnectionByCommand($ping);
    }

    /**
     * @medium
     * @group disconnected
     */
    public function testCanBeSerialized(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379?slots=0-5460');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=10923-16383');

        $cluster = new RedisCluster(new Factory(), new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->buildSlotMap();

        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }

    /**
     * @medium
     * @group disconnected
     * @group slow
     */
    public function testRetryCommandSuccessOnClusterDownErrors()
    {
        $clusterDownError = new Error('CLUSTERDOWN');

        $command = RawCommand::create('get', 'node:1001');

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->exactly(3))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls(
                        $clusterDownError,
                        $clusterDownError,
                        'foobar'));

        $cluster = new RedisCluster(new Factory(), new Parameters());
        $cluster->useClusterSlots(false);
        $cluster->setRetryLimit(2);
        $cluster->add($connection1);

        $this->assertSame('foobar', $cluster->executeCommand($command));
    }

    /**
     * @medium
     * @group disconnected
     * @group slow
     */
    public function testRetryCommandFailureOnClusterDownErrors()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('CLUSTERDOWN');

        $clusterDownError = new Error('CLUSTERDOWN');

        $command = RawCommand::create('get', 'node:1001');

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->exactly(3))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls(
                        $clusterDownError,
                        $clusterDownError,
                        $clusterDownError
                    ));

        $cluster = new RedisCluster(new Factory(), new Parameters());
        $cluster->useClusterSlots(false);
        $cluster->setRetryLimit(2);
        $cluster->add($connection1);

        $cluster->executeCommand($command);
    }

    /**
     * @medium
     * @group disconnected
     * @group slow
     */
    public function testQueryClusterNodeForSlotMapPauseDurationOnRetry()
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 9381], []],
            [5461, 10922, ['127.0.0.1', 6382], []],
            [10923, 16383, ['127.0.0.1', 6383], []],
        ];

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willThrowException(
                new ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6383]')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getRandomConnection'])
            ->setConstructorArgs([$factory, new Parameters()])
            ->getMock();
        $cluster
            ->expects($this->exactly(3))
            ->method('getRandomConnection')
            ->willReturnOnConsecutiveCalls($connection1, $connection2, $connection3);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->setRetryInterval(2000);

        $startTime = time();
        $cluster->askSlotMap();
        $endTime = time();
        $totalTime = $endTime - $startTime;
        $t1 = $cluster->getRetryInterval();
        $t2 = $t1 * 2;

        $expectedTime = ($t1 + $t2) / 1000; // expected time for 2 retries (fail 1=wait 2s, fail 2=wait 4s , OK)
        $this->AssertEqualsWithDelta($expectedTime, $totalTime, 1, 'Unexpected execution time');

        $this->assertCount(16384, $cluster->getSlotMap());
    }

    /**
     * @group disconnected
     */
    public function testGetParameters(): void
    {
        $connection = $this->getMockConnection('tcp://127.0.0.1:7001?protocol=3');
        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $expectedParameters = new Parameters([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 7001,
            'protocol' => '3',
        ]);

        $cluster = new RedisCluster($factory, $expectedParameters);

        $this->assertEquals($expectedParameters, $cluster->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandOnEachNode(): void
    {
        /** @var CommandInterface|MockObject */
        $mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();
        /** @var FactoryInterface|MockObject */
        $factory = $this->getMockBuilder(FactoryInterface::class)->getMock();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:7003');

        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($mockCommand)
            ->willReturn('response1');

        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($mockCommand)
            ->willReturn('response2');

        $connection3
            ->expects($this->once())
            ->method('executeCommand')
            ->with($mockCommand)
            ->willReturn('response3');

        $cluster = new RedisCluster($factory, new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertEquals(['response1', 'response2', 'response3'], $cluster->executeCommandOnEachNode($mockCommand));
    }

    /**
     * @group disconnected
     */
    public function testCreatesRelayConnectionsCluster(): void
    {
        /** @var RelayFactory|MockObject */
        $factory = $this->getMockBuilder(RelayFactory::class)->getMock();
        /** @var RelayConnection|MockObject */
        $expectedConnection = $this
            ->getMockBuilder(RelayConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $parameters = new Parameters([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 7001,
            'protocol' => '3',
        ]);

        $cluster = new RedisCluster($factory, $parameters);

        $cluster->add($expectedConnection);

        $this->assertInstanceOf(RelayConnection::class, $cluster->getConnectionBySlot(9999));
    }

    /**
     * @group disconnected
     */
    public function testLoadBalancingReadsFromSecondaries()
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 1001], ['127.0.0.1', 2001]],
            [5461, 10922, ['127.0.0.1', 1002], ['127.0.0.1', 2002]],
            [10923, 16383, ['127.0.0.1', 1003], ['127.0.0.1', 2003]],
        ];

        /** @var RedisCluster|MockObject $cluster */
        [$cluster, $returnMap] = $this->setupMocks($slotsmap, new ReplicationStrategy());

        $cluster
            ->expects($this->exactly(3))
            ->method('createConnection')
            ->willReturnMap($returnMap);

        // Check that the right connections are returned
        foreach ($slotsmap as $i => $mapData) {
            // pick a slot that belongs to this shard, e.g. the start
            $slot = $mapData[0];

            $command = new RawCommand('GET', ['foo']);
            $command->setSlot($slot);
            $commandConnection = $cluster->getConnectionByCommand($command);

            $expectedConnection = $returnMap[$i][1];

            $this->assertSame($expectedConnection, $commandConnection);
        }
    }

    /**
     * Ensure that disabled load balancing keep the previous behavior of only using primaries.
     *
     * @group disconnected
     */
    public function testNoLoadBalancingReadsFromPrimaries()
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 1001], ['127.0.0.1', 2001]],
            [5461, 10922, ['127.0.0.1', 1002], ['127.0.0.1', 2002]],
            [10923, 16383, ['127.0.0.1', 1003], ['127.0.0.1', 2003]],
        ];

        $replicationStrategy = new ReplicationStrategy();
        $replicationStrategy->disableLoadBalancing();
        /** @var RedisCluster|MockObject $cluster */
        [$cluster, $returnMap, $primaryConnections] = $this->setupMocks($slotsmap, $replicationStrategy);

        // Check that the right connections are returned
        foreach ($slotsmap as $i => $mapData) {
            // pick a slot that belongs to this shard, e.g. the start
            $slot = $mapData[0];

            $command = new RawCommand('GET', ['foo']);
            $command->setSlot($slot);
            $commandConnection = $cluster->getConnectionByCommand($command);

            $expectedConnection = $primaryConnections[$i];
            $this->assertSame($expectedConnection, $commandConnection);
        }
    }

    /**
     * Ensure that disabled load balancing keep the previous behavior of only using primaries.
     *
     * @group disconnected
     */
    public function testLoadBalancingWritesToPrimaries()
    {
        $command = new RawCommand('SET', ['foo', 'bar']);
        $this->checkLoadBalancingOnPrimaryCommands($command);
    }

    /**
     * @group disconnected
     */
    public function testLoadBalancingDisallowedCommandsToPrimaries()
    {
        $command = new RawCommand('INFO', []);
        $this->checkLoadBalancingOnPrimaryCommands($command);
    }

    private function checkLoadBalancingOnPrimaryCommands(CommandInterface $command)
    {
        $slotsmap = [
            [0, 5460, ['127.0.0.1', 1001], ['127.0.0.1', 2001]],
            [5461, 10922, ['127.0.0.1', 1002], ['127.0.0.1', 2002]],
            [10923, 16383, ['127.0.0.1', 1003], ['127.0.0.1', 2003]],
        ];

        $replicationStrategy = new ReplicationStrategy();

        /** @var RedisCluster|MockObject $cluster */
        [$cluster, $returnMap, $primaryConnections] = $this->setupMocks($slotsmap, $replicationStrategy);

        // Check that the right connections are returned
        foreach ($slotsmap as $i => $mapData) {
            // pick a slot that belongs to this shard, e.g. the start
            $slot = $mapData[0];

            $command->setSlot($slot);
            $commandConnection = $cluster->getConnectionByCommand($command);

            $expectedConnection = $primaryConnections[$i];
            $this->assertSame($expectedConnection, $commandConnection);
        }
    }

    private function setupMocks(array $slotsmap, ReplicationStrategy $replicationStrategy): array
    {
        // Setup mock cluster connections
        $primaryConnections = [];
        $returnMap = [];
        foreach ($slotsmap as $mapData) {
            [$start, $end, $primary, $secondary] = $mapData;

            $primaryIp = $primary[0];
            $primaryPort = $primary[1];
            $primaryConnection = $this->getMockConnection("tcp://{$primaryIp}:{$primaryPort}");

            // Mock cluster slots response
            $primaryConnection
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', ['SLOTS']
            ))
            ->willReturn($slotsmap);

            $primaryConnections[] = $primaryConnection;

            $secondaryIp = $secondary[0];
            $secondaryPort = $secondary[1];
            $connectionId = "{$secondaryIp}:{$secondaryPort}";

            $secondaryConnection = $this->getMockConnection("tcp://{$connectionId}");

            $returnMap[] = [$connectionId, $secondaryConnection];
        }

        // Setup mock cluster object
        $connections = $this->getMockBuilder(FactoryInterface::class)->getMock();
        $connections
            ->expects($this->never())
            ->method('create');
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods([
                'createConnection',
                'getRandomConnection',
            ])
            ->setConstructorArgs([
                new Factory(),
                new Parameters(),
                null,
                $replicationStrategy,
            ])
            ->getMock();

        // setup mock methods
        $cluster
            ->expects($this->once())
            ->method('getRandomConnection')
            ->willReturnOnConsecutiveCalls(...$primaryConnections);

        foreach ($primaryConnections as $primaryConnection) {
            $cluster->add($primaryConnection);
        }

        // Init slot map
        $cluster->askSlotMap();

        return [$cluster, $returnMap, $primaryConnections];
    }

    /**
     * Cover the guard clause in getReadConnection.
     *
     * @group disconnected
     */
    public function testLoadBalancingSlotRange()
    {
        $cluster = new RedisCluster(
            new Factory(),
            new Parameters(),
            null,
            new ReplicationStrategy()
        );

        $this->expectException(OutOfBoundsException::class);

        $command = new RawCommand('GET', ['foo']);
        $command->setSlot(16384);

        $cluster->getConnectionByCommand($command);
    }

    /**
     * Coverage test for empty replica slotmap.
     *
     * @group disconnected
     */
    public function testLoadBalancingEmptySlotMap()
    {
        /** @var RedisCluster|MockObject */
        $cluster = $this->getMockBuilder(RedisCluster::class)
            ->onlyMethods(['getConnectionBySlot'])
            ->setConstructorArgs([
                new Factory(),
                new Parameters(),
                null,
                new ReplicationStrategy(),
            ])
            ->getMock();

        $command = new RawCommand('GET', ['foo']);

        $cluster->expects($this->once())
            ->method('getConnectionBySlot')
            ->willReturn($this->getMockConnection(''));

        $cluster->getConnectionByCommand($command);
    }

    /**
     * Test some basic key setting and getting.
     *
     * @requiresRedisVersion >= 2.0.0
     * @group connected
     * @group cluster
     */
    public function testLoadBalancingClusterIntegration()
    {
        $options = ['loadBalancing' => true];
        $client = $this->createClient(null, $options);

        // Test normal set/get
        foreach (range(0, 100) as $i) {
            $key = "foo{$i}";
            $value = "bar{$i}";
            $client->set($key, $value);

            $gotValue = $client->get($key);
            $this->assertEquals($value, $gotValue);

            $client->del($key);

            $newValue = $client->get($key);
            $this->assertNull($newValue);
        }

        // Test hset/hget
        foreach (range(0, 100) as $i) {
            $key = 'key' . ($i % 4);
            $field = "foo{$i}";
            $value = "bar{$i}";
            $client->hset($key, $field, $value);

            $gotValue = $client->hget($key, $field);
            $this->assertEquals($value, $gotValue);

            $client->hdel($key, [$field]);

            $newValue = $client->hget($key, $field);
            $this->assertNull($newValue);
        }
    }
}
