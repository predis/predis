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
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(2, count($cluster));
        $this->assertSame($connection1, $cluster->getConnectionById('127.0.0.1:7001'));
        $this->assertSame($connection2, $cluster->getConnectionById('127.0.0.1:7002'));
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsWithAliasParameterToCluster()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=node01');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002?alias=node02');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame(2, count($cluster));
        $this->assertSame($connection1, $cluster->getConnectionByAlias('node01'));
        $this->assertSame($connection2, $cluster->getConnectionByAlias('node02'));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromCluster()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=node01');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:7003');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->remove($connection1));
        $this->assertNull($cluster->getConnectionByAlias('node02'));

        $this->assertFalse($cluster->remove($connection3));

        $this->assertCount(1, $cluster);
    }

    /**
     * @group disconnected
     */
    public function testConnectForcesAllConnectionsToConnect()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('connect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('connect');

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
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('disconnect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('disconnect');

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
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
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
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
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
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertInstanceOf('Iterator', $iterator = $cluster->getIterator());
        $connections = iterator_to_array($iterator);

        $this->assertSame(array(
            '127.0.0.1:7001' => $connection1,
            '127.0.0.1:7002' => $connection2,
        ), iterator_to_array($iterator));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingSlot()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame($connection1, $cluster->getConnectionBySlot(1839357934));
        $this->assertSame($connection2, $cluster->getConnectionBySlot(2146453549));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingKey()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

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
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $commands->createCommand('set', array('node01:5431', 'foobar'));
        $get = $commands->createCommand('get', array('node01:5431'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->createCommand('set', array('prefix:{node01:5431}', 'foobar'));
        $get = $commands->createCommand('get', array('prefix:{node01:5431}'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->createCommand('set', array('node02:3212', 'foobar'));
        $get = $commands->createCommand('get', array('node02:3212'));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));

        $set = $commands->createCommand('set', array('prefix:{node02:3212}', 'foobar'));
        $get = $commands->createCommand('get', array('prefix:{node02:3212}'));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Cannot use 'PING' over clusters of connections.
     */
    public function testThrowsExceptionOnNonShardableCommand()
    {
        $ping = $this->getCommandFactory()->createCommand('ping');

        $cluster = new PredisCluster();

        $cluster->add($this->getMockConnection('tcp://127.0.0.1:6379'));

        $cluster->getConnectionByCommand($ping);
    }

    /**
     * @group disconnected
     */
    public function testSupportsKeyHashTags()
    {
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $commands->createCommand('set', array('{node:1001}:foo', 'foobar'));
        $get = $commands->createCommand('get', array('{node:1001}:foo'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->createCommand('set', array('{node:1001}:bar', 'foobar'));
        $get = $commands->createCommand('get', array('{node:1001}:bar'));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection()
    {
        $command = $this->getCommandFactory()->createCommand('get', array('node01:5431'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('writeRequest')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->never())
            ->method('writeRequest');

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
        $command = $this->getCommandFactory()->createCommand('get', array('node02:3212'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->never())
            ->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('readResponse')
            ->with($command);

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
        $command = $this->getCommandFactory()->createCommand('get', array('node01:5431'));

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=first');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002?alias=second');

        $cluster = new PredisCluster();

        $cluster->add($connection1);
        $cluster->add($connection2);

        // We use the following line to initialize the underlying hashring.
        $cluster->getConnectionByKey('foo');
        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }
}
