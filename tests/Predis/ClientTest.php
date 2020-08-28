<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis;

use PredisTestCase;

/**
 *
 */
class ClientTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithoutArguments()
    {
        $client = new Client();

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullArgument()
    {
        $client = new Client(null);

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndNullArguments()
    {
        $client = new Client(null, null);

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->commands, $client->getCommandFactory());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayArgument()
    {
        $client = new Client($arg1 = array('host' => 'localhost', 'port' => 7000));

        $parameters = $client->getConnection()->getParameters();
        $this->assertSame($parameters->host, $arg1['host']);
        $this->assertSame($parameters->port, $arg1['port']);
    }

    /**
     * @group disconnected
     */
    public function testConstructorThrowsExceptionWithArrayOfParametersArgumentAndMissingOption()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Array of connection parameters requires `cluster`, `replication` or `aggregate` client option');

        $arg1 = array(
            array('host' => 'localhost', 'port' => 7000),
            array('host' => 'localhost', 'port' => 7001),
        );

        $client = new Client($arg1);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfArrayArgumentAndClusterOption()
    {
        $arg1 = array(
            array('host' => 'localhost', 'port' => 7000),
            array('host' => 'localhost', 'port' => 7001),
        );

        $client = new Client($arg1, array(
            'aggregate' => $this->getAggregateInitializer($arg1),
        ));

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithStringArgument()
    {
        $client = new Client('tcp://localhost:7000');

        $parameters = $client->getConnection()->getParameters();
        $this->assertSame($parameters->host, 'localhost');
        $this->assertSame($parameters->port, 7000);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfStringArgument()
    {
        $arg1 = array('tcp://localhost:7000', 'tcp://localhost:7001');

        $client = new Client($arg1, array(
            'aggregate' => $this->getAggregateInitializer($arg1),
        ));

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfConnectionsArgument()
    {
        $arg1 = array(
            $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock(),
            $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock(),
        );

        $client = new Client($arg1, array(
            'aggregate' => $this->getAggregateInitializer($arg1),
        ));

        $this->assertInstanceOf('Predis\Connection\AggregateConnectionInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithConnectionArgument()
    {
        $factory = new Connection\Factory();
        $connection = $factory->create('tcp://localhost:7000');

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $client->getConnection());
        $this->assertSame($connection, $client->getConnection());

        $parameters = $client->getConnection()->getParameters();
        $this->assertSame($parameters->host, 'localhost');
        $this->assertSame($parameters->port, 7000);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithClusterArgument()
    {
        $cluster = new Connection\Cluster\PredisCluster();

        $factory = new Connection\Factory();
        $factory->aggregate($cluster, array('tcp://localhost:7000', 'tcp://localhost:7001'));

        $client = new Client($cluster);

        $this->assertInstanceOf('Predis\Connection\Cluster\ClusterInterface', $client->getConnection());
        $this->assertSame($cluster, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithReplicationArgument()
    {
        $replication = new Connection\Replication\MasterSlaveReplication();

        $factory = new Connection\Factory();
        $factory->aggregate($replication, array('tcp://host1?alias=master', 'tcp://host2?alias=slave'));

        $client = new Client($replication);

        $this->assertInstanceOf('Predis\Connection\Replication\ReplicationInterface', $client->getConnection());
        $this->assertSame($replication, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCallableArgument()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($connection));

        $client = new Client($callable);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCallableConnectionInitializerThrowsExceptionOnInvalidReturnType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Callable parameters must return a valid connection');

        $wrongType = $this->getMockBuilder('stdClass')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
            ->will($this->returnValue($wrongType));

        new Client($callable);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndArrayArgument()
    {
        $connections = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();

        $arg2 = array('prefix' => 'prefix:', 'connections' => $connections);
        $client = new Client(null, $arg2);

        $this->assertInstanceOf('Predis\Command\FactoryInterface', $commands = $client->getCommandFactory());
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $commands->getProcessor());
        $this->assertSame('prefix:', $commands->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayAndOptionReplication()
    {
        $arg1 = array('tcp://127.0.0.1:6379?role=master', 'tcp://127.0.0.1:6380?role=slave');
        $arg2 = array('replication' => 'predis');
        $client = new Client($arg1, $arg2);

        $this->assertInstanceOf('Predis\Connection\Replication\ReplicationInterface', $connection = $client->getConnection());
        $this->assertSame('127.0.0.1:6379', (string) $connection->getConnectionByRole('master'));
        $this->assertSame('127.0.0.1:6380', (string) $connection->getConnectionByRole('slave'));
    }

    /**
     * @group disconnected
     */
    public function testClusterOptionHasPrecedenceOverReplicationOptionAndAggregateOption()
    {
        $arg1 = array('tcp://host1', 'tcp://host2');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $fncluster = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fncluster
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'), $arg1)
            ->will($this->returnValue($connection));

        $fnreplication = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fnreplication
            ->expects($this->never())
            ->method('__invoke');

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fnaggregate
            ->expects($this->never())
            ->method('__invoke');

        $arg2 = array(
            'cluster' => $fncluster,
            'replication' => $fnreplication,
            'aggregate' => $fnaggregate,
        );

        $client = new Client($arg1, $arg2);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testReplicationOptionHasPrecedenceOverAggregateOption()
    {
        $arg1 = array('tcp://host1', 'tcp://host2');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $fnreplication = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fnreplication
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'), $arg1)
            ->will($this->returnValue($connection));

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fnaggregate
            ->expects($this->never())
            ->method('__invoke');

        $arg2 = array(
            'replication' => $fnreplication,
            'aggregate' => $fnaggregate,
        );

        $client = new Client($arg1, $arg2);
    }

    /**
     * @group disconnected
     */
    public function testAggregateOptionDoesNotTriggerAggregationInClient()
    {
        $arg1 = array('tcp://host1', 'tcp://host2');

        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $fnaggregate = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $fnaggregate
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'), $arg1)
            ->will($this->returnValue($connection));

        $connections = $this->getMockBuilder('Predis\Connection\FactoryInterface')->getMock();
        $connections
            ->expects($this->never())
            ->method('aggregate');

        $arg2 = array('aggregate' => $fnaggregate, 'connections' => $connections);

        $client = new Client($arg1, $arg2);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithInvalidArgumentType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type for connection parameters');

        $client = new Client(new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithInvalidOptionType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid type for client options');

        $client = new Client('tcp://host1', new \stdClass());
    }

    /**
     * @group disconnected
     */
    public function testConnectAndDisconnect()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('connect');
        $connection
            ->expects($this->once())
            ->method('disconnect');

        $client = new Client($connection);
        $client->connect();
        $client->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedChecksConnectionState()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('isConnected');

        $client = new Client($connection);
        $client->isConnected();
    }

    /**
     * @group disconnected
     */
    public function testQuitIsAliasForDisconnect()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('disconnect');

        $client = new Client($connection);
        $client->quit();
    }

    /**
     * @group disconnected
     */
    public function testCreatesNewCommandUsingSpecifiedCommandFactory()
    {
        $ping = $this->getCommandFactory()->createCommand('ping', array());

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('createCommand')
            ->with('ping', array())
            ->will($this->returnValue($ping));

        $client = new Client(null, array('commands' => $commands));
        $this->assertSame($ping, $client->createCommand('ping', array()));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsParsedResponses()
    {
        $commands = $this->getCommandFactory();

        $ping = $commands->createCommand('ping', array());
        $hgetall = $commands->createCommand('hgetall', array('metavars', 'foo', 'hoge'));

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($ping)
            ->will($this->returnValue(new Response\Status('PONG')));
        $connection
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($hgetall)
            ->will($this->returnValue(array('foo', 'bar', 'hoge', 'piyo')));

        $client = new Client($connection);

        $this->assertEquals('PONG', $client->executeCommand($ping));
        $this->assertSame(array('foo' => 'bar', 'hoge' => 'piyo'), $client->executeCommand($hgetall));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandThrowsExceptionOnRedisError()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $ping = $this->getCommandFactory()->createCommand('ping', array());
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->will($this->returnValue($expectedResponse));

        $client = new Client($connection);
        $client->executeCommand($ping);
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsErrorResponseOnRedisError()
    {
        $ping = $this->getCommandFactory()->createCommand('ping', array());
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->will($this->returnValue($expectedResponse));

        $client = new Client($connection, array('exceptions' => false));
        $response = $client->executeCommand($ping);

        $this->assertSame($response, $expectedResponse);
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandExecutesInstanceOfCommand()
    {
        $ping = $this->getCommandFactory()->createCommand('ping', array());

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isInstanceOf('Predis\Command\Redis\PING'))
            ->will($this->returnValue('PONG'));

        $commands = $this->getMockBuilder('Predis\Command\FactoryInterface')->getMock();
        $commands
            ->expects($this->once())
            ->method('createCommand')
            ->with('ping', array())
            ->will($this->returnValue($ping));

        $options = array('commands' => $commands);
        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(null)
            ->setConstructorArgs(array($connection, $options))
            ->getMock();

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandThrowsExceptionOnServerError()
    {
        $this->expectException('Predis\Response\ServerException');
        $this->expectExceptionMessage('Operation against a key holding the wrong kind of value');

        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->will($this->returnValue($expectedResponse));

        $client = new Client($connection);
        $client->ping();
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandReturnsErrorResponseOnRedisError()
    {
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->will($this->returnValue($expectedResponse));

        $client = new Client($connection, array('exceptions' => false));
        $response = $client->ping();

        $this->assertSame($response, $expectedResponse);
    }

    /**
     * @group disconnected
     */
    public function testRawCommand()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand('SET', array('foo', 'bar')))
            ->will($this->returnValue(new Response\Status('OK')));
        $connection
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($this->isRedisCommand('GET', array('foo')))
            ->will($this->returnValue('bar'));
        $connection
            ->expects($this->at(2))
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->will($this->returnValue('PONG'));

        $client = new Client($connection);

        $this->assertSame('OK', $client->executeRaw(array('SET', 'foo', 'bar')));
        $this->assertSame('bar', $client->executeRaw(array('GET', 'foo')));

        $error = true;  // $error is always populated by reference.
        $this->assertSame('PONG', $client->executeRaw(array('PING'), $error));
        $this->assertFalse($error);
    }

    /**
     * @group disconnected
     */
    public function testRawCommandNeverAppliesPrefix()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($this->isRedisCommand('SET', array('foo', 'bar')))
            ->will($this->returnValue(new Response\Status('OK')));
        $connection
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($this->isRedisCommand('GET', array('foo')))
            ->will($this->returnValue('bar'));

        $client = new Client($connection, array('prefix' => 'predis:'));

        $this->assertSame('OK', $client->executeRaw(array('SET', 'foo', 'bar')));
        $this->assertSame('bar', $client->executeRaw(array('GET', 'foo')));
    }

    /**
     * @group disconnected
     */
    public function testRawCommandNeverThrowsExceptions()
    {
        $message = 'ERR Mock error response';
        $response = new Response\Error($message);

        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->with($this->isRedisCommand('PING'))
            ->will($this->returnValue($response));

        $client = new Client($connection, array('exceptions' => true));

        $this->assertSame($message, $client->executeRaw(array('PING'), $error));
        $this->assertTrue($error);
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnNonRegisteredRedisCommand()
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage("Command `INVALIDCOMMAND` is not a registered Redis command");

        $client = new Client();
        $client->invalidCommand();
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodReturnsInstanceOfSubclass()
    {
        $client = $this->getMockBuilder('Predis\Client')
            ->setMethods(null)
            ->setConstructorArgs(array(
                array('tcp://host1?alias=node01', 'tcp://host2?alias=node02'),
                array('cluster' => 'predis'),
            ))
            ->setMockClassName('SubclassedClient')
            ->getMock();

        $this->assertInstanceOf('SubclassedClient', $client->getClientBy('alias', 'node02'));
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodInvokesCallableInSecondArgumentAndReturnsItsReturnValue()
    {
        $test = $this;
        $client = new Client(array('tcp://host1?alias=node01', 'tcp://host2?alias=node02'), array('cluster' => 'predis'));

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(function ($clientNode) use ($test, $client) {
                $test->isInstanceOf('Predis\ClientInterface', $clientNode);
                $test->assertNotSame($client, $clientNode);
                $test->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $connection = $clientNode->getConnection());
                $test->assertSame('node02', $connection->getParameters()->alias);

                return true;
            }))
            ->will($this->returnValue('value'));

        $this->assertSame('value', $client->getClientBy('alias', 'node02', $callable));
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionById()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionById'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionById')
            ->with('127.0.0.1:6379')
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('id', '127.0.0.1:6379');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionSelectingConnectionByUnknownId()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot find a connection by id matching `127.0.0.1:7000`');

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionById')
            ->with('127.0.0.1:7000')
            ->will($this->returnValue(null));

        $client = new Client($aggregate);
        $client->getClientBy('id', '127.0.0.1:7000');
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByAlias()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionByAlias'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByAlias')
            ->with('myalias')
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('alias', 'myalias');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByKey()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionByKey'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByKey')
            ->with('key:1')
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('key', 'key:1');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionBySlot()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionBySlot'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionBySlot')
            ->with(5460)
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('slot', 5460);

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByRole()
    {
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionByRole'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByRole')
            ->with('master')
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('role', 'master');

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodSupportsSelectingConnectionByCommand()
    {
        $command = \Predis\Command\RawCommand::create('GET', 'key');
        $connection = $this->getMockBuilder('Predis\Connection\ConnectionInterface')->getMock();

        $aggregate = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')
            ->setMethods(array('getConnectionByCommand'))
            ->getMockForAbstractClass();
        $aggregate
            ->expects($this->once())
            ->method('getConnectionByCommand')
            ->with($command)
            ->will($this->returnValue($connection));

        $client = new Client($aggregate);
        $nodeClient = $client->getClientBy('command', $command);

        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame($client->getOptions(), $nodeClient->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionWhenSelectingConnectionByUnknownType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid selector type: `unknown`');

        $client = new Client('tcp://127.0.0.1?alias=node01');

        $client->getClientBy('unknown', 'test');
    }

    /**
     * @group disconnected
     */
    public function testGetClientByMethodThrowsExceptionWhenConnectionDoesNotSupportSelectorType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Selecting connection by id is not supported by Predis\Connection\StreamConnection');

        $client = new Client('tcp://127.0.0.1?alias=node01');

        $client->getClientBy('id', 'node01');
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithoutArgumentsReturnsPipeline()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Pipeline\Pipeline', $client->pipeline());
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithArrayReturnsPipeline()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Pipeline\Pipeline', $client->pipeline(array()));
        $this->assertInstanceOf('Predis\Pipeline\Atomic', $client->pipeline(array('atomic' => true)));
        $this->assertInstanceOf('Predis\Pipeline\FireAndForget', $client->pipeline(array('fire-and-forget' => true)));
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithCallableExecutesPipeline()
    {
        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Pipeline\Pipeline'));

        $client = new Client();
        $client->pipeline($callable);
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithoutArgumentsReturnsPubSubConsumer()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\PubSub\Consumer', $client->pubSubLoop());
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithArrayReturnsPubSubConsumerWithOptions()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $options = array('subscribe' => 'channel');

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\PubSub\Consumer', $pubsub = $client->pubSubLoop($options));

        $reflection = new \ReflectionProperty($pubsub, 'options');
        $reflection->setAccessible(true);

        $this->assertSame($options, $reflection->getValue($pubsub));
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithArrayAndCallableExecutesPubSub()
    {
        // NOTE: we use a subscribe count of 0 in the message payload to trick
        //       the context and forcing it to be closed since there are no more
        //       active subscriptions.
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(array('subscribe', 'channel', 0)));

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke');

        $client = new Client($connection);
        $this->assertNull($client->pubSubLoop(array('subscribe' => 'channel'), $callable));
    }

    /**
     * @group disconnected
     */
    public function testPubSubLoopWithCallableReturningFalseStopsPubSubConsumer()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->at(1))
            ->method('read')
            ->will($this->returnValue(array('subscribe', 'channel', 1)));
        $connection
            ->expects($this->at(2))
            ->method('writeRequest')
            ->with($this->isRedisCommand('UNSUBSCRIBE'));
        $connection
            ->expects($this->at(3))
            ->method('read')
            ->will($this->returnValue(array('unsubscribe', 'channel', 0)));

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->at(0))
            ->method('__invoke')
            ->will($this->returnValue(false));

        $client = new Client($connection);

        $this->assertNull($client->pubSubLoop(array('subscribe' => 'channel'), $callable));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithoutArgumentsReturnsMultiExec()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExec', $client->transaction());
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithArrayReturnsMultiExecTransactionWithOptions()
    {
        $options = array('cas' => true, 'retry' => 3);

        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExec', $tx = $client->transaction($options));

        // I hate this part but reflection is the easiest way in this case.
        $property = new \ReflectionProperty($tx, 'modeCAS');
        $property->setAccessible(true);
        $this->assertSame($options['cas'], $property->getValue($tx));

        $property = new \ReflectionProperty($tx, 'attempts');
        $property->setAccessible(true);
        $this->assertSame($options['retry'], $property->getValue($tx));
    }

    /**
     * @group disconnected
     */
    public function testTransactionWithArrayAndCallableExecutesMultiExec()
    {
        // We use CAS here as we don't care about the actual MULTI/EXEC context.
        $options = array('cas' => true, 'retry' => 3);

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->once())
            ->method('executeCommand')
            ->will($this->returnValue(new Response\Status('QUEUED')));

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->will($this->returnCallback(function ($tx) { $tx->ping(); }));

        $client = new Client($connection);
        $client->transaction($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testMonitorReturnsMonitorConsumer()
    {
        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Monitor\Consumer', $monitor = $client->monitor());
    }

    /**
     * @group disconnected
     */
    public function testClientResendScriptCommandUsingEvalOnNoScriptErrors()
    {
        $command = $this->getMockForAbstractClass('Predis\Command\ScriptCommand', array(), '', true, true, true, array('parseResponse'));
        $command
            ->expects($this->once())
            ->method('getScript')
            ->will($this->returnValue('return redis.call(\'exists\', KEYS[1])'));
        $command
            ->expects($this->once())
            ->method('parseResponse')
            ->with('OK')
            ->will($this->returnValue(true));

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $connection
            ->expects($this->at(0))
            ->method('executeCommand')
            ->with($command)
            ->will($this->returnValue(new Response\Error('NOSCRIPT')));
        $connection
            ->expects($this->at(1))
            ->method('executeCommand')
            ->with($this->isRedisCommand('EVAL'))
            ->will($this->returnValue('OK'));

        $client = new Client($connection);

        $this->assertTrue($client->executeCommand($command));
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorWithTraversableConnections()
    {
        $connection1 = $this->getMockConnection('tcp://127.0.0.1:6381');
        $connection2 = $this->getMockConnection('tcp://127.0.0.1:6382');
        $connection3 = $this->getMockConnection('tcp://127.0.0.1:6383');

        $aggregate = new \Predis\Connection\Cluster\PredisCluster();

        $aggregate->add($connection1);
        $aggregate->add($connection2);
        $aggregate->add($connection3);

        $client = new Client($aggregate);

        $iterator = $client->getIterator();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection1, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6381', $iterator->key());

        $iterator->next();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection2, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6382', $iterator->key());

        $iterator->next();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection3, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6383', $iterator->key());
    }

    /**
     * @group disconnected
     */
    public function testGetIteratorWithNonTraversableConnectionNoException()
    {
        $connection = $this->getMockConnection('tcp://127.0.0.1:6381');
        $client = new Client($connection);

        $iterator = $client->getIterator();

        $this->assertInstanceOf('\Predis\Client', $nodeClient = $iterator->current());
        $this->assertSame($connection, $nodeClient->getConnection());
        $this->assertSame('127.0.0.1:6381', $iterator->key());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param array $parameters Array of connection parameters.
     *
     * @return string URI string.
     */
    protected function getParametersString(array $parameters)
    {
        $defaults = $this->getDefaultParametersArray();

        $scheme = isset($parameters['scheme']) ? $parameters['scheme'] : $defaults['scheme'];
        $host = isset($parameters['host']) ? $parameters['host'] : $defaults['host'];
        $port = isset($parameters['port']) ? $parameters['port'] : $defaults['port'];

        unset($parameters['scheme'], $parameters['host'], $parameters['port']);
        $uriString = "$scheme://$host:$port/?";

        foreach ($parameters as $k => $v) {
            $uriString .= "$k=$v&";
        }

        return $uriString;
    }

    /**
     * Returns a mock callable simulating an aggregate connection initializer.
     *
     * @param mixed $parameters Expected connection parameters
     *
     * @return callable
     */
    protected function getAggregateInitializer($parameters)
    {
        $connection = $this->getMockBuilder('Predis\Connection\AggregateConnectionInterface')->getMock();

        $callable = $this->getMockBuilder('stdClass')
            ->setMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'), $parameters)
            ->will($this->returnValue($connection));

        return $callable;
    }
}
