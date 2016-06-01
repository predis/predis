<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Aggregate;

use Predis\Profile;
use PredisTestCase;

/**
 *
 */
class PredisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExposesCommandHashStrategy()
    {
        $cluster = new PredisCluster();
        $this->assertInstanceOf('Predis\Cluster\PredisStrategy', $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster()
    {
        $connection1 = $this->getMockConnection();
        $connection2 = $this->getMockConnection();

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(2, count($cluster));
        $this->assertSame($connection1, $cluster->getConnectionById(0));
        $this->assertSame($connection2, $cluster->getConnectionById(1));
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToClusterUsesConnectionAlias()
    {
        $connection1 = $this->getMockConnection('tcp://host1:7001?alias=node1');
        $connection2 = $this->getMockConnection('tcp://host1:7002?alias=node2');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(2, count($cluster));
        $this->assertSame($connection1, $cluster->getConnectionById('node1'));
        $this->assertSame($connection2, $cluster->getConnectionById('node2'));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromCluster()
    {
        $connection1 = $this->getMockConnection();
        $connection2 = $this->getMockConnection();
        $connection3 = $this->getMockConnection();

        $cluster = new PredisCluster();
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
        $connection1 = $this->getMockConnection();
        $connection2 = $this->getMockConnection('tcp://host1:7001?alias=node2');
        $connection3 = $this->getMockConnection('tcp://host1:7002?alias=node3');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertTrue($cluster->removeById(0));
        $this->assertTrue($cluster->removeById('node2'));
        $this->assertFalse($cluster->removeById('node4'));
        $this->assertSame(1, count($cluster));
    }

    /**
     * @group disconnected
     */
    public function testConnectForcesAllConnectionsToConnect()
    {
        $connection1 = $this->getMockConnection();
        $connection1->expects($this->once())->method('connect');

        $connection2 = $this->getMockConnection();
        $connection2->expects($this->once())->method('connect');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->connect();
    }

    /**
     * @group disconnected
     */
    public function testDisconnectForcesAllConnectionsToDisconnect()
    {
        $connection1 = $this->getMockConnection();
        $connection1->expects($this->once())->method('disconnect');

        $connection2 = $this->getMockConnection();
        $connection2->expects($this->once())->method('disconnect');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsTrueIfAtLeastOneConnectionIsOpen()
    {
        $connection1 = $this->getMockConnection();
        $connection1->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection();
        $connection2->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(true));

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsFalseIfAllConnectionsAreClosed()
    {
        $connection1 = $this->getMockConnection();
        $connection1->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection();
        $connection2->expects($this->once())
                    ->method('isConnected')
                    ->will($this->returnValue(false));

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertFalse($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testCanReturnAnIteratorForConnections()
    {
        $connection1 = $this->getMockConnection();
        $connection2 = $this->getMockConnection();

        $cluster = new PredisCluster();
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
    public function testReturnsCorrectConnectionUsingKey()
    {
        $connection1 = $this->getMockConnection('tcp://host1:7001');
        $connection2 = $this->getMockConnection('tcp://host1:7002');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame($connection1, $cluster->getConnectionByKey('node01:5431'));
        $this->assertSame($connection2, $cluster->getConnectionByKey('node02:3212'));
        $this->assertSame($connection1, $cluster->getConnectionByKey('prefix:{node01:5431}'));
        $this->assertSame($connection2, $cluster->getConnectionByKey('prefix:{node02:3212}'));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingCommandInstance()
    {
        $profile = Profile\Factory::getDefault();

        $connection1 = $this->getMockConnection('tcp://host1:7001');
        $connection2 = $this->getMockConnection('tcp://host1:7002');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $profile->createCommand('set', array('node01:5431', 'foobar'));
        $get = $profile->createCommand('get', array('node01:5431'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('prefix:{node01:5431}', 'foobar'));
        $get = $profile->createCommand('get', array('prefix:{node01:5431}'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('node02:3212', 'foobar'));
        $get = $profile->createCommand('get', array('node02:3212'));
        $this->assertSame($connection2, $cluster->getConnection($set));
        $this->assertSame($connection2, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('prefix:{node02:3212}', 'foobar'));
        $get = $profile->createCommand('get', array('prefix:{node02:3212}'));
        $this->assertSame($connection2, $cluster->getConnection($set));
        $this->assertSame($connection2, $cluster->getConnection($get));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Cannot use 'PING' over clusters of connections.
     */
    public function testThrowsExceptionOnNonShardableCommand()
    {
        $ping = Profile\Factory::getDefault()->createCommand('ping');

        $cluster = new PredisCluster();
        $cluster->add($this->getMockConnection());

        $cluster->getConnection($ping);
    }

    /**
     * @group disconnected
     */
    public function testSupportsKeyHashTags()
    {
        $profile = Profile\Factory::getDefault();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $profile->createCommand('set', array('{node:1001}:foo', 'foobar'));
        $get = $profile->createCommand('get', array('{node:1001}:foo'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));

        $set = $profile->createCommand('set', array('{node:1001}:bar', 'foobar'));
        $get = $profile->createCommand('get', array('{node:1001}:bar'));
        $this->assertSame($connection1, $cluster->getConnection($set));
        $this->assertSame($connection1, $cluster->getConnection($get));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection()
    {
        $command = Profile\Factory::getDefault()->createCommand('get', array('node01:5431'));

        $connection1 = $this->getMockConnection('tcp://host1:7001');
        $connection1->expects($this->once())->method('writeRequest')->with($command);

        $connection2 = $this->getMockConnection('tcp://host1:7002');
        $connection2->expects($this->never())->method('writeRequest');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->writeRequest($command);
    }

    /**
     * @group disconnected
     */
    public function testReadsCommandFromCorrectConnection()
    {
        $command = Profile\Factory::getDefault()->createCommand('get', array('node02:3212'));

        $connection1 = $this->getMockConnection('tcp://host1:7001');
        $connection1->expects($this->never())->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://host1:7002');
        $connection2->expects($this->once())->method('readResponse')->with($command);

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->readResponse($command);
    }

    /**
     * @group disconnected
     */
    public function testExecutesCommandOnCorrectConnection()
    {
        $command = Profile\Factory::getDefault()->createCommand('get', array('node01:5431'));

        $connection1 = $this->getMockConnection('tcp://host1:7001');
        $connection1->expects($this->once())->method('executeCommand')->with($command);

        $connection2 = $this->getMockConnection('tcp://host1:7002');
        $connection2->expects($this->never())->method('executeCommand');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandOnEachNode()
    {
        $ping = Profile\Factory::getDefault()->createCommand('ping', array());

        $connection1 = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection1->expects($this->once())
                    ->method('executeCommand')
                    ->with($ping)
                    ->will($this->returnValue(true));

        $connection2 = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection2->expects($this->once())
                    ->method('executeCommand')
                    ->with($ping)
                    ->will($this->returnValue(false));

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(array(true, false), $cluster->executeCommandOnNodes($ping));
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $connection1 = $this->getMockConnection('tcp://host1?alias=first');
        $connection2 = $this->getMockConnection('tcp://host2?alias=second');

        $cluster = new PredisCluster();
        $cluster->add($connection1);
        $cluster->add($connection2);

        // We use the following line to initialize the underlying hashring.
        $cluster->getConnectionByKey('foo');
        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }
}
