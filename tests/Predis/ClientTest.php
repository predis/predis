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

use \PHPUnit_Framework_TestCase as StandardTestCase;

use Predis\Profiles\ServerProfile;
use Predis\Network\PredisCluster;
use Predis\Network\MasterSlaveReplication;

/**
 *
 */
class ClientTest extends StandardTestCase
{
    /**
     * @group disconnected
     */
    public function testConstructorWithoutArguments()
    {
        $client = new Client();

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->profile->getVersion(), ServerProfile::getDefault()->getVersion());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullArgument()
    {
        $client = new Client(null);

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->profile->getVersion(), ServerProfile::getDefault()->getVersion());

        $this->assertFalse($client->isConnected());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndNullArguments()
    {
        $client = new Client(null, null);

        $connection = $client->getConnection();
        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $connection);

        $parameters = $connection->getParameters();
        $this->assertSame($parameters->host, '127.0.0.1');
        $this->assertSame($parameters->port, 6379);

        $options = $client->getOptions();
        $this->assertSame($options->profile->getVersion(), ServerProfile::getDefault()->getVersion());

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

        $this->assertInstanceOf('Predis\Network\IConnectionCluster', $client->getConnection());
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

        $this->assertInstanceOf('Predis\Network\IConnectionCluster', $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayOfConnectionsArgument()
    {
        $connection1 = $this->getMock('Predis\Network\IConnectionSingle');
        $connection2 = $this->getMock('Predis\Network\IConnectionSingle');

        $client = new Client(array($connection1, $connection2));

        $this->assertInstanceOf('Predis\Network\IConnectionCluster', $cluster = $client->getConnection());
        $this->assertSame($connection1, $cluster->getConnectionById(0));
        $this->assertSame($connection2, $cluster->getConnectionById(1));
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithConnectionArgument()
    {
        $factory = new ConnectionFactory();
        $connection = $factory->create('tcp://localhost:7000');

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $client->getConnection());
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
        $cluster = new PredisCluster();

        $factory = new ConnectionFactory();
        $factory->createCluster($cluster, array('tcp://localhost:7000', 'tcp://localhost:7001'));

        $client = new Client($cluster);

        $this->assertInstanceOf('Predis\Network\IConnectionCluster', $client->getConnection());
        $this->assertSame($cluster, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithReplicationArgument()
    {
        $replication = new MasterSlaveReplication();

        $factory = new ConnectionFactory();
        $factory->createReplication($replication, array('tcp://host1?alias=master', 'tcp://host2?alias=slave'));

        $client = new Client($replication);

        $this->assertInstanceOf('Predis\Network\IConnectionReplication', $client->getConnection());
        $this->assertSame($replication, $client->getConnection());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndStringArgument()
    {
        $client = new Client(null, '2.0');

        $this->assertSame($client->getProfile()->getVersion(), ServerProfile::get('2.0')->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndProfileArgument()
    {
        $client = new Client(null, $arg2 = ServerProfile::get('2.0'));

        $this->assertSame($client->getProfile(), $arg2);
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithNullAndArrayArgument()
    {
        $factory = $this->getMock('Predis\IConnectionFactory');

        $arg2 = array('profile' => '2.0', 'prefix' => 'prefix:', 'connections' => $factory);
        $client = new Client(null, $arg2);

        $profile = $client->getProfile();
        $this->assertSame($profile->getVersion(), ServerProfile::get('2.0')->getVersion());
        $this->assertInstanceOf('Predis\Commands\Processors\KeyPrefixProcessor', $profile->getProcessor());
        $this->assertSame('prefix:', $profile->getProcessor()->getPrefix());

        $this->assertSame($factory, $client->getConnectionFactory());
    }

    /**
     * @group disconnected
     */
    public function testConstructorWithArrayAndOptionReplicationArgument()
    {
        $arg1 = array('tcp://host1?alias=master', 'tcp://host2?alias=slave');
        $arg2 = array('replication' => true);
        $client = new Client($arg1, $arg2);

        $this->assertInstanceOf('Predis\Network\IConnectionReplication', $connection = $client->getConnection());
        $this->assertSame('host1', $connection->getConnectionById('master')->getParameters()->host);
        $this->assertSame('host2', $connection->getConnectionById('slave')->getParameters()->host);
    }

    /**
     * @group disconnected
     */
    public function testConnectAndDisconnect()
    {
        $connection = $this->getMock('Predis\Network\IConnection');
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
        $connection = $this->getMock('Predis\Network\IConnection');
        $connection->expects($this->once())->method('isConnected');

        $client = new Client($connection);
        $client->isConnected();
    }

    /**
     * @group disconnected
     */
    public function testQuitIsAliasForDisconnect()
    {
        $connection = $this->getMock('Predis\Network\IConnection');
        $connection->expects($this->once())->method('disconnect');

        $client = new Client($connection);
        $client->quit();
    }

    /**
     * @group disconnected
     */
    public function testCreatesNewCommandUsingSpecifiedProfile()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping', array());

        $profile = $this->getMock('Predis\Profiles\IServerProfile');
        $profile->expects($this->once())
                ->method('createCommand')
                ->with('ping', array())
                ->will($this->returnValue($ping));

        $client = new Client(null, $profile);
        $this->assertSame($ping, $client->createCommand('ping', array()));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommand()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping', array());

        $connection= $this->getMock('Predis\Network\IConnection');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($ping)
                   ->will($this->returnValue(true));

        $client = new Client($connection);

        $this->assertTrue($client->executeCommand($ping));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandOnEachNode()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping', array());

        $connection1 = $this->getMock('Predis\Network\IConnectionSingle');
        $connection1->expects($this->once())
                    ->method('executeCommand')
                    ->with($ping)
                    ->will($this->returnValue(true));

        $connection2 = $this->getMock('Predis\Network\IConnectionSingle');
        $connection2->expects($this->once())
                    ->method('executeCommand')
                    ->with($ping)
                    ->will($this->returnValue(false));

        $client = new Client(array($connection1, $connection2));

        $this->assertSame(array(true, false), $client->executeCommandOnShards($ping));
    }

    /**
     * @group disconnected
     */
    public function testExecuteCommandOnEachNodeButConnectionSingle()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping', array());

        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $connection->expects($this->once())
                    ->method('executeCommand')
                    ->with($ping)
                    ->will($this->returnValue(true));

        $client = new Client($connection);

        $this->assertSame(array(true), $client->executeCommandOnShards($ping));
    }

    /**
     * @group disconnected
     */
    public function testCallingRedisCommandExecutesInstanceOfCommand()
    {
        $ping = ServerProfile::getDefault()->createCommand('ping', array());

        $connection = $this->getMock('Predis\Network\IConnection');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->with($this->isInstanceOf('Predis\Commands\ConnectionPing'))
                   ->will($this->returnValue(true));

        $profile = $this->getMock('Predis\Profiles\IServerProfile');
        $profile->expects($this->once())
                ->method('createCommand')
                ->with('ping', array())
                ->will($this->returnValue($ping));

        $client = $this->getMock('Predis\Client', array('createCommand'), array($connection, $profile));

        $this->assertTrue($client->ping());
    }

    /**
     * @group disconnected
     * @expectedException Predis\ClientException
     * @expectedExceptionMessage 'invalidcommand' is not a registered Redis command
     */
    public function testThrowsExceptionOnNonRegisteredRedisCommand()
    {
        $client = new Client();
        $client->invalidCommand();
    }

    /**
     * @group disconnected
     */
    public function testGetConnectionFromClusterWithAlias()
    {
        $client = new Client(array('tcp://host1?alias=node01', 'tcp://host2?alias=node02'));

        $this->assertInstanceOf('Predis\Network\IConnectionCluster', $cluster = $client->getConnection());
        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $node01 = $client->getConnection('node01'));
        $this->assertInstanceOf('Predis\Network\IConnectionSingle', $node02 = $client->getConnection('node02'));

        $this->assertSame('host1', $node01->getParameters()->host);
        $this->assertSame('host2', $node02->getParameters()->host);
    }

    /**
     * @group disconnected
     * @expectedException Predis\NotSupportedException
     * @expectedExceptionMessage Retrieving connections by alias is supported only with aggregated connections (cluster or replication)
     */
    public function testGetConnectionWithAliasWorksOnlyWithCluster()
    {
        $client = new Client();

        $client->getConnection('node01');
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithoutArgumentsReturnsPipelineContext()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Pipeline\PipelineContext', $pipeline = $client->pipeline());
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithArrayReturnsPipelineContextWithOptions()
    {
        $client = new Client();

        $executor = $this->getMock('Predis\Pipeline\IPipelineExecutor');
        $options = array('executor' => $executor);

        $this->assertInstanceOf('Predis\Pipeline\PipelineContext', $pipeline = $client->pipeline($options));

        $reflection = new \ReflectionProperty($pipeline, 'executor');
        $reflection->setAccessible(true);

        $this->assertSame($executor, $reflection->getValue($pipeline));
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithCallableExecutesPipeline()
    {
        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($this->isInstanceOf('Predis\Pipeline\PipelineContext'));

        $client = new Client();
        $client->pipeline($callable);
    }

    /**
     * @group disconnected
     */
    public function testPipelineWithArrayAndCallableExecutesPipelineWithOptions()
    {
        $executor = $this->getMock('Predis\Pipeline\IPipelineExecutor');
        $options = array('executor' => $executor);

        $test = $this;
        $mockCallback = function($pipeline) use($executor, $test) {
            $reflection = new \ReflectionProperty($pipeline, 'executor');
            $reflection->setAccessible(true);

            $test->assertSame($executor, $reflection->getValue($pipeline));
        };

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($this->isInstanceOf('Predis\Pipeline\PipelineContext'))
                 ->will($this->returnCallback($mockCallback));

        $client = new Client();
        $client->pipeline($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testPubSubWithoutArgumentsReturnsPubSubContext()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\PubSub\PubSubContext', $pubsub = $client->pubSub());
    }

    /**
     * @group disconnected
     */
    public function testPubSubWithArrayReturnsPubSubContextWithOptions()
    {
        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $options = array('subscribe' => 'channel');

        $client = new Client($connection);

        $this->assertInstanceOf('Predis\PubSub\PubSubContext', $pubsub = $client->pubSub($options));

        $reflection = new \ReflectionProperty($pubsub, 'options');
        $reflection->setAccessible(true);

        $this->assertSame($options, $reflection->getValue($pubsub));
    }

    /**
     * @group disconnected
     */
    public function testPubSubWithArrayAndCallableExecutesPubSub()
    {
        // NOTE: we use a subscribe count of 0 in the fake message to trick
        //       the context and to make it think that it can be closed
        //       since there are no more subscriptions active.

        $message = array('subscribe', 'channel', 0);
        $options = array('subscribe' => 'channel');

        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $connection->expects($this->once())
                   ->method('read')
                   ->will($this->returnValue($message));

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke');

        $client = new Client($connection);
        $client->pubSub($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testMultiExecWithoutArgumentsReturnsMultiExecContext()
    {
        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExecContext', $pubsub = $client->multiExec());
    }

    /**
     * @group disconnected
     */
    public function testMultiExecWithArrayReturnsMultiExecContextWithOptions()
    {
        $options = array('cas' => true, 'retry' => 3);

        $client = new Client();

        $this->assertInstanceOf('Predis\Transaction\MultiExecContext', $tx = $client->multiExec($options));

        $reflection = new \ReflectionProperty($tx, 'options');
        $reflection->setAccessible(true);

        $this->assertSame($options, $reflection->getValue($tx));
    }

    /**
     * @group disconnected
     */
    public function testMultiExecWithArrayAndCallableExecutesMultiExec()
    {
        // NOTE: we use CAS since testing the actual MULTI/EXEC context
        //       here is not the point.
        $options = array('cas' => true, 'retry' => 3);

        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $connection->expects($this->once())
                   ->method('executeCommand')
                   ->will($this->returnValue(new ResponseQueued()));

        $txCallback = function($tx) { $tx->ping(); };

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->will($this->returnCallback($txCallback));

        $client = new Client($connection);
        $client->multiExec($options, $callable);
    }

    /**
     * @group disconnected
     */
    public function testMonitorReturnsMonitorContext()
    {
        $connection = $this->getMock('Predis\Network\IConnectionSingle');
        $client = new Client($connection);

        $this->assertInstanceOf('Predis\MonitorContext', $monitor = $client->monitor());
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns a named array with the default connection parameters and their values.
     *
     * @return Array Default connection parameters.
     */
    protected function getDefaultParametersArray()
    {
        return array(
            'scheme' => 'tcp',
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
            'database' => REDIS_SERVER_DBNUM,
        );
    }

    /**
     * Returns a named array with the default client options and their values.
     *
     * @return Array Default connection parameters.
     */
    protected function getDefaultOptionsArray()
    {
        return array(
            'profile' => REDIS_SERVER_VERSION,
        );
    }

    /**
     * Returns a named array with the default connection parameters merged with
     * the specified additional parameters.
     *
     * @param Array $additional Additional connection parameters.
     * @return Array Connection parameters.
     */
    protected function getParametersArray(Array $additional)
    {
        return array_merge($this->getDefaultParametersArray(), $additional);
    }

    /**
     * Returns an URI string representation of the specified connection parameters.
     *
     * @param Array $parameters Array of connection parameters.
     * @return String URI string.
     */
    protected function getParametersString(Array $parameters)
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
