<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection;

use PredisTestCase;
use Predis\ResponseError;
use Predis\Command\RawCommand;
use Predis\Profile\ServerProfile;

/**
 *
 */
class RedisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExposesCommandHashStrategy()
    {
        $cluster = new RedisCluster();
        $this->assertInstanceOf('Predis\Cluster\RedisClusterHashStrategy', $cluster->getCommandHashStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster();
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

        $cluster = new RedisCluster();
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

        $cluster = new RedisCluster();
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

        $cluster = new RedisCluster();
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
        $connection1->expects($this->any())
                    ->method('connect')
                    ->will($this->returnCallback(function () use (&$connect1) {
                        $connect1 = true;
                    }));
        $connection1->expects($this->any())
                    ->method('isConnected')
                    ->will($this->returnCallback(function () use (&$connect1) {
                        return $connect1;
                    }));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->any())
                    ->method('connect')
                    ->will($this->returnCallback(function () use (&$connect2) {
                        $connect2 = true;
                    }));
        $connection2->expects($this->any())
                    ->method('isConnected')
                    ->will($this->returnCallback(function () use (&$connect2) {
                        return $connect2;
                    }));

        $cluster = new RedisCluster();
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
        $connection1->expects($this->once())->method('disconnect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->once())->method('disconnect');

        $cluster = new RedisCluster();
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
        $connection1->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(true));

        $cluster = new RedisCluster();
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
        $connection1->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertFalse($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testCanReturnAnIteratorForConnections()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertInstanceOf('Iterator', $iterator = $cluster->getIterator());
        $connections = iterator_to_array($iterator);

        $this->assertSame($connection1, $connections[0]);
        $this->assertSame($connection2, $connections[1]);
    }

    /**
     * @group disconnected
     */
    public function testCanAssignConnectionsToCustomSlots()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->setSlots(0, 1364, '127.0.0.1:6379');
        $cluster->setSlots(1365, 2729, '127.0.0.1:6380');
        $cluster->setSlots(2730, 4095, '127.0.0.1:6381');

        $expectedMap = array_merge(
            array_fill(0, 1365, '127.0.0.1:6379'),
            array_fill(1364, 1365, '127.0.0.1:6380'),
            array_fill(2729, 1366, '127.0.0.1:6381')
        );

        $this->assertSame($expectedMap, $cluster->getSlotsMap());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionResetsSlotsMap()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster();
        $cluster->add($connection1);

        $cluster->setSlots(0, 4095, '127.0.0.1:6379');
        $this->assertSame(array_fill(0, 4096, '127.0.0.1:6379'), $cluster->getSlotsMap());

        $cluster->add($connection2);

        $this->assertEmpty($cluster->getSlotsMap());
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionResetsSlotsMap()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->setSlots(0, 2047, '127.0.0.1:6379');
        $cluster->setSlots(2048, 4095, '127.0.0.1:6380');

        $expectedMap = array_merge(
            array_fill(0, 2048, '127.0.0.1:6379'),
            array_fill(2048, 2048, '127.0.0.1:6380')
        );

        $this->assertSame($expectedMap, $cluster->getSlotsMap());

        $cluster->remove($connection1);
        $this->assertEmpty($cluster->getSlotsMap());
    }

    /**
     * @group disconnected
     */
    public function testCanAssignConnectionsToCustomSlotsFromParameters()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379?slots=0-5460');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380?slots=5461-10921');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=10922-16383');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $expectedMap = array_merge(
            array_fill(0, 5461, '127.0.0.1:6379'),
            array_fill(5460, 5461, '127.0.0.1:6380'),
            array_fill(10921, 5462, '127.0.0.1:6381')
        );

        $cluster->buildSlotsMap();

        $this->assertSame($expectedMap, $cluster->getSlotsMap());
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingSlotID()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertSame($connection1, $cluster->getConnectionBySlot(0));
        $this->assertSame($connection2, $cluster->getConnectionBySlot(5461));
        $this->assertSame($connection3, $cluster->getConnectionBySlot(10922));

        $cluster->setSlots(5461, 7096, '127.0.0.1:6380');
        $this->assertSame($connection2, $cluster->getConnectionBySlot(5461));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingCommandInstance()
    {
        $profile = ServerProfile::getDefault();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $set = $profile->createCommand('set', array('node:1001', 'foobar'));
        $get = $profile->createCommand('get', array('node:1001'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('node:1048', 'foobar'));
        $get = $profile->createCommand('get', array('node:1048'));
        $this->assertSame($connection2, $cluster->getConnection($set));
        $this->assertSame($connection2, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('node:1082', 'foobar'));
        $get = $profile->createCommand('get', array('node:1082'));
        $this->assertSame($connection3, $cluster->getConnection($set));
        $this->assertSame($connection3, $cluster->getConnection($get));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection()
    {
        $command = ServerProfile::getDefault()->createCommand('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->once())->method('writeCommand')->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->never())->method('writeCommand');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->writeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testReadsCommandFromCorrectConnection()
    {
        $command = ServerProfile::getDefault()->createCommand('get', array('node:1050'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->never())->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->once())->method('readResponse')->with($command);

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->readResponse($command);
    }

    /**
     * @group disconnected
     */
    public function testDoesNotSupportKeyTags()
    {
        $profile = ServerProfile::getDefault();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $profile->createCommand('set', array('{node:1001}:foo', 'foobar'));
        $get = $profile->createCommand('get', array('{node:1001}:foo'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('{node:1001}:bar', 'foobar'));
        $get = $profile->createCommand('get', array('{node:1001}:bar'));
        $this->assertSame($connection2, $cluster->getConnection($set));
        $this->assertSame($connection2, $cluster->getConnection($get));
    }

    /**
     * @group disconnected
     */
    public function testAskResponseWithConnectionInPool()
    {
        $askResponse = new ResponseError('ASK 1970 127.0.0.1:6380');

        $command = ServerProfile::getDefault()->createCommand('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->exactly(2))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls($askResponse, 'foobar'));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->at(2))
                    ->method('executeCommand')
                    ->with($this->isRedisCommand('ASKING'));
        $connection2->expects($this->at(3))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->returnValue('foobar'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory');
        $factory->expects($this->never())->method('create');

        $cluster = new RedisCluster($factory);
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
        $askResponse = new ResponseError('ASK 1970 127.0.0.1:6381');

        $command = ServerProfile::getDefault()->createCommand('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->exactly(2))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls($askResponse, 'foobar'));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->never())
                    ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3->expects($this->at(0))
                    ->method('executeCommand')
                    ->with($this->isRedisCommand('ASKING'));
        $connection3->expects($this->at(1))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->returnValue('foobar'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory');
        $factory->expects($this->once())
                ->method('create')
                ->with(array('host' => '127.0.0.1', 'port' => '6381'))
                ->will($this->returnValue($connection3));

        $cluster = new RedisCluster($factory);
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
        $movedResponse = new ResponseError('MOVED 1970 127.0.0.1:6380');

        $command = ServerProfile::getDefault()->createCommand('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->exactly(1))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->returnValue($movedResponse));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->exactly(2))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls('foobar', 'foobar'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory');
        $factory->expects($this->never())->method('create');

        $cluster = new RedisCluster($factory);
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
        $movedResponse = new ResponseError('MOVED 1970 127.0.0.1:6381');

        $command = ServerProfile::getDefault()->createCommand('get', array('node:1001'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection1->expects($this->once())
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->returnValue($movedResponse));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $connection2->expects($this->never())
                    ->method('executeCommand');

        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection3->expects($this->exactly(2))
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->onConsecutiveCalls('foobar', 'foobar'));

        $factory = $this->getMock('Predis\Connection\ConnectionFactory');
        $factory->expects($this->once())
                ->method('create')
                ->with(array('host' => '127.0.0.1', 'port' => '6381'))
                ->will($this->returnValue($connection3));

        $cluster = new RedisCluster($factory);
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame('foobar', $cluster->executeCommand($command));
        $this->assertSame(3, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testFetchSlotsMapFromClusterWithClusterNodesCommand()
    {
        $output =<<<EOS
1284a54dc3245d305ae5e3f5507579f9a3e4bd80 10.1.0.51:6387 master - 0 1400000953094 20 connected 12288-13311
1fb644aa255ef92db789a93cc834f597e9a3800b 10.1.0.52:6392 master - 0 1400000955139 15 connected 3072-4095
fe814c66d4171db548b6dce537a88c3994cd7197 10.1.0.51:6391 slave 7ca7ed3780c405ee50757550e19ade5921097d25 0 1400000954111 5 connected
b3a3b7564c75c096bdc72fc314500cdd666adf87 :0 myself,master - 0 0 18 connected 6144-7167
67b25b19a07b56a155e25145957dce82a6605f26 10.1.0.51:6388 master - 0 1400000954111 21 connected 14336-15359
ba6e596ae5382ad05f1df5abaaa1489a0e0b2449 10.1.0.52:6398 master - 0 1400000953094 13 connected 15360-16383
7ca7ed3780c405ee50757550e19ade5921097d25 10.1.0.52:6391 master - 0 1400000954111 5 connected 1024-2047
4d90ab772120a49b177eb7bd4799b592f5048e16 10.1.0.51:6394 slave fa9b9fae41e3d53d2a901dbfe6fd1527c874768c 0 1400000955139 26 connected
5731adc383f5f7afbfcca2ee75d17609e436b146 10.1.0.51:6398 slave ba6e596ae5382ad05f1df5abaaa1489a0e0b2449 0 1400000953094 21 connected
fd0bd0cd8461bd44b759fefaabaf541788ec65cf 10.1.0.52:6396 master - 0 1400000955139 7 connected 11264-12287
d012ddf044d0ef5d72d171f8af1e700488b79241 10.1.0.52:6393 master - 0 1400000953604 10 connected 5120-6143
462ab20db112a007f3ffc4e7e105b0c963aa8981 10.1.0.52:6382 slave a1948d989b092bc96ff91019d9d3b74361edcdfa 0 1400000954111 18 connected
f468a7018ff5a0701a1b614b8ed1b400cddcd578 10.1.0.52:6385 slave 2a1845f27d0f24904d67e2f2cd3e7380949e9d3a 0 1400000953604 25 connected
a48e023927edcc0d001be0535130f41d8e93adde 10.1.0.52:6383 slave a547c6de8acfbf4a97a1c341bddc2efffea83032 0 1400000955139 12 connected
2530af9ee9b8d4e359cefedd7492ba777dd1e5aa 10.1.0.51:6392 slave 1fb644aa255ef92db789a93cc834f597e9a3800b 0 1400000953603 17 connected
2d3934ef56abbe25949063474d2e408ae2fb41af 10.1.0.51:6395 slave 0f70284073a6613343fd9a2da82a0549c1efaaae 0 1400000954627 27 connected
6a0430bf8e1beed8911aa69b002ac7a00ad60428 10.1.0.52:6387 slave 1284a54dc3245d305ae5e3f5507579f9a3e4bd80 0 1400000954111 20 connected
578ad5ff1970a22419a6afca0cc97bfb729f6b47 10.1.0.51:6397 slave fd637fc700d31395d1f324348b49ad38248ba8d4 0 1400000955139 18 connected
c3083761437050f1cd80a5f95b3ff5aabba3bb97 10.1.0.51:6381 master - 0 1400000955139 11 connected 0-1023
846d3410ce8e7cea565617686f4685a35d376706 10.1.0.52:6384 slave b3a3b7564c75c096bdc72fc314500cdd666adf87 0 1400000953094 18 connected
fd637fc700d31395d1f324348b49ad38248ba8d4 10.1.0.52:6397 master - 0 1400000954627 16 connected 13312-14335
a547c6de8acfbf4a97a1c341bddc2efffea83032 10.1.0.51:6383 master - 0 1400000954627 8 connected 4096-5119
0f70284073a6613343fd9a2da82a0549c1efaaae 10.1.0.52:6395 master - 0 1400000954627 27 connected 9216-10239
2a1845f27d0f24904d67e2f2cd3e7380949e9d3a 10.1.0.51:6385 master - 0 1400000953603 25 connected 8192-9215
ba018d6d154205a47b83de33efbbe846cb6f4b1f 10.1.0.52:6381 slave c3083761437050f1cd80a5f95b3ff5aabba3bb97 0 1400000955139 11 connected
5a830c9ef0d09eb25b521395466e803052ddbef4 10.1.0.51:6386 master - 0 1400000954627 9 connected 10240-11263
91d9840437794ab1ff77b59f831ce80ffaaa4c5a 10.1.0.51:6393 slave d012ddf044d0ef5d72d171f8af1e700488b79241 0 1400000954627 14 connected
d6bdbb495fcb66ba3001c2a07c067d5aa354cf55 10.1.0.52:6388 slave 67b25b19a07b56a155e25145957dce82a6605f26 0 1400000954111 21 connected
2176380db50d687d37e37c3daeeea6e7f8feed0c 10.1.0.51:6396 slave fd0bd0cd8461bd44b759fefaabaf541788ec65cf 0 1400000953603 22 connected
c93ffb4a2ff815f9029156383b00f106234b9546 10.1.0.52:6386 slave 5a830c9ef0d09eb25b521395466e803052ddbef4 0 1400000953604 9 connected
a1948d989b092bc96ff91019d9d3b74361edcdfa 10.1.0.51:6382 master - 0 1400000953094 17 connected 2048-3071
fa9b9fae41e3d53d2a901dbfe6fd1527c874768c 10.1.0.52:6394 master - 0 1400000953094 1 connected 7168-8191
EOS;

        $command = RawCommand::create('CLUSTER', 'NODES');

        $connection1 = $this->getMockConnection('tcp://10.1.0.51:6384');
        $connection1->expects($this->once())
                    ->method('executeCommand')
                    ->with($command)
                    ->will($this->returnValue($output));

        $factory = $this->getMock('Predis\Connection\ConnectionFactoryInterface');

        $cluster = new RedisCluster($factory);
        $cluster->add($connection1);

        $cluster->askClusterNodes();

        $this->assertSame($cluster->getConnectionBySlot('6144'), $connection1);
    }

    /**
     * @group disconnected
     * @expectedException Predis\NotSupportedException
     * @expectedExceptionMessage Cannot use PING with redis-cluster
     */
    public function testThrowsExceptionOnNonSupportedCommand()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping');

        $cluster = new RedisCluster();
        $cluster->add($this->getMockConnection('tcp://127.0.0.1:6379'));

        $cluster->getConnection($ping);
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379?slots=0-1364');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380?slots=1365-2729');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6381?slots=2730-4095');

        $cluster = new RedisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $cluster->buildSlotsMap();

        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a base mocked connection from Predis\Connection\SingleConnectionInterface.
     *
     * @param  mixed $parameters Optional parameters.
     * @return mixed
     */
    protected function getMockConnection($parameters = null)
    {
        $connection = $this->getMock('Predis\Connection\SingleConnectionInterface');

        if ($parameters) {
            $parameters = new ConnectionParameters($parameters);
            $hash = "{$parameters->host}:{$parameters->port}";

            $connection->expects($this->any())
                       ->method('getParameters')
                       ->will($this->returnValue($parameters));
            $connection->expects($this->any())
                       ->method('__toString')
                       ->will($this->returnValue($hash));
        }

        return $connection;
    }
}
