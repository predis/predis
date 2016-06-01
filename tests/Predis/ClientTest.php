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
        $this->assertSame($options->profile->getVersion(), Profile\Factory::getDefault()->getVersion());

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
        $this->assertSame($options->profile->getVersion(), Profile\Factory::getDefault()->getVersion());

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
        $this->assertSame($options->profile->getVersion(), Profile\Factory::getDefault()->getVersion());

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
    public function testConstructorWithArrayOfArrayArgument()
    {
        $arg1 = array(
            array('host' => 'localhost', 'port' => 7000),
            array('host' => 'localhost', 'port' => 7001),
        );

        $client = new Client($arg1);

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $client->getConnection());
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
        $client = new Client($arg1 = array('tcp://localhost:7000', 'tcp://localhost:7001'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfConnectionsArgument()
    {
        $connection1 = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection2 = $this->getMock('Predis\Connection\NodeConnectionInterface');

        $client = new Client(array($connection1, $connection2));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $cluster = $client->getConnection());
        $this->assertSame($connection1, $cluster->getConnectionById(0));
        $this->assertSame($connection2, $cluster->getConnectionById(1));
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
        $cluster = new Connection\Aggregate\PredisCluster();

        $factory = new Connection\Factory();
        $factory->aggregate($cluster, array('tcp://localhost:7000', 'tcp://localhost:7001'));

        $client = new Client($cluster);

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $client->getConnection());
        $this->assertSame($cluster, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithReplicationArgument()
    {
        $replication = new Connection\Aggregate\MasterSlaveReplication();

        $factory = new Connection\Factory();
        $factory->aggregate($replication, array('tcp://host1?alias=master', 'tcp://host2?alias=slave'));

        $client = new Client($replication);

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $client->getConnection());
        $this->assertSame($replication, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithCallableArgument()
    {
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($this->isInstanceOf('Predis\Configuration\OptionsInterface'))
                 ->will($this->returnValue($connection));

        $client = new Client($callable);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The callable connection initializer returned an invalid type.
     */
    public function testConstructorWithCallableConnectionInitializerThrowsExceptionOnInvalidReturnType()
    {
        $wrongType = $this->getMock('stdClass');

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
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
        $factory = $this->getMock('Predis\Connection\FactoryInterface');

        $arg2 = array('profile' => '2.0', 'prefix' => 'prefix:', 'connections' => $factory);
        $client = new Client(null, $arg2);

        $profile = $client->getProfile();
        $this->assertSame($profile->getVersion(), Profile\Factory::get('2.0')->getVersion());
        $this->assertInstanceOf('Predis\Command\Processor\KeyPrefixProcessor', $profile->getProcessor());
        $this->assertSame('prefix:', $profile->getProcessor()->getPrefix());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayAndOptionReplication()
    {
        $arg1 = array('tcp://host1?alias=master', 'tcp://host2?alias=slave');
        $arg2 = array('replication' => true);
        $client = new Client($arg1, $arg2);

        $this->assertInstanceOf('Predis\Connection\Aggregate\ReplicationInterface', $connection = $client->getConnection());
        $this->assertSame('host1', $connection->getConnectionById('master')->getParameters()->host);
        $this->assertSame('host2', $connection->getConnectionById('slave')->getParameters()->host);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayAndOptionAggregate()
    {
        $arg1 = array('tcp://host1', 'tcp://host2');

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');

        $fnaggregate = $this->getMock('stdClass', array('__invoke'));
        $fnaggregate->expects($this->once())
                    ->method('__invoke')
                    ->with($arg1)
                    ->will($this->returnValue($connection));

        $fncluster = $this->getMock('stdClass', array('__invoke'));
        $fncluster->expects($this->never())->method('__invoke');

        $fnreplication = $this->getMock('stdClass', array('__invoke'));
        $fnreplication->expects($this->never())->method('__invoke');

        $arg2 = array(
            'aggregate' => function () use ($fnaggregate) { return $fnaggregate; },
            'cluster' => function () use ($fncluster) { return $fncluster; },
            'replication' => function () use ($fnreplication) { return $fnreplication; },
        );

        $client = new Client($arg1, $arg2);

        $this->assertSame($connection, $client->getConnection());
    }

    /**
     * @group disconnected
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The callable connection initializer returned an invalid type.
     */
    public function testConstructorWithArrayAndOptionAggregateThrowsExceptionOnInvalidReturnType()
    {
        $arg1 = array('tcp://host1', 'tcp://host2');

        $fnaggregate = $this->getMock('stdClass', array('__invoke'));
        $fnaggregate->expects($this->once())
                    ->method('__invoke')
                    ->with($arg1)
                    ->will($this->returnValue(false));

        $arg2 = array('aggregate' => function () use ($fnaggregate) { return $fnaggregate; });

        new Client($arg1, $arg2);
    }

    /**
     * @group disconnected
     */
    public function testConnectAndDisconnect()
    {
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())->method('connect');
        $connection->expects($this->once())->method('disconnect');

        $client = new Client($connection);
        $client->connect();
        $client->disconnect();
    }

    /**
     * @group disconnected
     */
    public function testIsConnectedChecksConnectionState()
    {
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())->method('isConnected');

        $client = new Client($connection);
        $client->isConnected();
    }

    /**
     * @group disconnected
     */
    public function testQuitIsAliasForDisconnect()
    {
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())->method('disconnect');

        $client = new Client($connection);
        $client->quit();
    }

    /**
     * @group disconnected
     */
    public function testCreatesNewCommandUsingSpecifiedProfile()
    {
        $ping = Profile\Factory::getDefault()->createCommand('ping', array());

        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->once())
                ->method('createCommand')
                ->with('ping', array())
                ->will($this->returnValue($ping));

        $client = new Client(null, array('profile' => $profile));
        $this->assertSame($ping, $client->createCommand('ping', array()));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandReturnsParsedResponses()
    {
        $profile = Profile\Factory::getDefault();

        $ping = $profile->createCommand('ping', array());
        $hgetall = $profile->createCommand('hgetall', array('metavars', 'foo', 'hoge'));

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->at(0))
                   ->method('executeCommand')
                   ->with($ping)
                   ->will($this->returnValue(new Response\Status('PONG')));
        $connection->expects($this->at(1))
                   ->method('executeCommand')
                   ->with($hgetall)
                   ->will($this->returnValue(array('foo', 'bar', 'hoge', 'piyo')));

        $client = new Client($connection);

        $this->assertEquals('PONG', $client->executeCommand($ping));
        $this->assertSame(array('foo' => 'bar', 'hoge' => 'piyo'), $client->executeCommand($hgetall));
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testExecuteCommandThrowsExceptionOnRedisError()
    {
        $ping = Profile\Factory::getDefault()->createCommand('ping', array());
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
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
        $ping = Profile\Factory::getDefault()->createCommand('ping', array());
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
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
        $ping = Profile\Factory::getDefault()->createCommand('ping', array());

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Command\ConnectionPing'))
                   ->will($this->returnValue('PONG'));

        $profile = $this->getMock('Predis\Profile\ProfileInterface');
        $profile->expects($this->once())
                ->method('createCommand')
                ->with('ping', array())
                ->will($this->returnValue($ping));

        $options = array('profile' => $profile);
        $client = $this->getMock('Predis\Client', null, array($connection, $options));

        $this->assertEquals('PONG', $client->ping());
    }

    /**
     * @group disconnected
     * @expectedException \Predis\Response\ServerException
     * @expectedExceptionMessage Operation against a key holding the wrong kind of value
     */
    public function testCallingRedisCommandThrowsExceptionOnServerError()
    {
        $expectedResponse = new Response\Error('ERR Operation against a key holding the wrong kind of value');

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Command\ConnectionPing'))
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

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Command\ConnectionPing'))
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
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->at(0))
                   ->method('executeCommand')
                   ->with($this->isRedisCommand('SET', array('foo', 'bar')))
                   ->will($this->returnValue(new Response\Status('OK')));
        $connection->expects($this->at(1))
                   ->method('executeCommand')
                   ->with($this->isRedisCommand('GET', array('foo')))
                   ->will($this->returnValue('bar'));
        $connection->expects($this->at(2))
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
        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->at(0))
                   ->method('executeCommand')
                   ->with($this->isRedisCommand('SET', array('foo', 'bar')))
                   ->will($this->returnValue(new Response\Status('OK')));
        $connection->expects($this->at(1))
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

        $connection = $this->getMock('Predis\Connection\ConnectionInterface');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($this->isRedisCommand('PING'))
                   ->will($this->returnValue($response));

        $client = new Client($connection, array('exceptions' => true));

        $this->assertSame($message, $client->executeRaw(array('PING'), $error));
        $this->assertTrue($error);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Command 'INVALIDCOMMAND' is not a registered Redis command.
     */
    public function testThrowsExceptionOnNonRegisteredRedisCommand()
    {
        $client = new Client();
        $client->invalidCommand();
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionFromAggregateConnectionWithAlias()
    {
        $client = new Client(array('tcp://host1?alias=node01', 'tcp://host2?alias=node02'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $cluster = $client->getConnection());
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $node01 = $client->getConnectionById('node01'));
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $node02 = $client->getConnectionById('node02'));

        $this->assertSame('host1', $node01->getParameters()->host);
        $this->assertSame('host2', $node02->getParameters()->host);
    }

    /**
     * @group disconnected
     * @expectedException \Predis\NotSupportedException
     * @expectedExceptionMessage Retrieving connections by ID is supported only by aggregate connections.
     */
    public function testGetConnectionByIdWorksOnlyWithAggregateConnections()
    {
        $client = new Client();

        $client->getConnectionById('node01');
    }

    /**
     * @group disconnected
     */
    public function testCreateClientWithConnectionFromAggregateConnection()
    {
        $client = new Client(array('tcp://host1?alias=node01', 'tcp://host2?alias=node02'), array('prefix' => 'pfx:'));

        $this->assertInstanceOf('Predis\Connection\Aggregate\ClusterInterface', $cluster = $client->getConnection());
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $node01 = $client->getConnectionById('node01'));
        $this->assertInstanceOf('Predis\Connection\NodeConnectionInterface', $node02 = $client->getConnectionById('node02'));

        $clientNode02 = $client->getClientFor('node02');

        $this->assertInstanceOf('Predis\Client', $clientNode02);
        $this->assertSame($node02, $clientNode02->getConnection());
        $this->assertSame($client->getOptions(), $clientNode02->getOptions());
    }

    /**
     * @group disconnected
     */
    public function testGetClientForReturnsInstanceOfSubclass()
    {
        $nodes = array('tcp://host1?alias=node01', 'tcp://host2?alias=node02');
        $client = $this->getMock('Predis\Client', array('dummy'), array($nodes), 'SubclassedClient');

        $this->assertInstanceOf('SubclassedClient', $client->getClientFor('node02'));
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
        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
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
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
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
        // NOTE: we use a subscribe count of 0 in the fake message to trick
        //       the context and to make it think that it can be closed
        //       since there are no more subscriptions active.

        $message = array('subscribe', 'channel', 0);
        $options = array('subscribe' => 'channel');

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())
                   ->method('read')
                   ->will($this->returnValue($message));

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke');

        $client = new Client($connection);
        $client->pubSubLoop($options, $callable);
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

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->will($this->returnValue(new Response\Status('QUEUED')));

        $txCallback = function ($tx) {
            $tx->ping();
        };

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->will($this->returnCallback($txCallback));

        $client = new Client($connection);
        $client->transaction($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testMonitorReturnsMonitorConsumer()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Monitor\Consumer', $monitor = $client->monitor());
    }

    /**
     * @group disconnected
     */
    public function testClientResendScriptCommandUsingEvalOnNoScriptErrors()
    {
        $command = $this->getMockForAbstractClass('Predis\Command\ScriptCommand', array(), '', true, true, true, array('parseResponse'));
        $command->expects($this->once())
                ->method('getScript')
                ->will($this->returnValue('return redis.call(\'exists\', KEYS[1])'));
        $command->expects($this->once())
                ->method('parseResponse')
                ->with('OK')
                ->will($this->returnValue(true));

        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $connection->expects($this->at(0))
                   ->method('executeCommand')
                   ->with($command)
                   ->will($this->returnValue(new Response\Error('NOSCRIPT')));
        $connection->expects($this->at(1))
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Command\ServerEval'))
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

        $aggregate = new \Predis\Connection\Aggregate\PredisCluster();

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
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage The underlying connection is not traversable
     */
    public function testGetIteratorWithNonTraversableConnectionThrowsException()
    {
        $connection = $this->getMock('Predis\Connection\NodeConnectionInterface');
        $client = new Client($connection);

        $client->getIterator();
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
}
