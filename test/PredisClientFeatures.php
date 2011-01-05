<?php
require_once 'PredisShared.php';
require_once '../lib/addons/RedisVersion1_0.php';

class PredisClientFeaturesTestSuite extends PHPUnit_Framework_TestCase {
    public $redis;

    protected function setUp() {
        $this->redis = RC::getConnection();
        $this->redis->flushdb();
    }

    protected function tearDown() {
    }

    protected function onNotSuccessfulTest(Exception $exception) {
        // drops and reconnect to a redis server on uncaught exceptions
        RC::resetConnection();
        parent::onNotSuccessfulTest($exception);
    }

    /* ConnectionParameters */

    function testConnectionParametersDefaultValues() {
        $params = new \Predis\ConnectionParameters();

        $this->assertEquals(\Predis\ConnectionParameters::DEFAULT_HOST, $params->host);
        $this->assertEquals(\Predis\ConnectionParameters::DEFAULT_PORT, $params->port);
        $this->assertEquals(\Predis\ConnectionParameters::DEFAULT_TIMEOUT, $params->connection_timeout);
        $this->assertNull($params->read_write_timeout);
        $this->assertNull($params->database);
        $this->assertNull($params->password);
        $this->assertNull($params->alias);
    }

    function testConnectionParametersSetupValuesArray() {
        $paramsArray = RC::getConnectionParametersArgumentsArray();
        $params = new \Predis\ConnectionParameters($paramsArray);

        $this->assertEquals($paramsArray['host'], $params->host);
        $this->assertEquals($paramsArray['port'], $params->port);
        $this->assertEquals($paramsArray['connection_timeout'], $params->connection_timeout);
        $this->assertEquals($paramsArray['read_write_timeout'], $params->read_write_timeout);
        $this->assertEquals($paramsArray['database'], $params->database);
        $this->assertEquals($paramsArray['password'], $params->password);
        $this->assertEquals($paramsArray['alias'], $params->alias);
    }

    function testConnectionParametersSetupValuesString() {
        $paramsArray  = RC::getConnectionParametersArgumentsArray();
        $paramsString = RC::getConnectionParametersArgumentsString($paramsArray);
        $params = new \Predis\ConnectionParameters($paramsArray);

        $this->assertEquals($paramsArray['host'], $params->host);
        $this->assertEquals($paramsArray['port'], $params->port);
        $this->assertEquals($paramsArray['connection_timeout'], $params->connection_timeout);
        $this->assertEquals($paramsArray['read_write_timeout'], $params->read_write_timeout);
        $this->assertEquals($paramsArray['database'], $params->database);
        $this->assertEquals($paramsArray['password'], $params->password);
        $this->assertEquals($paramsArray['alias'], $params->alias);
    }


    /* Command and derivates */

    function testCommand_TestArguments() {
        $cmdArgs = array('key1', 'key2', 'key3');

        $cmd = new \Predis\Commands\GetMultiple();
        $cmd->setArgumentsArray($cmdArgs);
        $this->assertEquals($cmdArgs[0], $cmd->getArgument(0));
        $this->assertEquals($cmdArgs[1], $cmd->getArgument(1));
        $this->assertEquals($cmdArgs[2], $cmd->getArgument(2));

        $cmd = new \Predis\Commands\GetMultiple();
        $cmd->setArguments('key1', 'key2', 'key3');
        $this->assertEquals($cmdArgs[0], $cmd->getArgument(0));
        $this->assertEquals($cmdArgs[1], $cmd->getArgument(1));
        $this->assertEquals($cmdArgs[2], $cmd->getArgument(2));

        $cmd = new \Predis\Commands\Ping();
        $this->assertNull($cmd->getArgument(0));
    }

    function testCommand_InlineWithNoArguments() {
        $cmd = new \Predis\Compatibility\v1_0\Commands\Ping();

        $this->assertType('\Predis\InlineCommand', $cmd);
        $this->assertEquals('PING', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("PING\r\n", $cmd->serialize());
    }

    function testCommand_InlineWithArguments() {
        $cmd = new \Predis\Compatibility\v1_0\Commands\Get();
        $cmd->setArgumentsArray(array('key'));

        $this->assertType('\Predis\InlineCommand', $cmd);
        $this->assertEquals('GET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("GET key\r\n", $cmd->serialize());
    }

    function testCommand_BulkWithArguments() {
        $cmd = new \Predis\Compatibility\v1_0\Commands\Set();
        $cmd->setArgumentsArray(array('key', 'value'));

        $this->assertType('\Predis\BulkCommand', $cmd);
        $this->assertEquals('SET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("SET key 5\r\nvalue\r\n", $cmd->serialize());
    }

    function testCommand_MultiBulkWithArguments() {
        $cmd = new \Predis\Commands\SetMultiple();
        $cmd->setArgumentsArray(array('key1', 'value1', 'key2', 'value2'));

        $this->assertType('\Predis\Command', $cmd);
        $this->assertEquals('MSET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("*5\r\n$4\r\nMSET\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n", $cmd->serialize());
    }

    function testCommand_ParseResponse() {
        // default parser
        $cmd = new \Predis\Commands\Get();
        $this->assertEquals('test', $cmd->parseResponse('test'));

        // overridden parser (boolean)
        $cmd = new \Predis\Commands\Exists();
        $this->assertTrue($cmd->parseResponse('1'));
        $this->assertFalse($cmd->parseResponse('0'));

        // overridden parser (boolean)
        $cmd = new \Predis\Commands\Ping();
        $this->assertTrue($cmd->parseResponse('PONG'));

        // overridden parser (complex)
        // TODO: emulate a respons to INFO
    }


    /* RedisServerProfile and derivates */

    function testRedisServerProfile_GetSpecificVersions() {
        $this->assertType('\Predis\RedisServer_v1_0', \Predis\RedisServerProfile::get('1.0'));
        $this->assertType('\Predis\RedisServer_v1_2', \Predis\RedisServerProfile::get('1.2'));
        $this->assertType('\Predis\RedisServer_v2_0', \Predis\RedisServerProfile::get('2.0'));
        $this->assertType('\Predis\RedisServer_vNext', \Predis\RedisServerProfile::get('dev'));
        $this->assertType('\Predis\RedisServerProfile', \Predis\RedisServerProfile::get('default'));
        $this->assertEquals(\Predis\RedisServerProfile::get('default'), \Predis\RedisServerProfile::getDefault());
    }

    function testRedisServerProfile_SupportedCommands() {
        $profile_10 = \Predis\RedisServerProfile::get('1.0');
        $profile_12 = \Predis\RedisServerProfile::get('1.2');

        $this->assertTrue($profile_10->supportsCommand('info'));
        $this->assertTrue($profile_12->supportsCommand('info'));

        $this->assertFalse($profile_10->supportsCommand('mset'));
        $this->assertTrue($profile_12->supportsCommand('mset'));

        $this->assertFalse($profile_10->supportsCommand('multi'));
        $this->assertFalse($profile_12->supportsCommand('multi'));
    }

    function testRedisServerProfile_CommandsCreation() {
        $profile = \Predis\RedisServerProfile::get('1.0');

        $cmdNoArgs = $profile->createCommand('info');
        $this->assertType('\Predis\Compatibility\v1_0\Commands\Info', $cmdNoArgs);
        $this->assertNull($cmdNoArgs->getArgument());

        $args = array('key1', 'key2');
        $cmdWithArgs = $profile->createCommand('mget', $args);
        $this->assertType('\Predis\Compatibility\v1_0\Commands\GetMultiple', $cmdWithArgs);
        $this->assertEquals($args[0], $cmdWithArgs->getArgument()); // TODO: why?
        $this->assertEquals($args[0], $cmdWithArgs->getArgument(0));
        $this->assertEquals($args[1], $cmdWithArgs->getArgument(1));

        $bogusCommand    = 'not_existing_command';
        $expectedMessage = "'$bogusCommand' is not a registered Redis command";
        RC::testForClientException($this, $expectedMessage, function()
            use($profile, $bogusCommand) {

            $profile->createCommand($bogusCommand);
        });
    }

    function testRedisServerProfile_CommandsRegistration() {
        $profile  = \Predis\RedisServerProfile::get('1.0');
        $cmdId    = 'mset';
        $cmdClass = '\Predis\Commands\SetMultiple';

        $this->assertFalse($profile->supportsCommand($cmdId));
        $profile->registerCommand(new $cmdClass(), $cmdId);
        $this->assertTrue($profile->supportsCommand($cmdId));
        $this->assertType($cmdClass, $profile->createCommand($cmdId));
    }


    /* ResponseQueued */

    function testResponseQueued() {
        $response = new \Predis\ResponseQueued();
        $this->assertTrue($response->queued);
        $this->assertEquals(\Predis\Protocol::QUEUED, (string)$response);
    }


    /* ResponseError */

    function testResponseError() {
        $errorMessage = 'ERROR MESSAGE';
        $response = new \Predis\ResponseError($errorMessage);

        $this->assertTrue($response->error);
        $this->assertEquals($errorMessage, $response->message);
        $this->assertEquals($errorMessage, (string)$response);
    }


    /* Connection */

    function testConnection_StringCastReturnsIPAndPort() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $this->assertEquals(RC::SERVER_HOST . ':' . RC::SERVER_PORT, (string) $connection);
    }

    function testConnection_ConnectDisconnect() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    function testConnection_WriteAndReadCommand() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $connection->connect();

        $connection->writeCommand($cmd);
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_WriteCommandAndCloseConnection() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('quit');
        $connection = new \Predis\TcpConnection(new \Predis\ConnectionParameters(
            RC::getConnectionArguments() + array('read_write_timeout' => 0.5)
        ));

        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->writeCommand($cmd);
        $connection->disconnect();

        $exceptionMessage = 'Error while reading line from the server';
        RC::testForCommunicationException($this, $exceptionMessage, function() use($connection, $cmd) {
            $connection->readResponse($cmd);
        });
    }

    function testConnection_GetSocketOpensConnection() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $this->assertType('resource', $connection->getSocket());
        $this->assertTrue($connection->isConnected());
    }

    function testConnection_LazyConnect() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->writeCommand($cmd);
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_RawCommand() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $this->assertEquals('PONG', $connection->rawCommand("PING\r\n"));
    }

    function testConnection_Alias() {
        $connection1 = new \Predis\TcpConnection(RC::getConnectionParameters());
        $this->assertNull($connection1->getParameters()->alias);

        $args = array_merge(RC::getConnectionArguments(), array('alias' => 'servername'));
        $connection2 = new \Predis\TcpConnection(new \Predis\ConnectionParameters($args));
        $this->assertEquals('servername', $connection2->getParameters()->alias);
    }

    function testConnection_ConnectionTimeout() {
        $timeout = 3;
        $args    = array('host' => '1.0.0.1', 'connection_timeout' => $timeout);
        $connection = new \Predis\TcpConnection(new \Predis\ConnectionParameters($args));

        $start = time();
        RC::testForCommunicationException($this, null, function() use($connection) {
            $connection->connect();
        });
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }

    function testConnection_ReadTimeout() {
        $timeout = 1;
        $args    = array_merge(RC::getConnectionArguments(), array('read_write_timeout' => $timeout));
        $cmdFake = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\TcpConnection(new \Predis\ConnectionParameters($args));

        $expectedMessage = 'Error while reading line from the server';
        $start = time();
        RC::testForCommunicationException($this, $expectedMessage, function() use($connection, $cmdFake) {
            $connection->readResponse($cmdFake);
        });
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }


    /* ResponseReader */

    function testResponseReader_OptionIterableMultiBulkReplies() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $responseReader = $connection->getResponseReader();

        $responseReader->setHandler(
            \Predis\Protocol::PREFIX_MULTI_BULK,
            new \Predis\ResponseMultiBulkHandler()
        );
        $this->assertType('array', $connection->rawCommand("KEYS *\r\n"));

        $responseReader->setHandler(
            \Predis\Protocol::PREFIX_MULTI_BULK,
            new \Predis\ResponseMultiBulkStreamHandler()
        );
        $this->assertType('\Iterator', $connection->rawCommand("KEYS *\r\n"));
    }

    function testResponseReader_OptionExceptionOnError() {
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $responseReader = $connection->getResponseReader();
        $connection->rawCommand("*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n");
        $rawCmdUnexpected = "*3\r\n$5\r\nLPUSH\r\n$3\r\nkey\r\n$5\r\nvalue\r\n";

        $responseReader->setHandler(
            \Predis\Protocol::PREFIX_ERROR,
            new \Predis\ResponseErrorSilentHandler()
        );
        $errorReply = $connection->rawCommand($rawCmdUnexpected);
        $this->assertType('\Predis\ResponseError', $errorReply);
        $this->assertEquals(RC::EXCEPTION_WRONG_TYPE, $errorReply->message);

        $responseReader->setHandler(
            \Predis\Protocol::PREFIX_ERROR,
            new \Predis\ResponseErrorHandler()
        );
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function()
            use ($connection, $rawCmdUnexpected) {

            $connection->rawCommand($rawCmdUnexpected);
        });
    }


    /* Client initialization */

    function testClientInitialization_SingleConnectionParameters() {
        $params1 = array_merge(RC::getConnectionArguments(), array(
            'connection_timeout' => 10,
            'read_write_timeout' => 30,
            'alias' => 'connection_alias',
        ));
        $params2 = RC::getConnectionParametersArgumentsString($params1);
        $params3 = new \Predis\ConnectionParameters($params1);
        $params4 = new \Predis\TcpConnection($params3);
        foreach (array($params1, $params2, $params3, $params4) as $params) {
            $client = new \Predis\Client($params);
            $parameters = $client->getConnection()->getParameters();
            $this->assertEquals($params1['host'], $parameters->host);
            $this->assertEquals($params1['port'], $parameters->port);
            $this->assertEquals($params1['connection_timeout'], $parameters->connection_timeout);
            $this->assertEquals($params1['read_write_timeout'], $parameters->read_write_timeout);
            $this->assertEquals($params1['alias'], $parameters->alias);
            $this->assertNull($parameters->password);
        }
    }

    function testClientInitialization_ClusterConnectionParameters() {
        $params1 = array_merge(RC::getConnectionArguments(), array(
            'connection_timeout' => 10,
            'read_write_timeout' => 30,
        ));
        $params2 = RC::getConnectionParametersArgumentsString($params1);
        $params3 = new \Predis\ConnectionParameters($params1);
        $params4 = new \Predis\TcpConnection($params3);

        $connectionCluster1 = array($params1, $params2, $params3, $params4);
        $connectionCluster2 = array($params4);
        $connectionCluster3 = new \Predis\ConnectionCluster();
        $connectionCluster3->add($params4);

        foreach (array($connectionCluster1, $connectionCluster2, $connectionCluster3) as $connectionCluster) {
            $client = new \Predis\Client($connectionCluster);

            foreach ($client->getConnection() as $connection) {
                $parameters = $connection->getParameters();
                $this->assertEquals($params1['host'], $parameters->host);
                $this->assertEquals($params1['port'], $parameters->port);
                $this->assertEquals($params1['connection_timeout'], $parameters->connection_timeout);
                $this->assertEquals($params1['read_write_timeout'], $parameters->read_write_timeout);
                $this->assertNull($parameters->password);
            }
        }

        foreach (array($connectionCluster2, $connectionCluster3) as $connectionCluster) {
            $client = new \Predis\Client($connectionCluster);

            foreach ($client->getConnection() as $connection) {
                $this->assertSame($params4, $connection);
            }
        }
    }

    /* Client + CommandPipeline */

    function testCommandPipeline_Simple() {
        $client = RC::getConnection();
        $client->flushdb();

        $pipe = $client->pipeline();

        $this->assertType('\Predis\CommandPipeline', $pipe);
        $this->assertType('\Predis\CommandPipeline', $pipe->set('foo', 'bar'));
        $this->assertType('\Predis\CommandPipeline', $pipe->set('hoge', 'piyo'));
        $this->assertType('\Predis\CommandPipeline', $pipe->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertType('\Predis\CommandPipeline', $pipe->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $pipe->execute();
        $this->assertType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testCommandPipeline_FluentInterface() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testCommandPipeline_CallableAnonymousBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline(function($pipe) {
            $pipe->ping();
            $pipe->set('foo', 'bar');
            $pipe->get('foo');
        });

        $this->assertType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testCommandPipeline_ClientExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        RC::testForClientException($this, 'TEST', function() use($client) {
            $client->pipeline(function($pipe) {
                $pipe->ping();
                $pipe->set('foo', 'bar');
                throw new \Predis\ClientException("TEST");
            });
        });
        $this->assertFalse($client->exists('foo'));
    }

    function testCommandPipeline_ServerExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->getResponseReader()->setHandler('-', new \Predis\ResponseErrorSilentHandler());

        $replies = $client->pipeline(function($pipe) {
            $pipe->set('foo', 'bar');
            $pipe->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
            $pipe->set('hoge', 'piyo');
        });

        $this->assertType('array', $replies);
        $this->assertType('\Predis\ResponseError', $replies[1]);
        $this->assertTrue($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testCommandPipeline_Flush() {
        $client = RC::getConnection();
        $client->flushdb();

        $pipe = $client->pipeline();
        $pipe->set('foo', 'bar')->set('hoge', 'piyo');
        $pipe->flushPipeline();
        $pipe->ping()->mget(array('foo', 'hoge'));
        $replies = $pipe->execute();

        $this->assertType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals('bar', $replies[3][0]);
        $this->assertEquals('piyo', $replies[3][1]);
    }


    /* Client + MultiExecBlock  */

    function testMultiExecBlock_Simple() {
        $client = RC::getConnection();
        $client->flushdb();

        $multi = $client->multiExec();

        $this->assertType('\Predis\MultiExecBlock', $multi);
        $this->assertType('\Predis\MultiExecBlock', $multi->set('foo', 'bar'));
        $this->assertType('\Predis\MultiExecBlock', $multi->set('hoge', 'piyo'));
        $this->assertType('\Predis\MultiExecBlock', $multi->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertType('\Predis\MultiExecBlock', $multi->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $multi->execute();
        $this->assertType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testMultiExecBlock_FluentInterface() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testMultiExecBlock_CallableAnonymousBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->ping();
            $multi->set('foo', 'bar');
            $multi->get('foo');
        });

        $this->assertType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    /**
     * @expectedException Predis\ClientException
     */
    function testMultiExecBlock_CannotMixFluentInterfaceAndAnonymousBlock() {
        $emptyBlock = function($tx) { };
        $tx = RC::getConnection()->multiExec()->get('foo')->execute($emptyBlock);
    }

    function testMultiExecBlock_EmptyCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) { });
        $this->assertEquals(0, count($replies));

        $options = array('cas' => true);
        $replies = $client->multiExec($options, function($multi) { });
        $this->assertEquals(0, count($replies));

        $options = array('cas' => true);
        $replies = $client->multiExec($options, function($multi) {
            $multi->multi();
        });
        $this->assertEquals(0, count($replies));
    }

    function testMultiExecBlock_ClientExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        RC::testForClientException($this, 'TEST', function() use($client) {
            $client->multiExec(function($multi) {
                $multi->ping();
                $multi->set('foo', 'bar');
                throw new \Predis\ClientException("TEST");
            });
        });
        $this->assertFalse($client->exists('foo'));
    }

    function testMultiExecBlock_ServerExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->getResponseReader()->setHandler('-', new \Predis\ResponseErrorSilentHandler());

        $replies = $client->multiExec(function($multi) {
            $multi->set('foo', 'bar');
            $multi->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
            $multi->set('hoge', 'piyo');
        });

        $this->assertType('array', $replies);
        $this->assertType('\Predis\ResponseError', $replies[1]);
        $this->assertTrue($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testMultiExecBlock_Discard() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->set('foo', 'bar');
            $multi->discard();
            $multi->set('hoge', 'piyo');
        });

        $this->assertEquals(1, count($replies));
        $this->assertFalse($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testMultiExecBlock_DiscardEmpty() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->discard();
        });

        $this->assertEquals(0, count($replies));
    }

    function testMultiExecBlock_Watch() {
        $client1 = RC::getConnection();
        $client2 = RC::getConnection(true);
        $client1->flushdb();

        RC::testForAbortedMultiExecException($this, function()
            use($client1, $client2) {

            $client1->multiExec(array('watch' => 'sentinel'), function($multi)
                use ($client2) {

                $multi->set('sentinel', 'client1');
                $multi->get('sentinel');
                $client2->set('sentinel', 'client2');
            });
        });

        $this->assertEquals('client2', $client1->get('sentinel'));
    }

    function testMultiExecBlock_CheckAndSet() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->set('foo', 'bar');

        $options = array('watch' => 'foo', 'cas' => true);
        $replies = $client->multiExec($options, function($tx) {
            $tx->watch('foobar');
            $foo = $tx->get('foo');
            $tx->multi();
            $tx->set('foobar', $foo);
            $tx->mget('foo', 'foobar');
        });
        $this->assertType('array', $replies);
        $this->assertEquals(array(true, array('bar', 'bar')), $replies);

        $tx = $client->multiExec($options);
        $tx->watch('foobar');
        $foo = $tx->get('foo');
        $replies = $tx->multi()
                      ->set('foobar', $foo)
                      ->mget('foo', 'foobar')
                      ->execute();
        $this->assertType('array', $replies);
        $this->assertEquals(array(true, array('bar', 'bar')), $replies);
    }

    function testMultiExecBlock_RetryOnServerAbort() {
        $client1 = RC::getConnection();
        $client2 = RC::getConnection(true);
        $client1->flushdb();

        $retry = 3;
        $attempts = 0;
        RC::testForAbortedMultiExecException($this, function()
            use($client1, $client2, $retry, &$attempts) {

            $options = array('watch' => 'sentinel', 'retry' => $retry);
            $client1->multiExec($options, function($tx)
                use ($client2, &$attempts) {

                $attempts++;
                $tx->set('sentinel', 'client1');
                $tx->get('sentinel');
                $client2->set('sentinel', 'client2');
            });
        });
        $this->assertEquals('client2', $client1->get('sentinel'));
        $this->assertEquals($retry + 1, $attempts);

        $retry = 3;
        $attempts = 0;
        RC::testForAbortedMultiExecException($this, function()
            use($client1, $client2, $retry, &$attempts) {

            $options = array(
                'watch' => 'sentinel',
                'cas'   => true,
                'retry' => $retry
            );
            $client1->multiExec($options, function($tx)
                use ($client2, &$attempts) {

                $attempts++;
                $tx->incr('attempts');
                $tx->multi();
                $tx->set('sentinel', 'client1');
                $tx->get('sentinel');
                $client2->set('sentinel', 'client2');
            });
        });
        $this->assertEquals('client2', $client1->get('sentinel'));
        $this->assertEquals($retry + 1, $attempts);
        $this->assertEquals($attempts, $client1->get('attempts'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    function testMultiExecBlock_RetryNotAvailableWithoutBlock() {
        $options = array('watch' => 'foo', 'retry' => 1);
        $tx = RC::getConnection()->multiExec($options);
        $tx->multi()->get('foo')->exec();
    }

    function testMultiExecBlock_CheckAndSet_Discard() {
        $client = RC::getConnection();
        $client->flushdb();

        $client->set('foo', 'bar');
        $options = array('watch' => 'foo', 'cas' => true);
        $replies = $client->multiExec($options, function($tx) {
            $tx->watch('foobar');
            $foo = $tx->get('foo');
            $tx->multi();
            $tx->set('foobar', $foo);
            $tx->discard();
            $tx->mget('foo', 'foobar');
        });
        $this->assertType('array', $replies);
        $this->assertEquals(array(array('bar', null)), $replies);

        $hijack = true;
        $client->set('foo', 'bar');
        $client2 = RC::getConnection(true);
        $options = array('watch' => 'foo', 'cas' => true, 'retry' => 1);
        $replies = $client->multiExec($options, function($tx)
            use ($client2, &$hijack) {

            $foo = $tx->get('foo');
            $tx->multi();
            $tx->set('foobar', $foo);
            $tx->discard();
            if ($hijack) {
                $hijack = false;
                $client2->set('foo', 'hijacked!');
            }
            $tx->mget('foo', 'foobar');
        });
        $this->assertType('array', $replies);
        $this->assertEquals(array(array('hijacked!', null)), $replies);
    }
}
?>
