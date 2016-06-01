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

use Predis\Command;
use Predis\Connection;
use Predis\Profile;
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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame($master, $replication->getConnectionById('master'));
        $this->assertSame($slave1, $replication->getConnectionById('slave1'));
        $this->assertSame($slave2, $replication->getConnectionById('slave2'));

        $this->assertSame($master, $replication->getMaster());
        $this->assertSame(array($slave1, $slave2), $replication->getSlaves());
    }

    /**
     * @group disconnected
     */
    public function testRemovingConnectionsFromReplication()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');

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
    public function testAddingConnectionsToReplicationWithoutAliasesResultsInCustomId()
    {
        $slave1 = $this->getMockConnection('tcp://host1');
        $slave2 = $this->getMockConnection('tcp://host2:6380');

        $replication = new MasterSlaveReplication();
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame($slave1, $replication->getConnectionById('slave-host1:6379'));
        $this->assertSame($slave2, $replication->getConnectionById('slave-host2:6380'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage No available connection for replication
     */
    public function testThrowsExceptionOnEmptyReplication()
    {
        $replication = new MasterSlaveReplication();
        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testConnectsToOneOfSlaves()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->never())->method('connect');

        $slave = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave->expects($this->once())->method('connect');

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
        $master = $this->getMockConnection('tcp://host1?alias=master');

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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->never())->method('isConnected')->will($this->returnValue(false));

        $slave = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave->expects($this->once())->method('isConnected')->will($this->returnValue(true));

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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->any())->method('isConnected')->will($this->returnValue(false));

        $slave = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave->expects($this->any())->method('isConnected')->will($this->returnValue(false));

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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('disconnect');

        $slave = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave->expects($this->once())->method('disconnect');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave);

        $replication->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchConnectionByAlias()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getCurrent());

        $replication->switchTo('master');
        $this->assertSame($master, $replication->getCurrent());
        $replication->switchTo('slave1');
        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid connection or connection not found.
     */
    public function testThrowsErrorWhenSwitchingToUnknownConnectionByAlias()
    {
        $replication = new MasterSlaveReplication();
        $replication->add($this->getMockConnection('tcp://host1?alias=master'));
        $replication->add($this->getMockConnection('tcp://host2?alias=slave1'));

        $replication->switchTo('unknown');
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchConnectionByInstance()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid connection or connection not found.
     */
    public function testThrowsErrorWhenSwitchingToUnknownConnectionByInstance()
    {
        $replication = new MasterSlaveReplication();
        $replication->add($this->getMockConnection('tcp://host1?alias=master'));
        $replication->add($this->getMockConnection('tcp://host2?alias=slave1'));

        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');

        $replication->switchTo($slave2);
    }

    /**
     * @group disconnected
     */
    public function testCanSwitchToMaster()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');

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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid connection or connection not found.
     */
    public function testThrowsErrorOnSwitchToMasterWithNoMasterDefined()
    {
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $this->assertNull($replication->getCurrent());

        $replication->switchToSlave();
        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid connection or connection not found.
     */
    public function testThrowsErrorOnSwitchToRandomSlaveWithNoSlavesDefined()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');

        $replication = new MasterSlaveReplication();
        $replication->add($master);

        $replication->switchToSlave();
    }

    /**
     * @group disconnected
     */
    public function testUsesSlavesOnReadOnlyCommands()
    {
        $profile = Profile\Factory::getDefault();

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $cmd = $profile->createCommand('exists', array('foo'));
        $this->assertSame($slave1, $replication->getConnection($cmd));

        $cmd = $profile->createCommand('get', array('foo'));
        $this->assertSame($slave1, $replication->getConnection($cmd));
    }

    /**
     * @group disconnected
     */
    public function testUsesMasterOnWriteRequests()
    {
        $profile = Profile\Factory::getDefault();

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $cmd = $profile->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnection($cmd));

        $cmd = $profile->createCommand('get', array('foo'));
        $this->assertSame($master, $replication->getConnection($cmd));
    }

    /**
     * @group disconnected
     */
    public function testUsesMasterOnReadRequestsWhenNoSlavesAvailable()
    {
        $profile = Profile\Factory::getDefault();

        $master = $this->getMockConnection('tcp://host1?alias=master');

        $replication = new MasterSlaveReplication();
        $replication->add($master);

        $cmd = $profile->createCommand('exists', array('foo'));
        $this->assertSame($master, $replication->getConnection($cmd));

        $cmd = $profile->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnection($cmd));
    }

    /**
     * @group disconnected
     */
    public function testSwitchesFromSlaveToMasterOnWriteRequestss()
    {
        $profile = Profile\Factory::getDefault();

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $cmd = $profile->createCommand('exists', array('foo'));
        $this->assertSame($slave1, $replication->getConnection($cmd));

        $cmd = $profile->createCommand('set', array('foo', 'bar'));
        $this->assertSame($master, $replication->getConnection($cmd));

        $cmd = $profile->createCommand('exists', array('foo'));
        $this->assertSame($master, $replication->getConnection($cmd));
    }

    /**
     * @group disconnected
     */
    public function testWritesCommandToCorrectConnection()
    {
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('foo'));
        $cmdSet = $profile->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('writeRequest')->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('writeRequest')->with($cmdExists);

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
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('foo'));
        $cmdSet = $profile->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('readResponse')->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('readResponse')->with($cmdExists);

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
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('foo'));
        $cmdSet = $profile->createCommand('set', array('foo', 'bar'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdSet);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('executeCommand')->with($cmdExists);

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
        $profile = Profile\Factory::getDefault();
        $cmdWatch = $profile->createCommand('watch', array('foo'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdWatch);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->never())->method('executeCommand');

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
        $profile = Profile\Factory::getDefault();
        $cmdMulti = $profile->createCommand('multi');

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdMulti);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->never())->method('executeCommand');

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
        $profile = Profile\Factory::get('dev');
        $cmdEval = $profile->createCommand('eval', array("return redis.call('info')"));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdEval);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->never())->method('executeCommand');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdEval);
    }

    /**
     * @group disconnected
     */
    public function testSortTriggersSwitchToMasterConnectionOnStoreModifier()
    {
        $profile = Profile\Factory::get('dev');
        $cmdSortNormal = $profile->createCommand('sort', array('key'));
        $cmdSortStore = $profile->createCommand('sort', array('key', array('store' => 'key:store')));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdSortStore);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('executeCommand')->with($cmdSortNormal);

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdSortNormal);
        $replication->executeCommand($cmdSortStore);
    }

    /**
     * @group disconnected
     */
    public function testDiscardsUnreachableSlaveAndExecutesReadOnlyCommandOnNextSlave()
    {
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('key'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->never())->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())
               ->method('executeCommand')
               ->with($cmdExists)
               ->will($this->throwException(new Connection\ConnectionException($slave1)));

        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');
        $slave2->expects($this->once())
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
        $this->assertNull($replication->getConnectionById('slave1'));
        $this->assertSame($slave2, $replication->getConnectionById('slave2'));
    }

    /**
     * @group disconnected
     */
    public function testDiscardsUnreachableSlavesAndExecutesReadOnlyCommandOnMaster()
    {
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('key'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())
               ->method('executeCommand')
               ->with($cmdExists)
               ->will($this->returnValue(1));

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())
               ->method('executeCommand')
               ->with($cmdExists)
               ->will($this->throwException(new Connection\ConnectionException($slave1)));

        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');
        $slave2->expects($this->once())
               ->method('executeCommand')
               ->with($cmdExists)
               ->will($this->throwException(new Connection\ConnectionException($slave2)));

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->switchTo($slave1);

        $response = $replication->executeCommand($cmdExists);

        $this->assertSame(1, $response);
        $this->assertNull($replication->getConnectionById('slave1'));
        $this->assertNull($replication->getConnectionById('slave2'));
    }

    /**
     * @group disconnected
     */
    public function testSucceedOnReadOnlyCommandAndNoConnectionSetAsMaster()
    {
        $profile = Profile\Factory::getDefault();
        $cmdExists = $profile->createCommand('exists', array('key'));

        $slave1 = $this->getMockConnection('tcp://host1?alias=slave1');
        $slave1->expects($this->once())
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
     * @expectedException \Predis\Replication\MissingMasterException
     * @expectedMessage No master server available for replication
     */
    public function testFailsOnWriteCommandAndNoConnectionSetAsMaster()
    {
        $profile = Profile\Factory::getDefault();
        $cmdSet = $profile->createCommand('set', array('key', 'value'));

        $slave1 = $this->getMockConnection('tcp://host1?alias=slave1');
        $slave1->expects($this->never())->method('executeCommand');

        $replication = new MasterSlaveReplication();
        $replication->add($slave1);

        $replication->executeCommand($cmdSet);
    }

    /**
     * @group disconnected
     */
    public function testDiscardsSlaveWhenRespondsLOADINGAndExecutesReadOnlyCommandOnNextSlave()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->never())
               ->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())
               ->method('executeCommand')
               ->with($this->isRedisCommand(
                   'EXISTS', array('key')
               ))
               ->will($this->returnValue(
                   new Response\Error('LOADING')
               ));

        $slave2 = $this->getMockConnection('tcp://host3?alias=slave2');
        $slave2->expects($this->once())
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
        $this->assertNull($replication->getConnectionById('slave1'));
        $this->assertSame($slave2, $replication->getConnectionById('slave2'));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Connection\ConnectionException
     */
    public function testFailsOnUnreachableMaster()
    {
        $profile = Profile\Factory::getDefault();
        $cmdSet = $profile->createCommand('set', array('key', 'value'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())
               ->method('executeCommand')
               ->with($cmdSet)
               ->will($this->throwException(new Connection\ConnectionException($master)));

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->never())
               ->method('executeCommand');

        $replication = new MasterSlaveReplication();

        $replication->add($master);
        $replication->add($slave1);

        $replication->executeCommand($cmdSet);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage The command 'INFO' is not allowed in replication mode.
     */
    public function testThrowsExceptionOnNonSupportedCommand()
    {
        $cmd = Profile\Factory::getDefault()->createCommand('info');

        $replication = new MasterSlaveReplication();
        $replication->add($this->getMockConnection('tcp://host1?alias=master'));
        $replication->add($this->getMockConnection('tcp://host2?alias=slave1'));

        $replication->getConnection($cmd);
    }

    /**
     * @group disconnected
     */
    public function testCanOverrideReadOnlyFlagForCommands()
    {
        $profile = Profile\Factory::getDefault();
        $cmdSet = $profile->createCommand('set', array('foo', 'bar'));
        $cmdGet = $profile->createCommand('get', array('foo'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdGet);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('executeCommand')->with($cmdSet);

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
        $profile = Profile\Factory::getDefault();
        $cmdExistsFoo = $profile->createCommand('exists', array('foo'));
        $cmdExistsBar = $profile->createCommand('exists', array('bar'));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->once())->method('executeCommand')->with($cmdExistsBar);

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->once())->method('executeCommand')->with($cmdExistsFoo);

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $replication->getReplicationStrategy()->setCommandReadOnly('exists', function ($cmd) {
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
        $profile = Profile\Factory::get('dev');

        $cmdEval = $profile->createCommand('eval', array($script = "return redis.call('info');"));
        $cmdEvalSha = $profile->createCommand('evalsha', array($scriptSHA1 = sha1($script)));

        $master = $this->getMockConnection('tcp://host1?alias=master');
        $master->expects($this->never())->method('executeCommand');

        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');
        $slave1->expects($this->exactly(2))
               ->method('executeCommand')
               ->with($this->logicalOr($cmdEval, $cmdEvalSha));

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $replication->getReplicationStrategy()->setScriptReadOnly($script);

        $replication->executeCommand($cmdEval);
        $replication->executeCommand($cmdEvalSha);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedMessage Discovery requires a connection factory
     */
    public function testDiscoveryRequiresConnectionFactory()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');

        $replication = new MasterSlaveReplication();
        $replication->add($master);

        $replication->discover();
    }

    /**
     * @group disconnected
     */
    public function testDiscoversReplicationConfigurationFromMaster()
    {
        $connFactory = new Connection\Factory();
        $cmdInfo = Command\RawCommand::create('INFO', 'REPLICATION');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?alias=master');
        $master->expects($this->once())
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
'));

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

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?alias=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?alias=slave1');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?alias=slave2');

        $connFactory = $this->getMock('Predis\Connection\Factory');
        $connFactory->expects($this->at(0))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6381', 'alias' => 'master'))
                    ->will($this->returnValue($master));
        $connFactory->expects($this->at(1))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6382'))
                    ->will($this->returnValue($slave1));
        $connFactory->expects($this->at(2))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6383'))
                    ->will($this->returnValue($slave2));

        $slave1->expects($this->once())
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
'));

        $master->expects($this->once())
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
'));

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

        $masterKO = $this->getMockConnection('tcp://127.0.0.1:7381?alias=master');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?alias=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?alias=slave1');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?alias=slave2');

        $connFactory = $this->getMock('Predis\Connection\Factory');
        $connFactory->expects($this->at(0))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6381', 'alias' => 'master'))
                    ->will($this->returnValue($master));
        $connFactory->expects($this->at(1))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6382'))
                    ->will($this->returnValue($slave1));
        $connFactory->expects($this->at(2))
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6383'))
                    ->will($this->returnValue($slave2));

        $masterKO->expects($this->once())
               ->method('executeCommand')
               ->with($cmdInfo)
               ->will($this->throwException(new Connection\ConnectionException($masterKO)));

        $slave1->expects($this->once())
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
'));

        $master->expects($this->once())
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
'));

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
     * @expectedException \Predis\ClientException
     * @expectedMessage Automatic discovery requires a connection factory
     */
    public function testAutomaticDiscoveryRequiresConnectionFactory()
    {
        $master = $this->getMockConnection('tcp://host1?alias=master');

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

        $slaveKO = $this->getMockConnection('tcp://127.0.0.1:7382?alias=slaveKO');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?alias=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?alias=slave1');

        $connFactory = $this->getMock('Predis\Connection\Factory');
        $connFactory->expects($this->once())
                    ->method('create')
                    ->with(array('host' => '127.0.0.1', 'port' => '6382'))
                    ->will($this->returnValue($slave1));

        $slaveKO->expects($this->once())
                ->method('executeCommand')
                ->with($cmdExists)
                ->will($this->throwException(new Connection\ConnectionException($slaveKO)));

        $slave1->expects($this->once())
               ->method('executeCommand')
               ->with($cmdExists)
               ->will($this->returnValue(1));

        $master->expects($this->once())
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
'));

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
        $master = $this->getMockConnection('tcp://host1?alias=master');
        $slave1 = $this->getMockConnection('tcp://host2?alias=slave1');

        $replication = new MasterSlaveReplication();
        $replication->add($master);
        $replication->add($slave1);

        $unserialized = unserialize(serialize($replication));

        $this->assertEquals($master, $unserialized->getConnectionById('master'));
        $this->assertEquals($slave1, $unserialized->getConnectionById('slave1'));
    }
}
