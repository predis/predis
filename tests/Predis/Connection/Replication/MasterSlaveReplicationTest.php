<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Connection\Replication;

use Predis\Command;
use Predis\Connection;
use Predis\Replication\ReplicationStrategy;
use Predis\Response;
use PredisTestCase;

/**
 *
 */
class MasterSlaveReplicationTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testAddingConnectionsToReplication()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame($master, $replication->getConnectionById('127.0.0.1:6379'));
        $this->assertSame($slave1, $replication->getConnectionById('127.0.0.1:6380'));
        $this->assertSame($slave2, $replication->getConnectionById('127.0.0.1:6381'));

        $this->assertSame($master, $replication->getMaster());
        $this->assertSame(array($slave1, $slave2), $replication->getSlaves());
    }

    /**
     * @group disconnected
     */
    public function testAddingConnectionsWithoutRoleParameterDefaultsToSlaveRole()
    {
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $replication = new MasterSlaveReplication();
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame(array($slave1, $slave2), $replication->getSlaves());
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromReplication()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertTrue($replication->remove($slave1));
        $this->assertFalse($replication->remove($slave2));

        $this->assertSame($master, $replication->getMaster());
        $this->assertSame(array(), $replication->getSlaves());
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByIdOnEmptyReplication()
    {
        $replication = new MasterSlaveReplication();

        $this->assertNull($replication->getConnectionById('127.0.0.1:6379'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByAlias()
    {
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6379?alias=aliased');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6380');

        $replication = new MasterSlaveReplication();
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame($slave1, $replication->getConnectionByAlias('aliased'));
        $this->assertNull($replication->getConnectionByAlias('127.0.0.1:6380'));
        $this->assertNull($replication->getConnectionByAlias('unkswn'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByAliasOnEmptyReplication()
    {
        $replication = new MasterSlaveReplication();

        $this->assertNull($replication->getConnectionByAlias('unknown'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByRole()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($master, $replication->getConnectionByRole('master'));
        $this->assertSame($slave1, $replication->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByRoleOnEmptyReplication()
    {
        $replication = new MasterSlaveReplication();

        $this->assertNull($replication->getConnectionByRole('master'));
        $this->assertNull($replication->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByRoleUnknown()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getConnectionByRole('unknown'));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnEmptyReplication()
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('No available connection for replication');

        $replication = new MasterSlaveReplication();
        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testConnectsToOneOfSlaves()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->never())
            ->method('connect');

        $slave = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave
            ->expects($this->once())
            ->method('connect');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave);

        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testConnectsToMasterOnMissingSlaves()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');

        $replication = new MasterSlaveReplication();
        $replication->add($master);

        $replication->connect();
        $this->assertSame($master, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsTrueIfAtLeastOneConnectionIsOpen()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->never())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $slave = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave);
        $replication->connect();

        $this->assertTrue($replication->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedReturnsFalseIfAllConnectionsAreClosed()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $slave = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(false));

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave);

        $this->assertFalse($replication->isConnected());

        $replication->connect();
        $replication->disconnect();

        $this->assertFalse($replication->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testDisconnectForcesCurrentConnectionToDisconnect()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('disconnect');

        $slave = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave
            ->expects($this->once())
            ->method('disconnect');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave);

        $replication->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchConnection()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getCurrent());

        $replication->switchTo($master);
        $this->assertSame($master, $replication->getCurrent());

        $replication->switchTo($slave1);
        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testThrowsErrorWhenSwitchingToConnectionNotInPool()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid connection or connection not found.');

        $replication = new MasterSlaveReplication();

        $replication->add($this->getMockConnection('tcp://127.0.0.1:6379?role=master'));
        $replication->add($this->getMockConnection('tcp://127.0.0.1:6380?role=slave'));

        $unknown = $this->getMockConnection('tcp://127.0.0.1:6381');

        $replication->switchTo($unknown);
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchConnectionByInstance()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getCurrent());

        $replication->switchTo($master);
        $this->assertSame($master, $replication->getCurrent());

        $replication->switchTo($slave1);
        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testThrowsErrorWhenSwitchingToUnknownConnectionByInstance()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid connection or connection not found.');

        $replication = new MasterSlaveReplication();

        $replication->add($this->getMockConnection('tcp://127.0.0.1:6379?role=master'));
        $replication->add($this->getMockConnection('tcp://127.0.0.1:6380?role=slave'));

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381');

        $replication->switchTo($slave2);
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchToMaster()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertNull($replication->getCurrent());

        $replication->switchToMaster();
        $this->assertSame($master, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testThrowsErrorOnSwitchToMasterWithNoMasterDefined()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid connection or connection not found.');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($slave1);

        $replication->switchToMaster();
    }

    /**
     * @group disconnected
     *
     * @todo We should find a way to test that the slave is indeed randomly selected.
     */
    public function testCanSwitchToRandomSlave()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getCurrent());

        $replication->switchToSlave();
        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testThrowsErrorOnSwitchToRandomSlaveWithNoSlavesDefined()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid connection or connection not found.');

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');

        $replication = new MasterSlaveReplication();

        $replication->add($master);

        $replication->switchToSlave();
    }

    /**
     * @group disconnected
     */
    public function testUsesSlavesOnReadOnlyCommands()
    {
        $commands = $this->getCommandFactory();

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $cmd = $commands->createCommand('exists', array('foo'));
        $this->assertSame($slave1, $replication->getConnectionByCommand($cmd));

        $cmd = $commands->createCommand('get', array('foo'));
        $this->assertSame($slave1, $replication->getConnectionByCommand($cmd));
    }

    /**
     * @group disconnected
     */
    public function testUsesMasterOnWriteRequests()
    {
        $commands = $this->getCommandFactory();

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $cmd = $commands->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));

        $cmd = $commands->createCommand('get', array('foo'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));
    }

    /**
     * @group disconnected
     */
    public function testUsesMasterOnReadRequestsWhenNoSlavesAvailable()
    {
        $commands = $this->getCommandFactory();

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');

        $replication = new MasterSlaveReplication();

        $replication->add($master);

        $cmd = $commands->createCommand('exists', array('foo'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));

        $cmd = $commands->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));
    }

    /**
     * @group disconnected
     */
    public function testSwitchesFromSlaveToMasterOnWriteRequests()
    {
        $commands = $this->getCommandFactory();

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave1');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $cmd = $commands->createCommand('exists', array('foo'));
        $this->assertSame($slave1, $replication->getConnectionByCommand($cmd));

        $cmd = $commands->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));

        $cmd = $commands->createCommand('exists', array('foo'));
        $this->assertSame($master, $replication->getConnectionByCommand($cmd));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('foo'));
        $cmdSet = $commands->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('writeRequest')
            ->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('writeRequest')
            ->with($cmdExists);

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $replication->writeRequest($cmdExists);
        $replication->writeRequest($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testReadsCommandFromCorrectConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('foo'));
        $cmdSet = $commands->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('readResponse')
            ->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('readResponse')
            ->with($cmdExists);

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->readResponse($cmdExists);
        $replication->readResponse($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testExecutesCommandOnCorrectConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('foo'));
        $cmdSet = $commands->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists);

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdExists);
        $replication->executeCommand($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testWatchTriggersSwitchToMasterConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdWatch = $commands->createCommand('watch', array('foo'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdWatch);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->never())
            ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdWatch);
    }

    /**
     * @group disconnected
     */
    public function testMultiTriggersSwitchToMasterConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdMulti = $commands->createCommand('multi');

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdMulti);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->never())
            ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdMulti);
    }

    /**
     * @group disconnected
     */
    public function testEvalTriggersSwitchToMasterConnection()
    {
        $commands = $this->getCommandFactory();
        $cmdEval = $commands->createCommand('eval', array("return redis.call('info')"));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdEval);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->never())
            ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdEval);
    }

    /**
     * @group disconnected
     */
    public function testDiscardsUnreachableSlaveAndExecutesReadOnlyCommandOnNextSlave()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('key'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->never())
            ->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave&role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->throwException(
                new Connection\ConnectionException($slave1)
            ));

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave&alias=slave2');
        $slave2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->returnValue(1));

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->switchTo($slave1);

        $response = $replication->executeCommand($cmdExists);

        $this->assertSame(1, $response);
        $this->assertNull($replication->getConnectionByAlias('slave1'));
        $this->assertSame($slave2, $replication->getConnectionByAlias('slave2'));
    }

    /**
     * @group disconnected
     */
    public function testDiscardsUnreachableSlavesAndExecutesReadOnlyCommandOnMaster()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('key'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->returnValue(1));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->throwException(new Connection\ConnectionException($slave1)));

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave');
        $slave2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->throwException(
                new Connection\ConnectionException($slave2)
            ));

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->switchTo($slave1);

        $response = $replication->executeCommand($cmdExists);

        $this->assertSame(1, $response);
        $this->assertNull($replication->getConnectionById('127.0.0.1:6380'));
        $this->assertNull($replication->getConnectionById('127.0.0.1:6381'));
    }

    /**
     * @group disconnected
     */
    public function testSucceedOnReadOnlyCommandAndNoConnectionSetAsMaster()
    {
        $commands = $this->getCommandFactory();
        $cmdExists = $commands->createCommand('exists', array('key'));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6379?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->returnValue(1));

        $replication = new MasterSlaveReplication();

        $replication->add($slave1);

        $response = $replication->executeCommand($cmdExists);

        $this->assertSame(1, $response);
    }

    /**
     * @group disconnected
     */
    public function testFailsOnWriteCommandAndNoConnectionSetAsMaster()
    {
        $this->expectException('Predis\Replication\MissingMasterException');
        $this->expectExceptionMessage('No master server available for replication');

        $commands = $this->getCommandFactory();
        $cmdSet = $commands->createCommand('set', array('key', 'value'));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6379?role=slave');
        $slave1
            ->expects($this->never())
            ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($slave1);

        $replication->executeCommand($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testDiscardsSlaveWhenRespondsLOADINGAndExecutesReadOnlyCommandOnNextSlave()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master->expects($this->never())
               ->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'EXISTS', array('key')
            ))
            ->will($this->returnValue(
                new Response\Error('LOADING')
            ));

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6381?role=slave');
        $slave2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'EXISTS', array('key')
            ))
            ->will($this->returnValue(1));

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->switchTo($slave1);

        $response = $replication->executeCommand(
            Command\RawCommand::create('exists', 'key')
        );

        $this->assertSame(1, $response);
        $this->assertNull($replication->getConnectionById('127.0.0.1:6380'));
        $this->assertSame($slave2, $replication->getConnectionById('127.0.0.1:6381'));
    }

    /**
     * @group disconnected
     */
    public function testFailsOnUnreachableMaster()
    {
        $this->expectException('Predis\Connection\ConnectionException');

        $commands = $this->getCommandFactory();
        $cmdSet = $commands->createCommand('set', array('key', 'value'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdSet)
            ->will($this->throwException(
                new Connection\ConnectionException($master)
            ));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->never())
            ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonSupportedCommand()
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The command 'INFO' is not allowed in replication mode.");

        $cmd = $this->getCommandFactory()->createCommand('info');

        $replication = new MasterSlaveReplication();

        $replication->add($this->getMockConnection('tcp://127.0.0.1:6379?role=master'));
        $replication->add($this->getMockConnection('tcp://127.0.0.1:6380?role=slave'));

        $replication->getConnectionByCommand($cmd);
    }

    /**
     * @group disconnected
     */
    public function testCanOverrideReadOnlyFlagForCommands()
    {
        $commands = $this->getCommandFactory();
        $cmdSet = $commands->createCommand('set', array('foo', 'bar'));
        $cmdGet = $commands->createCommand('get', array('foo'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdGet);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdSet);

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->getReplicationStrategy()->setCommandReadOnly($cmdSet->getId(), true);
        $replication->getReplicationStrategy()->setCommandReadOnly($cmdGet->getId(), false);

        $replication->executeCommand($cmdSet);
        $replication->executeCommand($cmdGet);
    }

    /**
     * @group disconnected
     */
    public function testAcceptsCallableToOverrideReadOnlyFlagForCommands()
    {
        $commands = $this->getCommandFactory();
        $cmdExistsFoo = $commands->createCommand('exists', array('foo'));
        $cmdExistsBar = $commands->createCommand('exists', array('bar'));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExistsBar);

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExistsFoo);

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication
            ->getReplicationStrategy()
            ->setCommandReadOnly('exists', function ($cmd) {
                list($arg1) = $cmd->getArguments();

                return $arg1 === 'foo';
            });

        $replication->executeCommand($cmdExistsFoo);
        $replication->executeCommand($cmdExistsBar);
    }

    /**
     * @group disconnected
     */
    public function testCanSetReadOnlyFlagForEvalScripts()
    {
        $commands = $this->getCommandFactory();

        $cmdEval = $commands->createCommand('eval', array($script = "return redis.call('info');"));
        $cmdEvalSha = $commands->createCommand('evalsha', array($scriptSHA1 = sha1($script)));

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $master
            ->expects($this->never())
            ->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');
        $slave1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->with(
                $this->logicalOr($cmdEval, $cmdEvalSha)
            );

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication
            ->getReplicationStrategy()
            ->setScriptReadOnly($script);

        $replication->executeCommand($cmdEval);
        $replication->executeCommand($cmdEvalSha);
    }

    /**
     * @group disconnected
     */
    public function testDiscoveryRequiresConnectionFactory()
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Discovery requires a connection factory');

        $replication = new MasterSlaveReplication();

        $replication->add($this->getMockConnection('tcp://127.0.0.1:6379?role=master'));

        $replication->discover();
    }

    /**
     * @group disconnected
     */
    public function testDiscoversReplicationConfigurationFromMaster()
    {
        $connFactory = new Connection\Factory();
        $cmdInfo = Command\RawCommand::create('INFO', 'REPLICATION');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:master
connected_slaves:2
slave0:ip=127.0.0.1,port=6382,state=online,offset=12979,lag=0
slave1:ip=127.0.0.1,port=6383,state=online,offset=12979,lag=1
master_repl_offset:12979
repl_backlog_active:1
repl_backlog_size:1048576
repl_backlog_first_byte_offset:2
repl_backlog_histlen:12978
'
            ));

        $replication = new MasterSlaveReplication();
        $replication->setConnectionFactory($connFactory);

        $replication->add($master);

        $replication->discover();

        $this->assertCount(2, $slaves = $replication->getSlaves());
        $this->assertContainsOnlyInstancesOf('Predis\Connection\ConnectionInterface', $slaves);

        $this->assertSame('127.0.0.1:6381', (string) $replication->getMaster());
        $this->assertSame('127.0.0.1:6382', (string) $slaves[0]);
        $this->assertSame('127.0.0.1:6383', (string) $slaves[1]);
    }

    /**
     * @group disconnected
     */
    public function testDiscoversReplicationConfigurationFromSlave()
    {
        $cmdInfo = $command = Command\RawCommand::create('INFO', 'REPLICATION');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $connFactory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $connFactory
            ->expects($this->at(0))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
                'role' => 'master',
            ))
            ->will($this->returnValue($master));
        $connFactory
            ->expects($this->at(1))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6382',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave1));
        $connFactory
            ->expects($this->at(2))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6383',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave2));

        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:slave
master_host:127.0.0.1
master_port:6381
master_link_status:up
master_last_io_seconds_ago:8
master_sync_in_progress:0
slave_repl_offset:17715532
slave_priority:100
slave_read_only:1
connected_slaves:0
master_repl_offset:0
repl_backlog_active:0
repl_backlog_size:1048576
repl_backlog_first_byte_offset:0
repl_backlog_histlen:0
'
            ));

        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:master
connected_slaves:2
slave0:ip=127.0.0.1,port=6382,state=online,offset=12979,lag=0
slave1:ip=127.0.0.1,port=6383,state=online,offset=12979,lag=1
master_repl_offset:12979
repl_backlog_active:1
repl_backlog_size:1048576
repl_backlog_first_byte_offset:2
repl_backlog_histlen:12978
'
            ));

        $replication = new MasterSlaveReplication();
        $replication->setConnectionFactory($connFactory);

        $replication->add($slave1);

        $replication->discover();

        $this->assertCount(2, $slaves = $replication->getSlaves());
        $this->assertContainsOnlyInstancesOf('Predis\Connection\ConnectionInterface', $slaves);

        $this->assertSame('127.0.0.1:6381', (string) $replication->getMaster());
        $this->assertSame('127.0.0.1:6382', (string) $slaves[0]);
        $this->assertSame('127.0.0.1:6383', (string) $slaves[1]);
    }

    /**
     * @group disconnected
     */
    public function testDiscoversReplicationConfigurationFromSlaveIfMasterFails()
    {
        $cmdInfo = $command = Command\RawCommand::create('INFO', 'REPLICATION');

        $masterKO = $this->getMockConnection('tcp://127.0.0.1:7381?role=master');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $connFactory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $connFactory
            ->expects($this->at(0))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
                'role' => 'master',
            ))
            ->will($this->returnValue($master));
        $connFactory
            ->expects($this->at(1))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6382',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave1));
        $connFactory
            ->expects($this->at(2))
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6383',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave2));

        $masterKO
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->throwException(
                new Connection\ConnectionException($masterKO)
            ));

        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:slave
master_host:127.0.0.1
master_port:6381
master_link_status:up
master_last_io_seconds_ago:8
master_sync_in_progress:0
slave_repl_offset:17715532
slave_priority:100
slave_read_only:1
connected_slaves:0
master_repl_offset:0
repl_backlog_active:0
repl_backlog_size:1048576
repl_backlog_first_byte_offset:0
repl_backlog_histlen:0
'
            ));

        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:master
connected_slaves:2
slave0:ip=127.0.0.1,port=6382,state=online,offset=12979,lag=0
slave1:ip=127.0.0.1,port=6383,state=online,offset=12979,lag=1
master_repl_offset:12979
repl_backlog_active:1
repl_backlog_size:1048576
repl_backlog_first_byte_offset:2
repl_backlog_histlen:12978
'
            ));

        $replication = new MasterSlaveReplication();
        $replication->setConnectionFactory($connFactory);

        $replication->add($masterKO);
        $replication->add($slave1);

        $replication->discover();

        $this->assertCount(2, $slaves = $replication->getSlaves());
        $this->assertContainsOnlyInstancesOf('Predis\Connection\ConnectionInterface', $slaves);

        $this->assertSame('127.0.0.1:6381', (string) $replication->getMaster());
        $this->assertSame('127.0.0.1:6382', (string) $slaves[0]);
        $this->assertSame('127.0.0.1:6383', (string) $slaves[1]);
    }

    /**
     * @group disconnected
     */
    public function testAutomaticDiscoveryRequiresConnectionFactory()
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Automatic discovery requires a connection factory');

        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');

        $replication = new MasterSlaveReplication();

        $replication->add($master);

        $replication->setAutoDiscovery(true);
    }

    /**
     * @group disconnected
     */
    public function testAutomaticDiscoveryOnUnreachableServer()
    {
        $cmdInfo = $command = Command\RawCommand::create('INFO', 'REPLICATION');
        $cmdExists = $command = Command\RawCommand::create('EXISTS', 'key');

        $slaveKO = $this->getMockConnection('tcp://127.0.0.1:7382?role=slave');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');

        $connFactory = $this->getMockBuilder('Predis\Connection\Factory')->getMock();
        $connFactory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6382',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave1));

        $slaveKO
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->throwException(
                new Connection\ConnectionException($slaveKO)
            ));

        $slave1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdExists)
            ->will($this->returnValue(1));

        $master
            ->expects($this->once())
            ->method('executeCommand')
            ->with($cmdInfo)
            ->will($this->returnValue('
# Replication
role:master
connected_slaves:2
slave0:ip=127.0.0.1,port=6382,state=online,offset=12979,lag=0
master_repl_offset:12979
repl_backlog_active:1
repl_backlog_size:1048576
repl_backlog_first_byte_offset:2
repl_backlog_histlen:12978
'
            ));

        $replication = new MasterSlaveReplication();
        $replication->setConnectionFactory($connFactory);
        $replication->setAutoDiscovery(true);

        $replication->add($master);
        $replication->add($slaveKO);

        $replication->executeCommand($cmdExists);
    }

    /**
     * @group disconnected
     */
    public function testExposesReplicationStrategy()
    {
        $replication = new MasterSlaveReplication();
        $this->assertInstanceOf('Predis\Replication\ReplicationStrategy', $replication->getReplicationStrategy());

        $strategy = new ReplicationStrategy();
        $replication = new MasterSlaveReplication($strategy);

        $this->assertSame($strategy, $replication->getReplicationStrategy());
    }

    /**
     * @group disconnected
     */
    public function testCanBeSerialized()
    {
        $master = $this->getMockConnection('tcp://127.0.0.1:6379?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6380?role=slave');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $unserialized = unserialize(serialize($replication));

        $this->assertEquals($master, $unserialized->getConnectionByRole('master'));
        $this->assertEquals($slave1, $unserialized->getConnectionByRole('slave'));
    }
}
