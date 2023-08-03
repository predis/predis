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

use Predis\Command\CommandInterface;
use Predis\Connection\Parameters;
use PredisTestCase;

class PredisClusterTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testExposesCommandHashStrategy(): void
    {
        $cluster = new PredisCluster(new Parameters());
        $this->assertInstanceOf('Predis\Cluster\PredisStrategy', $cluster->getClusterStrategy());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsToCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertCount(2, $cluster);
        $this->assertSame($connection1, $cluster->getConnectionById('127.0.0.1:7001'));
        $this->assertSame($connection2, $cluster->getConnectionById('127.0.0.1:7002'));
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsWithAliasParameterToCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=node01');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002?alias=node02');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertCount(2, $cluster);
        $this->assertSame($connection1, $cluster->getConnectionByAlias('node01'));
        $this->assertSame($connection2, $cluster->getConnectionByAlias('node02'));
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromCluster(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=node01');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:7003');

        $cluster = new PredisCluster(new Parameters());

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
    public function testConnectForcesAllConnectionsToConnect(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('connect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('connect');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->connect();
    }

    /**
     * @group disconnected
     */
    public function testDisconnectForcesAllConnectionsToDisconnect(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('disconnect');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('disconnect');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsTrueIfAtLeastOneConnectionIsOpen(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertTrue($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsFalseIfAllConnectionsAreClosed(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertFalse($cluster->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testCanReturnAnIteratorForConnections(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertInstanceOf('Iterator', $iterator = $cluster->getIterator());
        $connections = iterator_to_array($iterator);

        $this->assertSame([
            '127.0.0.1:7001' => $connection1,
            '127.0.0.1:7002' => $connection2,
        ], iterator_to_array($iterator));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingSlot(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $this->assertSame($connection1, $cluster->getConnectionBySlot(1839357934));
        $this->assertSame($connection2, $cluster->getConnectionBySlot(2146453549));
    }

    /**
     * @group disconnected
     */
    public function testReturnsCorrectConnectionUsingKey(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster(new Parameters());

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
    public function testReturnsCorrectConnectionUsingCommandInstance(): void
    {
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $set = $commands->create('set', ['node01:5431', 'foobar']);
        $get = $commands->create('get', ['node01:5431']);
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['prefix:{node01:5431}', 'foobar']);
        $get = $commands->create('get', ['prefix:{node01:5431}']);
        $this->assertSame($connection1, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection1, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['node02:3212', 'foobar']);
        $get = $commands->create('get', ['node02:3212']);
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));

        $set = $commands->create('set', ['prefix:{node02:3212}', 'foobar']);
        $get = $commands->create('get', ['prefix:{node02:3212}']);
        $this->assertSame($connection2, $cluster->getConnectionByCommand($set));
        $this->assertSame($connection2, $cluster->getConnectionByCommand($get));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonShardableCommand(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("Cannot use 'PING' over clusters of connections.");

        $ping = $this->getCommandFactory()->create('ping');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($this->getMockConnection('tcp://127.0.0.1:6379'));

        $cluster->getConnectionByCommand($ping);
    }

    /**
     * @group disconnected
     */
    public function testSupportsKeyHashTags(): void
    {
        $commands = $this->getCommandFactory();

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6379');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $cluster = new PredisCluster(new Parameters());

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
    public function testWritesCommandToCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', ['node01:5431']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('writeRequest')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->never())
            ->method('writeRequest');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->writeRequest($command);
    }

    /**
     * @group disconnected
     */
    public function testReadsCommandFromCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', ['node02:3212']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->never())
            ->method('readResponse');

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->once())
            ->method('readResponse')
            ->with($command);

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->readResponse($command);
    }

    /**
     * @group disconnected
     */
    public function testExecutesCommandOnCorrectConnection(): void
    {
        $command = $this->getCommandFactory()->create('get', ['node01:5431']);

        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001');
        $connection1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($command);

        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002');
        $connection2
            ->expects($this->never())
            ->method('executeCommand');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        $cluster->executeCommand($command);
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized(): void
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:7001?alias=first');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:7002?alias=second');

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);

        // We use the following line to initialize the underlying hashring.
        $cluster->getConnectionByKey('foo');
        $unserialized = unserialize(serialize($cluster));

        $this->assertEquals($cluster, $unserialized);
    }

    /**
     * @group disconnected
     */
    public function testGetParameters(): void
    {
        $connection = $this->getMockConnection('tcp://127.0.0.1:7001?protocol=3');
        $expectedParameters = new Parameters([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 7001,
            'protocol' => '3',
        ]);

        $cluster = new PredisCluster($expectedParameters);

        $this->assertEquals($expectedParameters, $cluster->getParameters());
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandOnEachNode(): void
    {
        $mockCommand = $this->getMockBuilder(CommandInterface::class)->getMock();

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

        $cluster = new PredisCluster(new Parameters());

        $cluster->add($connection1);
        $cluster->add($connection2);
        $cluster->add($connection3);

        $this->assertEquals(['response1', 'response2', 'response3'], $cluster->executeCommandOnEachNode($mockCommand));
    }
}
