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

use Predis\Command;
use Predis\Connection;
use Predis\Response;
use PredisTestCase;

/**
 *
 */
class RedisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testAcceptsCustomConnectionFactory()
    {
        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $cluster = new RedisCluster($factory);

        $this->assertSame($factory, $cluster->getConnectionFactory());
    }

    /**
     * @group disconnected
     */
    public function testUsesRedisClusterStrategyByDefault()
    {
        $cluster = new RedisCluster(new Connection\Factory());

        $this->assertInstanceOf('Predis\Cluster\RedisStrategy', $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCustomClusterStrategy()
    {
        $strategy = $this->getMockBuilder('Predis\Cluster\StrategyInterface')->getMock();

        $cluster = new RedisCluster(new Connection\Factory(), $strategy);

        $this->assertSame($strategy, $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(2, count($cluster));
        $this->assertSame($connection1, $cluster->getConnectionById('127.0.0.1:6379'));
        $this->assertSame($connection2, $cluster->getConnectionById('127.0.0.1:6380'));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromCluster()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6371');

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->remove($connection1));
        $this->assertFalse($cluster->remove($connection3));
        $this->assertSame(1, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromClusterByAlias()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->removeById('127.0.0.1:6380'));
        $this->assertFalse($cluster->removeById('127.0.0.1:6390'));
        $this->assertSame(1, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testCountReturnsNumberOfConnectionsInPool()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame(3, count($cluster));

        $cluster->remove($connection3);

        $this->assertSame(2, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testConnectPicksRandomConnection()
    {
        $connect1 = false;
        $connect2 = false;

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->any())
            ->method('connect')
            ->will($this->returnCallback(function () use (&$connect1) {
                $connect1 = true;
            }));
        $connection1
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnCallback(function () use (&$connect1) {
                return $connect1;
            }));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->any())
            ->method('connect')
            ->will($this->returnCallback(function () use (&$connect2) {
                $connect2 = true;
            }));
        $connection2
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnCallback(function () use (&$connect2) {
                return $connect2;
            }));

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
    public function testDisconnectForcesAllConnectionsToDisconnect()
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
    public function testIsConnectedReturnsTrueIfAtLeastOneConnectionIsOpen()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsFalseIfAllConnectionsAreClosed()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $cluster = new RedisCluster(new Connection\Factory());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertFalse($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorReturnsConnectionsMappedInSlotsMapWhenUseClusterSlotsIsDisabled()
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
    public function testGetIteratorReturnsConnectionsMappedInSlotsMapFetchedFromRedisCluster()
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
            ->will($this->returnValue($slotsmap));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383');
        $connection4 = $this->getMockConnection('tcp://127.0.0.1:6384');

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6383',
            ))
            ->will($this->returnValue($connection3));
        $factory
            ->expects($this->at(1))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6384',
            ))
            ->will($this->returnValue($connection4));

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->setMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
            ->getMock();
        $cluster
            ->expects($this->once())
            ->method('getRandomConnection')
            ->will($this->returnValue($connection1));

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
    public function testAddingConnectionResetsSlotsMap()
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
    public function testRemovingConnectionResetsSlotsMap()
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
    public function testCanAssignConnectionsToRangeOfSlotsFromParameters()
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
    public function testCanAssignConnectionsToSingleSlotOrRangesOfSlotsFromParameters()
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
    public function testReturnsCorrectConnectionUsingSlotID()
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
    public function testReturnsCorrectConnectionUsingCommandInstance()
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
    public function testWritesCommandToCorrectConnection()
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
    public function testReadsCommandFromCorrectConnection()
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
    public function testRetriesExecutingCommandOnConnectionFailureOnlyAfterFetchingNewSlotsMap()
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
            ->will($this->throwException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->returnValue($slotsmap));

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->returnValue($slotsmap));

        $connection4 = $this->getMockConnection('tcp://127.0.0.1:9381');
        $connection4
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:1001')
            ))
            ->will($this->returnValue('value:1001'));
        $connection4
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:5001')
            ))
            ->will($this->returnValue('value:5001'));

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '9381',
            ))
            ->will($this->returnValue($connection4));

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
    public function testRetriesExecutingCommandOnConnectionFailureButDoNotAskSlotMapWhenDisabled()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=0-5500');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:1001')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5501-11000');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('node:1001')
            ))
            ->will($this->returnValue(
                new Response\Error('MOVED 1970 127.0.0.1:9381')
            ));

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
            ->will($this->returnValue('value:1001'));

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->once())
             ->method('create')
             ->with(array(
                'host' => '127.0.0.1',
                'port' => '9381',
              ))
             ->will($this->returnValue($connection4));

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->setMethods(array('getRandomConnection'))
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
    public function testThrowsClientExceptionWhenExecutingCommandWithEmptyPool()
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('No connections available in the pool');

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
    public function testAskSlotMapReturnEmptyArrayOnEmptyConnectionsPool()
    {
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
    public function testAskSlotMapRetriesOnDifferentNodeOnConnectionFailure()
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
            ->will($this->throwException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6383]')
            ));

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->returnValue($slotsmap));

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->setMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
            ->getMock();
        $cluster
            ->expects($this->exactly(3))
            ->method('getRandomConnection')
            ->will($this->onConsecutiveCalls($connection1, $connection2, $connection3));

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->askSlotMap();

        $this->assertCount(16384, $cluster->getSlotMap());
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapHonorsRetryLimitOnMultipleConnectionFailures()
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
            ->will($this->throwException(
                new Connection\ConnectionException($connection1, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382?slots=5461-10922');
        $connection2
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($connection2, 'Unknown connection error [127.0.0.1:6382]')
            ));

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383?slots=10923-16383');
        $connection3
            ->expects($this->never())
            ->method('executeCommand');

        $factory = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        // TODO: I'm not sure about mocking a protected method, but it'll do for now
        $cluster = $this->getMockBuilder('Predis\Connection\Cluster\RedisCluster')
            ->setMethods(array('getRandomConnection'))
            ->setConstructorArgs(array($factory))
            ->getMock();
        $cluster
            ->expects($this->exactly(2))
            ->method('getRandomConnection')
            ->will($this->onConsecutiveCalls($connection1, $connection2));

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->setRetryLimit(1);

        $cluster->askSlotMap();
    }

    /**
     * @group disconnected
     */
    public function testSupportsKeyHashTags()
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
    public function testAskResponseWithConnectionInPool()
    {
        $askResponse = new Response\Error('ASK 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->will($this->onConsecutiveCalls($askResponse, 'foobar'));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->at(2))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ASKING'));
        $connection2
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue('foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory
            ->expects($this->never())
            ->method('create');

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame(2, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testAskResponseWithConnectionNotInPool()
    {
        $askResponse = new Response\Error('ASK 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->will($this->onConsecutiveCalls($askResponse, 'foobar'));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'ASKING'
            ));
        $connection3
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue('foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
            ))
            ->will($this->returnValue($connection3));

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame(2, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testMovedResponseWithConnectionInPool()
    {
        $movedResponse = new Response\Error('MOVED 1970 127.0.0.1:6380');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->exactly(1))
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue($movedResponse));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->will($this->onConsecutiveCalls('foobar', 'foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory->expects($this->never())->method('create');

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame(2, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testMovedResponseWithConnectionNotInPool()
    {
        $movedResponse = new Response\Error('MOVED 1970 127.0.0.1:6381');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue($movedResponse));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with($command)
            ->will($this->onConsecutiveCalls('foobar', 'foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
            ))
            ->will($this->returnValue($connection3));

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame(3, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testParseIPv6AddresseAndPortPairInRedirectionPayload()
    {
        $movedResponse = new Response\Error('MOVED 1970 2001:db8:0:f101::2:6379');

        $command = $this->getCommandFactory()->create('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://[2001:db8:0:f101::1]:6379');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue($movedResponse));

        $connection2 = $this->getMockConnection('tcp://[2001:db8:0:f101::2]:6379');
        $connection2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue('foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '2001:db8:0:f101::2',
                'port' => '6379',
            ))
            ->will($this->returnValue($connection2));

        $cluster = new RedisCluster($factory);
        $cluster->useClusterSlots(false);

        $cluster->add($connection1);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testFetchSlotsMapFromClusterWithClusterSlotsCommand()
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
            ->will($this->returnValue($response));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();

        $cluster = new RedisCluster($factory);

        $cluster->add($connection1);

        $cluster->askSlotMap();

        $this->assertSame($cluster->getConnectionBySlot('6144'), $connection1);
    }

    /**
     * @group disconnected
     */
    public function testAskSlotMapToRedisClusterOnMovedResponseByDefault()
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
            ->will($this->returnValue($rspMOVED));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'CLUSTER', array('SLOTS')
            ))
            ->will($this->returnValue($rspSlotsArray));
        $connection2
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($cmdGET)
            ->will($this->returnValue('foobar'));

        $factory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6380',
            ))
            ->will($this->returnValue($connection2));

        $cluster = new RedisCluster($factory);

        $cluster->add($connection1);

        $this->assertSame('foobar', $cluster->executeCommand($cmdGET));
        $this->assertSame(2, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonSupportedCommand()
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
    public function testCanBeSerialized()
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
