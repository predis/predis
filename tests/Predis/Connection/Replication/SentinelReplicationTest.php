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
use Predis\Replication;
use Predis\Response;
use PredisTestCase;

/**
 *
 */
class SentinelReplicationTest extends PredisTestCase
{
    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodGetSentinelConnectionThrowsExceptionOnEmptySentinelsPool()
    {
        $replication = $this->getReplicationConnection('svc', array());
        $replication->getSentinelConnection();
    }

    /**
     * @group disconnected
     */
    public function testParametersForSentinelConnectionShouldNotUseDatabaseAndPassword()
    {
        $replication = $this->getReplicationConnection('svc', array(
            'tcp://127.0.0.1:5381?role=sentinel&database=1&password=secret',
        ));

        $parameters = $replication->getSentinelConnection()->getParameters()->toArray();

        $this->assertArraySubset(array('database' => null, 'password' => null), $parameters);
    }

    /**
     * @group disconnected
     */
    public function testParametersForSentinelConnectionHaveDefaultTimeout()
    {
        $replication = $this->getReplicationConnection('svc', array(
            'tcp://127.0.0.1:5381?role=sentinel',
        ));

        $parameters = $replication->getSentinelConnection()->getParameters()->toArray();

        $this->assertArrayHasKey('timeout', $parameters);
        $this->assertSame(0.100, $parameters['timeout']);
    }

    /**
     * @group disconnected
     */
    public function testParametersForSentinelConnectionCanOverrideDefaultTimeout()
    {
        $replication = $this->getReplicationConnection('svc', array(
            'tcp://127.0.0.1:5381?role=sentinel&timeout=1',
        ));

        $parameters = $replication
            ->getSentinelConnection()
            ->getParameters()
            ->toArray();

        $this->assertArrayHasKey('timeout', $parameters);
        $this->assertSame('1', $parameters['timeout']);
    }

    /**
     * @group disconnected
     */
    public function testConnectionParametersInstanceForSentinelConnectionIsNotModified()
    {
        $originalParameters = Connection\Parameters::create(
            'tcp://127.0.0.1:5381?role=sentinel&database=1&password=secret'
        );

        $replication = $this->getReplicationConnection('svc', array($originalParameters));

        $parameters = $replication
            ->getSentinelConnection()
            ->getParameters();

        $this->assertSame($originalParameters, $parameters);
        $this->assertNotNull($parameters->password);
        $this->assertNotNull($parameters->database);
    }

    /**
     * @group disconnected
     */
    public function testMethodGetSentinelConnectionReturnsFirstAvailableSentinel()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel&alias=sentinel1');
        $sentinel2 = $this->getMockSentinelConnection('tcp://127.0.0.1:5382?role=sentinel&alias=sentinel2');
        $sentinel3 = $this->getMockSentinelConnection('tcp://127.0.0.1:5383?role=sentinel&alias=sentinel3');

        $replication = $this->getReplicationConnection('svc', array($sentinel1, $sentinel2, $sentinel3));

        $this->assertSame($sentinel1, $replication->getSentinelConnection());
    }

    /**
     * @group disconnected
     */
    public function testMethodAddAttachesMasterOrSlaveNodesToReplication()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertSame($master, $replication->getConnectionById('127.0.0.1:6381'));
        $this->assertSame($slave1, $replication->getConnectionById('127.0.0.1:6382'));
        $this->assertSame($slave2, $replication->getConnectionById('127.0.0.1:6383'));

        $this->assertSame($master, $replication->getMaster());
        $this->assertSame(array($slave1, $slave2), $replication->getSlaves());
    }

    /**
     * @group disconnected
     * @FIXME
     */
    public function testMethodRemoveDismissesMasterOrSlaveNodesFromReplication()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $this->assertTrue($replication->remove($slave1));
        $this->assertTrue($replication->remove($sentinel1));

        $this->assertSame('127.0.0.1:6381', (string) $replication->getMaster());
        $this->assertCount(1, $slaves = $replication->getSlaves());
        $this->assertSame('127.0.0.1:6383', (string) $slaves[0]);
    }

    /**
     * @group disconnected
     */
    public function testMethodGetConnectionByIdOnEmptyReplication()
    {
        $replication = $this->getReplicationConnection('svc', array());

        $this->assertNull($replication->getConnectionById('127.0.0.1:6381'));
    }

    /**
     * @group disconnected
     */
    public function testMethodGetConnectionByRole()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');

        $replication = $this->getReplicationConnection('svc', array());

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($sentinel1);

        $this->assertSame($sentinel1, $replication->getConnectionByRole('sentinel'));
        $this->assertSame($master, $replication->getConnectionByRole('master'));
        $this->assertSame($slave1, $replication->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testMethodGetConnectionByRoleOnEmptyReplicationForcesSentinelQueries()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->exactly(2))
            ->method('executeCommand')
            ->withConsecutive(
                $this->isRedisCommand('SENTINEL', array('get-master-addr-by-name', 'svc')),
                $this->isRedisCommand('SENTINEL', array('slaves', 'svc'))
            )
            ->will($this->onConsecutiveCalls(
                // SENTINEL get-master-addr-by-name svc
                array('127.0.0.1', '6381'),

                // SENTINEL slaves svc
                array(
                    array(
                        'name', '127.0.0.1:6382',
                        'ip', '127.0.0.1',
                        'port', '6382',
                        'runid', '112cdebd22924a7d962be496f3a1c4c7c9bad93f',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $this->assertSame($sentinel1, $replication->getConnectionByRole('sentinel'));
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $replication->getConnectionByRole('master'));
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $replication->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testMethodGetConnectionByRoleUnknown()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');

        $replication = $this->getReplicationConnection('svc', array());

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($sentinel1);

        $this->assertNull($replication->getConnectionByRole('unknown'));
    }

    /**
     * @group disconnected
     */
    public function testMethodUpdateSentinelsFetchesSentinelNodes()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('sentinels', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:5382',
                        'ip', '127.0.0.1',
                        'port', '5382',
                        'runid', 'a113aa7a0d4870a85bb22b4b605fd26eb93ed40e',
                        'flags', 'sentinel',
                    ),
                    array(
                        'name', '127.0.0.1:5383',
                        'ip', '127.0.0.1',
                        'port', '5383',
                        'runid', 'f53b52d281be5cdd4873700c94846af8dbe47209',
                        'flags', 'sentinel',
                    ),
                )
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));
        $replication->updateSentinels();

        // TODO: sorry for the smell...
        $reflection = new \ReflectionProperty($replication, 'sentinels');
        $reflection->setAccessible(true);

        $expected = array(
            array('host' => '127.0.0.1', 'port' => '5381'),
            array('host' => '127.0.0.1', 'port' => '5382'),
            array('host' => '127.0.0.1', 'port' => '5383'),
        );

        $this->assertSame($sentinel1, $replication->getSentinelConnection());
        $this->assertSame($expected, array_intersect_key($expected, $reflection->getValue($replication)));
    }

    /**
     * @group disconnected
     */
    public function testMethodUpdateSentinelsRemovesCurrentSentinelAndRetriesNextOneOnFailure()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel&alias=sentinel1');
        $sentinel1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('sentinels', 'svc')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($sentinel1, 'Unknown connection error [127.0.0.1:5381]')
            ));

        $sentinel2 = $this->getMockSentinelConnection('tcp://127.0.0.1:5382?role=sentinel&alias=sentinel2');
        $sentinel2
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('sentinels', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:5383',
                        'ip', '127.0.0.1',
                        'port', '5383',
                        'runid', 'f53b52d281be5cdd4873700c94846af8dbe47209',
                        'flags', 'sentinel',
                    ),
                )
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1, $sentinel2));
        $replication->updateSentinels();

        // TODO: sorry for the smell...
        $reflection = new \ReflectionProperty($replication, 'sentinels');
        $reflection->setAccessible(true);

        $expected = array(
            array('host' => '127.0.0.1', 'port' => '5382'),
            array('host' => '127.0.0.1', 'port' => '5383'),
        );

        $this->assertSame($sentinel2, $replication->getSentinelConnection());
        $this->assertSame($expected, array_intersect_key($expected, $reflection->getValue($replication)));
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodUpdateSentinelsThrowsExceptionOnNoAvailableSentinel()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('sentinels', 'svc')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($sentinel1, 'Unknown connection error [127.0.0.1:5381]')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));
        $replication->updateSentinels();
    }

    /**
     * @group disconnected
     */
    public function testMethodQuerySentinelFetchesMasterNodeSlaveNodesAndSentinelNodes()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel&alias=sentinel1');
        $sentinel1
            ->expects($this->exactly(3))
            ->method('executeCommand')
            ->withConsecutive(
                $this->isRedisCommand('SENTINEL', array('sentinels', 'svc')),
                $this->isRedisCommand('SENTINEL', array('get-master-addr-by-name', 'svc')),
                $this->isRedisCommand('SENTINEL', array('slaves', 'svc'))
            )
            ->will($this->onConsecutiveCalls(
                // SENTINEL sentinels svc
                array(
                    array(
                        'name', '127.0.0.1:5382',
                        'ip', '127.0.0.1',
                        'port', '5382',
                        'runid', 'a113aa7a0d4870a85bb22b4b605fd26eb93ed40e',
                        'flags', 'sentinel',
                    ),
                ),

                // SENTINEL get-master-addr-by-name svc
                array('127.0.0.1', '6381'),

                // SENTINEL slaves svc
                array(
                    array(
                        'name', '127.0.0.1:6382',
                        'ip', '127.0.0.1',
                        'port', '6382',
                        'runid', '112cdebd22924a7d962be496f3a1c4c7c9bad93f',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                    array(
                        'name', '127.0.0.1:6383',
                        'ip', '127.0.0.1',
                        'port', '6383',
                        'runid', '1c0bf1291797fbc5608c07a17da394147dc62817',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $sentinel2 = $this->getMockSentinelConnection('tcp://127.0.0.1:5382?role=sentinel&alias=sentinel2');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));
        $replication->querySentinel();

        // TODO: sorry for the smell...
        $reflection = new \ReflectionProperty($replication, 'sentinels');
        $reflection->setAccessible(true);

        $sentinels = array(
            array('host' => '127.0.0.1', 'port' => '5381'),
            array('host' => '127.0.0.1', 'port' => '5382'),
        );

        $this->assertSame($sentinel1, $replication->getSentinelConnection());
        $this->assertSame($sentinels, array_intersect_key($sentinels, $reflection->getValue($replication)));

        $master = $replication->getMaster();
        $slaves = $replication->getSlaves();

        $this->assertSame('127.0.0.1:6381', (string) $master);

        $this->assertCount(2, $slaves);
        $this->assertSame('127.0.0.1:6382', (string) $slaves[0]);
        $this->assertSame('127.0.0.1:6383', (string) $slaves[1]);
    }

    /**
     * @group disconnected
     */
    public function testMethodGetMasterAsksSentinelForMasterOnMasterNotSet()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->returnValue(
                array('127.0.0.1', '6381')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $this->assertSame('127.0.0.1:6381', (string) $replication->getMaster());
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodGetMasterThrowsExceptionOnNoAvailableSentinels()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($sentinel1, 'Unknown connection error [127.0.0.1:5381]')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->getMaster();
    }

    /**
     * @group disconnected
     */
    public function testMethodGetSlavesOnEmptySlavePoolAsksSentinelForSlaves()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:6382',
                        'ip', '127.0.0.1',
                        'port', '6382',
                        'runid', '112cdebd22924a7d962be496f3a1c4c7c9bad93f',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                    array(
                        'name', '127.0.0.1:6383',
                        'ip', '127.0.0.1',
                        'port', '6383',
                        'runid', '1c0bf1291797fbc5608c07a17da394147dc62817',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $slaves = $replication->getSlaves();

        $this->assertSame('127.0.0.1:6382', (string) $slaves[0]);
        $this->assertSame('127.0.0.1:6383', (string) $slaves[1]);
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodGetSlavesThrowsExceptionOnNoAvailableSentinels()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($sentinel1, 'Unknown connection error [127.0.0.1:5381]')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->getSlaves();
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodConnectThrowsExceptionOnConnectWithEmptySentinelsPool()
    {
        $replication = $this->getReplicationConnection('svc', array());
        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testMethodConnectForcesConnectionToSlave()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->never())
            ->method('connect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->once())
            ->method('connect');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testMethodConnectOnEmptySlavePoolAsksSentinelForSlavesAndForcesConnectionToSlave()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:6382',
                        'ip', '127.0.0.1',
                        'port', '6382',
                        'runid', '112cdebd22924a7d962be496f3a1c4c7c9bad93f',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->never())
            ->method('connect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->once())
            ->method('connect');

        $factory = $this->getMock('Predis\Connection\FactoryInterface');
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6382',
                'role' => 'slave',
            ))
           ->will($this->returnValue($slave1));

        $replication = $this->getReplicationConnection('svc', array($sentinel1), $factory);

        $replication->add($master);

        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testMethodConnectOnEmptySlavePoolAsksSentinelForSlavesAndForcesConnectionToMasterIfStillEmpty()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->returnValue(
                array()
            ));
        $sentinel1
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->returnValue(
                array('127.0.0.1', '6381')
            ));

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('connect');

        $factory = $this->getMock('Predis\Connection\FactoryInterface');
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6381',
                'role' => 'master',
            ))
            ->will($this->returnValue($master));

        $replication = $this->getReplicationConnection('svc', array($sentinel1), $factory);

        $replication->connect();
    }

    /**
     * @group disconnected
     */
    public function testMethodDisconnectForcesDisconnectionOnAllConnectionsInPool()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->never())
            ->method('disconnect');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('disconnect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->once())
            ->method('disconnect');

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');
        $slave2
            ->expects($this->once())
            ->method('disconnect');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testMethodIsConnectedReturnConnectionStatusOfCurrentConnection()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(true, false));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($slave1);

        $this->assertFalse($replication->isConnected());
        $replication->connect();
        $this->assertTrue($replication->isConnected());
        $replication->getConnectionById('127.0.0.1:6382')->disconnect();
        $this->assertFalse($replication->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testMethodGetConnectionByIdReturnsConnectionWhenFound()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($master, $replication->getConnectionById('127.0.0.1:6381'));
        $this->assertSame($slave1, $replication->getConnectionById('127.0.0.1:6382'));
        $this->assertNull($replication->getConnectionById('127.0.0.1:6383'));
    }

    /**
     * @group disconnected
     */
    public function testMethodSwitchToSelectsCurrentConnection()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('connect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->never())
            ->method('connect');

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2
            ->expects($this->once())
            ->method('connect');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $replication->switchTo($master);
        $this->assertSame($master, $replication->getCurrent());

        $replication->switchTo($slave2);
        $this->assertSame($slave2, $replication->getCurrent());
    }

    /**
     * @group disconnected
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid connection or connection not found.
     */
    public function testMethodSwitchToThrowsExceptionOnConnectionNotFound()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $replication->switchTo($slave2);
    }

    /**
     * @group disconnected
     */
    public function testMethodSwitchToMasterSelectsCurrentConnectionToMaster()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('connect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->never())
            ->method('connect');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $replication->switchToMaster();

        $this->assertSame($master, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testMethodSwitchToSlaveSelectsCurrentConnectionToRandomSlave()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->never())
            ->method('connect');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->once())
            ->method('connect');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $replication->switchToSlave();

        $this->assertSame($slave1, $replication->getCurrent());
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByCommandReturnsMasterForWriteCommands()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(false, true));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'master', 3129659, array(array('127.0.0.1', 6382, 3129242)),
            )));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($master, $replication->getConnectionByCommand(
            Command\RawCommand::create('set', 'key', 'value')
        ));

        $this->assertSame($master, $replication->getConnectionByCommand(
            Command\RawCommand::create('del', 'key')
        ));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByCommandReturnsSlaveForReadOnlyCommands()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(false, true));
        $slave1
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'slave', '127.0.0.1', 9000, 'connected', 3167038,
            )));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($slave1, $replication->getConnectionByCommand(
            Command\RawCommand::create('get', 'key')
        ));

        $this->assertSame($slave1, $replication->getConnectionByCommand(
            Command\RawCommand::create('exists', 'key')
        ));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByCommandSwitchesToMasterAfterWriteCommand()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->exactly(2))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(false, true));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'master', 3129659, array(array('127.0.0.1', 6382, 3129242)),
            )));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->exactly(1))
            ->method('isConnected')
            ->will($this->onConsecutiveCalls(false));
        $slave1
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'slave', '127.0.0.1', 9000, 'connected', 3167038,
            )));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($slave1, $replication->getConnectionByCommand(
            Command\RawCommand::create('exists', 'key')
        ));

        $this->assertSame($master, $replication->getConnectionByCommand(
            Command\RawCommand::create('set', 'key', 'value')
        ));

        $this->assertSame($master, $replication->getConnectionByCommand(
            Command\RawCommand::create('get', 'key')
        ));
    }

    /**
     * @group disconnected
     * @expectedException Predis\Replication\RoleException
     * @expectedExceptionMessage Expected master but got slave [127.0.0.1:6381]
     */
    public function testGetConnectionByCommandThrowsExceptionOnNodeRoleMismatch()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'slave', '127.0.0.1', 9000, 'connected', 3167038,
            )));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);

        $replication->getConnectionByCommand(Command\RawCommand::create('del', 'key'));
    }
    
    /**
     * @group disconnected
     */
    public function testGetConnectionByCommandFallbacksRoleOnOldVersions()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        
        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(new Response\Error("ERR unknown command 'ROLE'")));
        
        $replicationInfo = "# Replication
            role:master
            connected_slaves:0
            master_repl_offset:0
            repl_backlog_active:0
            repl_backlog_size:1048576
            repl_backlog_first_byte_offset:0
            repl_backlog_histlen:0
        ";
        
        $master
            ->expects($this->at(4))
            ->method('executeCommand')
            ->with($this->isRedisCommand('INFO', ['REPLICATION']))
            ->will($this->returnValue($replicationInfo));
        $replication = $this->getReplicationConnection('svc', array($sentinel1));
        
        $replication->add($master);
        
        $replication->getConnectionByCommand(Command\RawCommand::create('del', 'key'));
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionByCommandReturnsMasterForReadOnlyOperationsOnUnavailableSlaves()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:6382',
                        'ip', '127.0.0.1',
                        'port', '6382',
                        'runid', '1c0bf1291797fbc5608c07a17da394147dc62817',
                        'flags', 'slave,s_down,disconnected',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->once())
            ->method('isConnected')
            ->will($this->returnValue(false));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand('ROLE'))
            ->will($this->returnValue(array(
                'master', '0', array(),
            )));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);

        $replication->getConnectionByCommand(Command\RawCommand::create('get', 'key'));
    }

    /**
     * @group disconnected
     */
    public function testMethodExecuteCommandSendsCommandToNodeAndReturnsResponse()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $cmdGet = Command\RawCommand::create('get', 'key');
        $cmdGetResponse = 'value';

        $cmdSet = Command\RawCommand::create('set', 'key', 'value');
        $cmdSetResponse = Response\Status::get('OK');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SET', array('key', $cmdGetResponse)
            ))
           ->will($this->returnValue($cmdSetResponse));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $slave1
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('key')
            ))
            ->will($this->returnValue($cmdGetResponse));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame($cmdGetResponse, $replication->executeCommand($cmdGet));
        $this->assertSame($cmdSetResponse, $replication->executeCommand($cmdSet));
    }

    /**
     * @group disconnected
     */
    public function testMethodExecuteCommandRetriesReadOnlyCommandOnNextSlaveOnFailure()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('slaves', 'svc')
            ))
            ->will($this->returnValue(
                array(
                    array(
                        'name', '127.0.0.1:6383',
                        'ip', '127.0.0.1',
                        'port', '6383',
                        'runid', '1c0bf1291797fbc5608c07a17da394147dc62817',
                        'flags', 'slave',
                        'master-host', '127.0.0.1',
                        'master-port', '6381',
                    ),
                )
            ));

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));

        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave1
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $slave1
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('key')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($slave1, 'Unknown connection error [127.0.0.1:6382]')
            ));

        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');
        $slave2
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $slave2
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'GET', array('key')
            ))
            ->will($this->returnValue('value'));

        $factory = $this->getMock('Predis\Connection\FactoryInterface');
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6383',
                'role' => 'slave',
            ))
            ->will($this->returnValue($slave2));

        $replication = $this->getReplicationConnection('svc', array($sentinel1), $factory);

        $replication->add($master);
        $replication->add($slave1);

        $this->assertSame('value', $replication->executeCommand(
            Command\RawCommand::create('get', 'key')
        ));
    }

    /**
     * @group disconnected
     */
    public function testMethodExecuteCommandRetriesWriteCommandOnNewMasterOnFailure()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->returnValue(
                array('127.0.0.1', '6391')
            ));

        $masterOld = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $masterOld
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $masterOld
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'DEL', array('key')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($masterOld, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $masterNew = $this->getMockConnection('tcp://127.0.0.1:6391?role=master');
        $masterNew
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $masterNew
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'DEL', array('key')
            ))
            ->will($this->returnValue(1));

        $factory = $this->getMock('Predis\Connection\FactoryInterface');
        $factory
            ->expects($this->once())
            ->method('create')
            ->with(array(
                'host' => '127.0.0.1',
                'port' => '6391',
                'role' => 'master',
            ))
            ->will($this->returnValue($masterNew));

        $replication = $this->getReplicationConnection('svc', array($sentinel1), $factory);

        $replication->add($masterOld);

        $this->assertSame(1, $replication->executeCommand(
            Command\RawCommand::create('del', 'key')
        ));
    }

    /**
     * @group disconnected
     * @expectedException Predis\Response\ServerException
     * @expectedExceptionMessage ERR No such master with that name
     */
    public function testMethodExecuteCommandThrowsExceptionOnUnknownServiceName()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->returnValue(null));

        $masterOld = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $masterOld
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $masterOld
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'DEL', array('key')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($masterOld, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($masterOld);

        $replication->executeCommand(
            Command\RawCommand::create('del', 'key')
        );
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage No sentinel server available for autodiscovery.
     */
    public function testMethodExecuteCommandThrowsExceptionOnConnectionFailureAndNoAvailableSentinels()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');
        $sentinel1
            ->expects($this->any())
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'SENTINEL', array('get-master-addr-by-name', 'svc')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($sentinel1, 'Unknown connection error [127.0.0.1:5381]')
            ));

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $master
            ->expects($this->any())
            ->method('isConnected')
            ->will($this->returnValue(true));
        $master
            ->expects($this->at(3))
            ->method('executeCommand')
            ->with($this->isRedisCommand(
                'DEL', array('key')
            ))
            ->will($this->throwException(
                new Connection\ConnectionException($master, 'Unknown connection error [127.0.0.1:6381]')
            ));

        $replication = $this->getReplicationConnection('svc', array($sentinel1));

        $replication->add($master);

        $replication->executeCommand(
            Command\RawCommand::create('del', 'key')
        );
    }

    /**
     * @group disconnected
     */
    public function testMethodGetReplicationStrategyReturnsInstance()
    {
        $strategy = new Replication\ReplicationStrategy();
        $factory = new Connection\Factory();

        $replication = new SentinelReplication(
            'svc', array('tcp://127.0.0.1:5381?role=sentinel'), $factory, $strategy
        );

        $this->assertSame($strategy, $replication->getReplicationStrategy());
    }

    /**
     * @group disconnected
     */
    public function testMethodSerializeCanSerializeWholeObject()
    {
        $sentinel1 = $this->getMockSentinelConnection('tcp://127.0.0.1:5381?role=sentinel');

        $master = $this->getMockConnection('tcp://127.0.0.1:6381?role=master');
        $slave1 = $this->getMockConnection('tcp://127.0.0.1:6382?role=slave');
        $slave2 = $this->getMockConnection('tcp://127.0.0.1:6383?role=slave');

        $strategy = new Replication\ReplicationStrategy();
        $factory = new Connection\Factory();

        $replication = new SentinelReplication('svc', array($sentinel1), $factory, $strategy);

        $replication->add($master);
        $replication->add($slave1);
        $replication->add($slave2);

        $unserialized = unserialize(serialize($replication));

        $this->assertEquals($master, $unserialized->getConnectionById('127.0.0.1:6381'));
        $this->assertEquals($slave1, $unserialized->getConnectionById('127.0.0.1:6382'));
        $this->assertEquals($master, $unserialized->getConnectionById('127.0.0.1:6383'));
        $this->assertEquals($strategy, $unserialized->getReplicationStrategy());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Creates a new instance of replication connection.
     *
     * @param string                          $service   Name of the service
     * @param array                           $sentinels Array of sentinels
     * @param ConnectionFactoryInterface|null $factory   Optional connection factory instance.
     *
     * @return SentinelReplication
     */
    protected function getReplicationConnection($service, $sentinels, Connection\FactoryInterface $factory = null)
    {
        $factory = $factory ?: new Connection\Factory();

        $replication = new SentinelReplication($service, $sentinels, $factory);
        $replication->setRetryWait(0);

        return $replication;
    }

    /**
     * Returns a base mocked connection from Predis\Connection\NodeConnectionInterface.
     *
     * @param mixed $parameters Optional parameters.
     *
     * @return mixed
     */
    protected function getMockSentinelConnection($parameters = null)
    {
        $connection = $this->getMockConnection($parameters);

        return $connection;
    }
}
