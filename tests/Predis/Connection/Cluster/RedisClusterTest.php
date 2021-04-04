<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Cluster;

use PredisTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Cluster;
use Predis\Command;
use Predis\Connection;
use Predis\Response;

/**
 *
 */
class RedisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testAcceptsCustomConnectionFactory(): void
    {
        /** @var Connection\FactoryInterface */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $cluster = new RedisCluster($factory);

        $this->assertSame($factory, $cluster->getConnectionFactory());
    }

    /**
     * @group disconnected
     */
    public function testUsesRedisClusterStrategyByDefault(): void
    {
        $cluster = new RedisCluster(new Connection\Factory());

        $this->assertInstanceOf('Predis\Cluster\RedisStrategy', $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCustomClusterStrategy(): void
    {
        /** @var Cluster\StrategyInterface */
        $strategy = $this->getMockBuilder('Predis\Cluster\StrategyInterface')->getMock();

        $cluster = new RedisCluster(new Connection\Factory(), $strategy);

        $this->assertSame($strategy, $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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
    public function testConnectPicksRandomConnection(): void
    {
        $connect1 = false;
        $connect2 = false;

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->any())
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
            ->expects($this->any())
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

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->connect();

        $this->assertTrue($cluster->isConnected());

        if ($connect1) {
            $this->assertTrue($connect1);
            $this->assertFalse($connect2);
        } else {
            $this->assertFalse($connect1);
            $this->assertTrue($connect2);
        }
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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());
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
        $slotsmap = array(
            array(0, 5460, array('127.0.0.1', 6381), array()),
            array(5461, 10922, array('127.0.0.1', 6383), array()),
            array(10923, 16383, array('127.0.0.1', 6384), array()),
        );

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willReturn($slotsmap);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383');
        $connection4 = $this->getMockConnection('tcp://127.0.0.1:6384');

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                array(
                    array(
                        'host' => '127.0.0.1',
                        'port' => '6383',
                    )
                ),
                array(
                    array(
                        'host' => '127.0.0.1',
                        'port' => '6384',
                    )
                )
            )
            ->willReturnOnConsecutiveCalls(
                $connection3,
                $connection4
            );

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var Connection\Cluster\RedisCluster|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->onlyMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $set = $commands->create('set', array('node:1001', 'foobar'));
        $get = $commands->create('get', array('node:1001'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', array('node:1048', 'foobar'));
        $get = $commands->create('get', array('node:1048'));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', array('node:1082', 'foobar'));
        $get = $commands->create('get', array('node:1082'));
        $this->assertSame($connection3, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection3, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('writeRequest')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('writeRequest');

        $cluster = new RedisCluster(new Connection\Factory());
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
        $command = $this->getCommandFactory()->create('get', array('node:1050'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->never())
            ->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('readResponse')
            ->with($command);

        $cluster = new RedisCluster(new Connection\Factory());
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
        $slotsmap = array(
            array(0, 5460, array('127.0.0.1', 9381), array()),
            array(5461, 10922, array('127.0.0.1', 6382), array()),
            array(10923, 16383, array('127.0.0.1', 6383), array()),
        );

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:1001')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willReturn($slotsmap);

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willReturn($slotsmap);

        $connection4 = $this->getMockConnection('tcp://127.0.0.1:9381');
        $connection4
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                array($this->isRedisCommand('GET', array('node:1001'))),
                array($this->isRedisCommand('GET', array('node:5001')))
            )
            ->willReturnOnConsecutiveCalls(
                'value:1001',
                'value:5001'
            );

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '9381',
            ))
            ->willReturn($connection4);

        $cluster = new RedisCluster($factory);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame('value:1001', $cluster->executeCommand(
            Command\RawCommand::create('get', 'node:1001')
        ));

        $this->assertSame('value:5001', $cluster->executeCommand(
            Command\RawCommand::create('get', 'node:5001')
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
                'GET', array('node:1001')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5501-11000');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:1001')
            ))
            ->willReturn(
                new Response\Error('MOVED 1970 127.0.0.1:9381')
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
                'GET', array('node:1001')
            ))
            ->willReturn('value:1001');

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
             ->method('create')
             ->with(array(
                'host' => '127.0.0.1',
                'port' => '9381',
              ))
             ->willReturn($connection4);

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var Connection\Cluster\RedisCluster|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->onlyMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
            ->getMock();
        $cluster
            ->expects($this->never())
            ->method('getRandomConnection');

        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame('value:1001', $cluster->executeCommand(
            Command\RawCommand::create('get', 'node:1001')
        ));
    }

    /**
     * @group disconnected
     */
    public function testThrowsClientExceptionWhenExecutingCommandWithEmptyPool(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('No connections available in the pool');

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory);

        $cluster->executeCommand(
            Command\RawCommand::create('get', 'node:1001')
        );
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapReturnEmptyArrayOnEmptyConnectionsPool(): void
    {
        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory);
        $cluster->askSlotMap();

        $this->assertCount(0, $cluster->getSlotMap());
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapRetriesOnDifferentNodeOnConnectionFailure(): void
    {
        $slotsmap = array(
            array(0, 5460, array('127.0.0.1', 9381), array()),
            array(5461, 10922, array('127.0.0.1', 6382), array()),
            array(10923, 16383, array('127.0.0.1', 6383), array()),
        );

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6383]')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willReturn($slotsmap);

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var Connection\Cluster\RedisCluster|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->onlyMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
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
        $this->expectException('Predis\Connection\ConnectionException');
        $this->expectExceptionMessage("Unknown connection error [127.0.0.1:6382]");

        $slotsmap = array(
            array(0, 5460, array('127.0.0.1', 9381), array()),
            array(5461, 10922, array('127.0.0.1', 6382), array()),
            array(10923, 16383, array('127.0.0.1', 6383), array()),
        );

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5460');
        $connection1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            );

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willThrowException(
                new Connection\ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6382]')
            );

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->never())
            ->method('executeCommand');

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        /** @var Connection\Cluster\RedisCluster|MockObject */
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->onlyMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
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

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $commands->create('set', array('{node:1001}:foo', 'foobar'));
        $get = $commands->create('get', array('{node:1001}:foo'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', array('{node:1001}:bar', 'foobar'));
        $get = $commands->create('get', array('{node:1001}:bar'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testAskResponseWithConnectionInPool(): void
    {
        $askResponse = new Response\Error('ASK 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

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
                array($this->isRedisCommand('ASKING')),
                array($this->isRedisCommand($command))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                'foobar'
            );

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory);
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
        $askResponse = new Response\Error('ASK 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

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
                array($this->isRedisCommand('ASKING')),
                array($this->isRedisCommand($command))
            )
            ->willReturnOnConsecutiveCalls(
                new Response\Status('OK'),
                'foobar'
            );

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
            ))
            ->willReturn($connection3);

        $cluster = new RedisCluster($factory);
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
        $movedResponse = new Response\Error('MOVED 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

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

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory->expects($this->never())->method('create');

        $cluster = new RedisCluster($factory);
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
        $movedResponse = new Response\Error('MOVED 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

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

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
            ))
            ->willReturn($connection3);

        $cluster = new RedisCluster($factory);
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
        $movedResponse = new Response\Error('MOVED 1970 2001:db8:0:f101::2:6379');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

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

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '2001:db8:0:f101::2',
                'port' => '6379',
            ))
            ->willReturn($connection2);

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testFetchSlotsMapFromClusterWithClusterSlotsCommand(): void
    {
        $response = array(
            array(12288, 13311, array('10.1.0.51', 6387), array('10.1.0.52', 6387)),
            array(3072,  4095, array('10.1.0.52', 6392), array('10.1.0.51', 6392)),
            array(6144,  7167, array('', 6384), array('10.1.0.52', 6384)),
            array(14336, 15359, array('10.1.0.51', 6388), array('10.1.0.52', 6388)),
            array(15360, 16383, array('10.1.0.52', 6398), array('10.1.0.51', 6398)),
            array(1024,  2047, array('10.1.0.52', 6391), array('10.1.0.51', 6391)),
            array(11264, 12287, array('10.1.0.52', 6396), array('10.1.0.51', 6396)),
            array(5120,  6143, array('10.1.0.52', 6393), array('10.1.0.51', 6393)),
            array(0,  1023, array('10.1.0.51', 6381), array('10.1.0.52', 6381)),
            array(13312, 14335, array('10.1.0.52', 6397), array('10.1.0.51', 6397)),
            array(4096,  5119, array('10.1.0.51', 6383), array('10.1.0.52', 6383)),
            array(9216, 10239, array('10.1.0.52', 6395), array('10.1.0.51', 6395)),
            array(8192,  9215, array('10.1.0.51', 6385), array('10.1.0.52', 6385)),
            array(10240, 11263, array('10.1.0.51', 6386), array('10.1.0.52', 6386)),
            array(2048,  3071, array('10.1.0.51', 6382), array('10.1.0.52', 6382)),
            array(7168,  8191, array('10.1.0.52', 6394), array('10.1.0.51', 6394)),
        );

        $connection1 = $this->getMockConnection('tcp://10.1.0.51:6384');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->willReturn($response);

        /** @var Connection\FactoryInterface */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $cluster = new RedisCluster($factory);

        $cluster->add($connection1);

        $cluster->askSlotMap();

        $this->assertSame($cluster->getConnectionBySlot('6144'), $connection1);
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapToRedisClusterOnMovedResponseByDefault(): void
    {
        $cmdGET = Command\RawCommand::create('GET', 'node:1001');
        $rspMOVED = new Response\Error('MOVED 1970 127.0.0.1:6380');
        $rspSlotsArray = array(
            array(0,  8191, array('127.0.0.1', 6379)),
            array(8192, 16383, array('127.0.0.1', 6380)),
        );

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
                array($this->isRedisCommand('CLUSTER', array('SLOTS'))),
                array($this->isRedisCommand($cmdGET))
            )
            ->willReturnOnConsecutiveCalls(
                $rspSlotsArray,
                'foobar'
            );

        /** @var Connection\FactoryInterface|MockObject */
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6380',
            ))
            ->willReturn($connection2);

        $cluster = new RedisCluster($factory);

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

        $cluster = new RedisCluster(new Connection\Factory());

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

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->buildSlotMap();

        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }
}
