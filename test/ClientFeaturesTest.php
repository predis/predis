<?php

class ClientFeaturesTestSuite extends PHPUnit_Framework_TestCase {
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
        $params = new Predis_ConnectionParameters();

        $this->assertEquals(Predis_ConnectionParameters::DEFAULT_HOST, $params->host);
        $this->assertEquals(Predis_ConnectionParameters::DEFAULT_PORT, $params->port);
        $this->assertEquals(Predis_ConnectionParameters::DEFAULT_TIMEOUT, $params->connection_timeout);
        $this->assertNull($params->read_write_timeout);
        $this->assertNull($params->database);
        $this->assertNull($params->password);
        $this->assertNull($params->alias);
    }

    function testConnectionParametersSetupValuesArray() {
        $paramsArray = RC::getConnectionParametersArgumentsArray();
        $params = new Predis_ConnectionParameters($paramsArray);

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
        $params = new Predis_ConnectionParameters($paramsArray);

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

        $cmd = new Predis_Commands_GetMultiple();
        $cmd->setArgumentsArray($cmdArgs);
        $this->assertEquals($cmdArgs[0], $cmd->getArgument(0));
        $this->assertEquals($cmdArgs[1], $cmd->getArgument(1));
        $this->assertEquals($cmdArgs[2], $cmd->getArgument(2));

        $cmd = new Predis_Commands_GetMultiple();
        $cmd->setArguments('key1', 'key2', 'key3');
        $this->assertEquals($cmdArgs[0], $cmd->getArgument(0));
        $this->assertEquals($cmdArgs[1], $cmd->getArgument(1));
        $this->assertEquals($cmdArgs[2], $cmd->getArgument(2));

        $cmd = new Predis_Commands_Ping();
        $this->assertNull($cmd->getArgument(0));
    }

    function testCommand_InlineWithNoArguments() {
        $cmd = new Predis_Compatibility_v1_0_Commands_Ping();

        $this->assertInstanceOf('Predis_InlineCommand', $cmd);
        $this->assertEquals('PING', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNull($cmd->getHash(new Predis_Distribution_HashRing()));
        $this->assertEquals("PING\r\n", $cmd->invoke());
    }

    function testCommand_InlineWithArguments() {
        $cmd = new Predis_Compatibility_v1_0_Commands_Get();
        $cmd->setArgumentsArray(array('key'));

        $this->assertInstanceOf('Predis_InlineCommand', $cmd);
        $this->assertEquals('GET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new Predis_Distribution_HashRing()));
        $this->assertEquals("GET key\r\n", $cmd->invoke());
    }

    function testCommand_BulkWithArguments() {
        $cmd = new Predis_Compatibility_v1_0_Commands_Set();
        $cmd->setArgumentsArray(array('key', 'value'));

        $this->assertInstanceOf('Predis_BulkCommand', $cmd);
        $this->assertEquals('SET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new Predis_Distribution_HashRing()));
        $this->assertEquals("SET key 5\r\nvalue\r\n", $cmd->invoke());
    }

    function testCommand_MultiBulkWithArguments() {
        $cmd = new Predis_Commands_SetMultiple();
        $cmd->setArgumentsArray(array('key1', 'value1', 'key2', 'value2'));

        $this->assertInstanceOf('Predis_MultiBulkCommand', $cmd);
        $this->assertEquals('MSET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new Predis_Distribution_HashRing()));
        $this->assertEquals("*5\r\n$4\r\nMSET\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n", $cmd->invoke());
    }

    function testCommand_ParseResponse() {
        // default parser
        $cmd = new Predis_Commands_Get();
        $this->assertEquals('test', $cmd->parseResponse('test'));

        // overridden parser (boolean)
        $cmd = new Predis_Commands_Exists();
        $this->assertTrue($cmd->parseResponse('1'));
        $this->assertFalse($cmd->parseResponse('0'));

        // overridden parser (boolean)
        $cmd = new Predis_Commands_Ping();
        $this->assertTrue($cmd->parseResponse('PONG'));

        // overridden parser (complex)
        // TODO: emulate a respons to INFO
    }


    /* RedisServerProfile and derivates */

    function testRedisServerProfile_GetSpecificVersions() {
        $this->assertInstanceOf('Predis_RedisServer_v1_0', Predis_RedisServerProfile::get('1.0'));
        $this->assertInstanceOf('Predis_RedisServer_v1_2', Predis_RedisServerProfile::get('1.2'));
        $this->assertInstanceOf('Predis_RedisServer_v2_0', Predis_RedisServerProfile::get('2.0'));
        $this->assertInstanceOf('Predis_RedisServer_vNext', Predis_RedisServerProfile::get('dev'));
        $this->assertInstanceOf('Predis_RedisServerProfile', Predis_RedisServerProfile::get('default'));
        $this->assertEquals(Predis_RedisServerProfile::get('default'), Predis_RedisServerProfile::getDefault());
    }

    function testRedisServerProfile_SupportedCommands() {
        $profile_10 = Predis_RedisServerProfile::get('1.0');
        $profile_12 = Predis_RedisServerProfile::get('1.2');

        $this->assertTrue($profile_10->supportsCommand('info'));
        $this->assertTrue($profile_12->supportsCommand('info'));

        $this->assertFalse($profile_10->supportsCommand('mset'));
        $this->assertTrue($profile_12->supportsCommand('mset'));

        $this->assertFalse($profile_10->supportsCommand('multi'));
        $this->assertFalse($profile_12->supportsCommand('multi'));
    }

    function testRedisServerProfile_CommandsCreation() {
        $profile = Predis_RedisServerProfile::get('1.0');

        $cmdNoArgs = $profile->createCommand('info');
        $this->assertInstanceOf('Predis_Compatibility_v1_0_Commands_Info', $cmdNoArgs);
        $this->assertNull($cmdNoArgs->getArgument());

        $args = array('key1', 'key2');
        $cmdWithArgs = $profile->createCommand('mget', $args);
        $this->assertInstanceOf('Predis_Compatibility_v1_0_Commands_GetMultiple', $cmdWithArgs);
        $this->assertEquals($args[0], $cmdWithArgs->getArgument()); // TODO: why?
        $this->assertEquals($args[0], $cmdWithArgs->getArgument(0));
        $this->assertEquals($args[1], $cmdWithArgs->getArgument(1));

        $bogusCommand    = 'not_existing_command';
        $expectedMessage = "'$bogusCommand' is not a registered Redis command";
        RC::testForClientException($this, $expectedMessage, p_anon("\$test", "
            \$profile = Predis_RedisServerProfile::getDefault();
            \$profile->createCommand('$bogusCommand');
        "));
    }

    function testRedisServerProfile_CommandsRegistration() {
        $profile  = Predis_RedisServerProfile::get('1.0');
        $cmdId    = 'mset';
        $cmdClass = 'Predis_Commands_SetMultiple';

        $this->assertFalse($profile->supportsCommand($cmdId));
        $profile->registerCommand(new $cmdClass(), $cmdId);
        $this->assertTrue($profile->supportsCommand($cmdId));
        $this->assertInstanceOf($cmdClass, $profile->createCommand($cmdId));
    }


    /* ResponseQueued */

    function testResponseQueued() {
        $response = new Predis_ResponseQueued();
        $this->assertTrue($response->skipParse);
        $this->assertTrue($response->queued);
        $this->assertEquals(Predis_Protocol::QUEUED, (string)$response);
    }


    /* ResponseError */

    function testResponseError() {
        $errorMessage = 'ERROR MESSAGE';
        $response = new Predis_ResponseError($errorMessage);

        $this->assertTrue($response->skipParse);
        $this->assertTrue($response->error);
        $this->assertEquals($errorMessage, $response->message);
        $this->assertEquals($errorMessage, (string)$response);
    }


    /* Connection */

    function testConnection_StringCastReturnsIPAndPort() {
        $connection = new Predis_Connection(RC::getConnectionParameters());
        $this->assertEquals(RC::SERVER_HOST . ':' . RC::SERVER_PORT, (string) $connection);
    }

    function testConnection_ConnectDisconnect() {
        $connection = new Predis_Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    function testConnection_WriteAndReadCommand() {
        $cmd = Predis_RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis_Connection(RC::getConnectionParameters());
        $connection->connect();

        $connection->writeCommand($cmd);
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_WriteCommandAndCloseConnection() {
        $cmd = Predis_RedisServerProfile::getDefault()->createCommand('quit');
        $connection = new Predis_Connection(new Predis_ConnectionParameters(
            RC::getConnectionArguments() + array('read_write_timeout' => 0.5)
        ));

        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->writeCommand($cmd);
        $connection->disconnect();

        $expectedMessage = 'Error while reading line from the server';
        $thrownException = null;
        try {
            $connection->readResponse($cmd);
        }
        catch (Predis_CommunicationException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_CommunicationException', $thrownException);
        $this->assertEquals($expectedMessage, $thrownException->getMessage());
    }

    function testConnection_GetSocketOpensConnection() {
        $connection = new Predis_Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $this->assertInternalType('resource', $connection->getSocket());
        $this->assertTrue($connection->isConnected());
    }

    function testConnection_LazyConnect() {
        $cmd = Predis_RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis_Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->writeCommand($cmd);
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_RawCommand() {
        $connection = new Predis_Connection(RC::getConnectionParameters());
        $this->assertEquals('PONG', $connection->rawCommand("PING\r\n"));
    }

    function testConnection_Alias() {
        $connection1 = new Predis_Connection(RC::getConnectionParameters());
        $this->assertNull($connection1->getParameters()->alias);

        $args = array_merge(RC::getConnectionArguments(), array('alias' => 'servername'));
        $connection2 = new Predis_Connection(new Predis_ConnectionParameters($args));
        $this->assertEquals('servername', $connection2->getParameters()->alias);
    }

    function testConnection_ConnectionTimeout() {
        $timeout = 3;
        $args    = array('host' => '1.0.0.1', 'connection_timeout' => $timeout);
        $connection = new Predis_Connection(new Predis_ConnectionParameters($args));

        $start = time();
        $thrownException = null;
        try {
            $connection->connect();
        }
        catch (Predis_CommunicationException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_CommunicationException', $thrownException);
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }

    function testConnection_ReadTimeout() {
        $timeout = 1;
        $args    = array_merge(RC::getConnectionArguments(), array('read_write_timeout' => $timeout));
        $cmdFake = Predis_RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis_Connection(new Predis_ConnectionParameters($args));

        $expectedMessage = 'Error while reading line from the server';
        $start = time();
        $thrownException = null;
        try {
            $connection->readResponse($cmdFake);
        }
        catch (Predis_CommunicationException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_CommunicationException', $thrownException);
        $this->assertEquals($expectedMessage, $thrownException->getMessage());
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }


    /* ResponseReader */

    function testResponseReader_OptionIterableMultiBulkReplies() {
        $connection = new Predis_Connection(RC::getConnectionParameters());

        $connection->getResponseReader()->setOption('iterable_multibulk', false);
        $this->assertInternalType('array', $connection->rawCommand("KEYS *\r\n"));

        $connection->getResponseReader()->setOption('iterable_multibulk', true);
        $this->assertInstanceOf('Iterator', $connection->rawCommand("KEYS *\r\n"));
    }

    function testResponseReader_OptionExceptionOnError() {
        $connection = new Predis_Connection(RC::getConnectionParameters());
        $responseReader = $connection->getResponseReader();
        $connection->rawCommand("*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n");
        $rawCmdUnexpected = "*3\r\n$5\r\nLPUSH\r\n$3\r\nkey\r\n$5\r\nvalue\r\n";

        $responseReader->setOption('throw_on_error', false);
        $errorReply = $connection->rawCommand($rawCmdUnexpected);
        $this->assertInstanceOf('Predis_ResponseError', $errorReply);
        $this->assertEquals(RC::EXCEPTION_WRONG_TYPE, $errorReply->message);

        $responseReader->setOption('throw_on_error', true);
        $thrownException = null;
        try {
            $connection->rawCommand($rawCmdUnexpected);
        }
        catch (Predis_ServerException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_ServerException', $thrownException);
        $this->assertEquals(RC::EXCEPTION_WRONG_TYPE, $thrownException->getMessage());
    }

    function testResponseReader_EmptyBulkResponse() {
        $this->assertTrue($this->redis->set('foo', ''));
        $this->assertEquals('', $this->redis->get('foo'));
        $this->assertEquals('', $this->redis->get('foo'));
    }


    /* Client + CommandPipeline */

    function testCommandPipeline_Simple() {
        $client = RC::getConnection();
        $client->flushdb();

        $pipe = $client->pipeline();

        $this->assertInstanceOf('Predis_CommandPipeline', $pipe);
        $this->assertInstanceOf('Predis_CommandPipeline', $pipe->set('foo', 'bar'));
        $this->assertInstanceOf('Predis_CommandPipeline', $pipe->set('hoge', 'piyo'));
        $this->assertInstanceOf('Predis_CommandPipeline', $pipe->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertInstanceOf('Predis_CommandPipeline', $pipe->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $pipe->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testCommandPipeline_FluentInterface() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testCommandPipeline_CallableAnonymousBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline(p_anon("\$pipe", "
            \$pipe->ping();
            \$pipe->set('foo', 'bar');
            \$pipe->get('foo');
        "));

        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testCommandPipeline_ClientExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $expectedMessage = 'TEST';
        $thrownException = null;
        try {
            $client->pipeline(p_anon("\$pipe", "
                \$pipe->ping();
                \$pipe->set('foo', 'bar');
                throw new Predis_ClientException('$expectedMessage');
            "));
        }
        catch (Predis_ClientException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_ClientException', $thrownException);
        $this->assertEquals($expectedMessage, $thrownException->getMessage());

        $this->assertFalse($client->exists('foo'));
    }

    function testCommandPipeline_ServerExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->getResponseReader()->setOption('throw_on_error', false);

        $replies = $client->pipeline(p_anon("\$pipe", "
            \$pipe->set('foo', 'bar');
            \$pipe->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
            \$pipe->set('hoge', 'piyo');
        "));

        $this->assertInternalType('array', $replies);
        $this->assertInstanceOf('Predis_ResponseError', $replies[1]);
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

        $this->assertInternalType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals('bar', $replies[3][0]);
        $this->assertEquals('piyo', $replies[3][1]);
    }


    /* Client + MultiExecBlock  */

    function testMultiExecBlock_Simple() {
        $client = RC::getConnection();
        $client->flushdb();

        $multi = $client->multiExec();

        $this->assertInstanceOf('Predis_MultiExecBlock', $multi);
        $this->assertInstanceOf('Predis_MultiExecBlock', $multi->set('foo', 'bar'));
        $this->assertInstanceOf('Predis_MultiExecBlock', $multi->set('hoge', 'piyo'));
        $this->assertInstanceOf('Predis_MultiExecBlock', $multi->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertInstanceOf('Predis_MultiExecBlock', $multi->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $multi->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testMultiExecBlock_FluentInterface() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testMultiExecBlock_CallableAnonymousBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(p_anon("\$multi", "
            \$multi->ping();
            \$multi->set('foo', 'bar');
            \$multi->get('foo');
        "));

        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    /**
     * @expectedException Predis_ClientException
     */
    function testMultiExecBlock_CannotMixFluentInterfaceAndAnonymousBlock() {
        $emptyBlock = p_anon("\$tx", "");
        $tx = RC::getConnection()->multiExec()->get('foo')->execute($emptyBlock);
    }

    function testMultiExecBlock_EmptyCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(p_anon("\$multi", ""));
        $this->assertEquals(0, count($replies));

        $options = array('cas' => true);
        $replies = $client->multiExec($options, p_anon("\$multi", ""));
        $this->assertEquals(0, count($replies));

        $options = array('cas' => true);
        $replies = $client->multiExec($options, p_anon("\$multi", "
            \$multi->multi();
        "));
        $this->assertEquals(0, count($replies));
    }

    function testMultiExecBlock_ClientExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();

        $expectedMessage = 'TEST';
        $thrownException = null;
        try {
            $client->multiExec(p_anon("\$multi", " 
                \$multi->ping();
                \$multi->set('foo', 'bar');
                throw new Predis_ClientException('$expectedMessage');
            "));
        }
        catch (Predis_ClientException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_ClientException', $thrownException);
        $this->assertEquals($expectedMessage, $thrownException->getMessage());

        $this->assertFalse($client->exists('foo'));
    }

    function testMultiExecBlock_ServerExceptionInCallableBlock() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->getResponseReader()->setOption('throw_on_error', false);

        $multi = $client->multiExec();
        $multi->set('foo', 'bar');
        $multi->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
        $multi->set('hoge', 'piyo');
        $replies = $multi->execute();

        $this->assertInternalType('array', $replies);
        $this->assertInstanceOf('Predis_ResponseError', $replies[1]);
        $this->assertTrue($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testMultiExecBlock_Discard() {
        $client = RC::getConnection();
        $client->flushdb();

        $multi = $client->multiExec();
        $multi->set('foo', 'bar');
        $multi->discard();
        $multi->set('hoge', 'piyo');
        $replies = $multi->execute();

        $this->assertEquals(1, count($replies));
        $this->assertFalse($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testMultiExecBlock_DiscardEmpty() {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec()->discard()->execute();
        $this->assertEquals(0, count($replies));
    }

    function testMultiExecBlock_Watch() {
        $client1 = RC::getConnection();
        $client2 = RC::getConnection(true);
        $client1->flushdb();

        $thrownException = null;
        try {
            $multi = $client1->multiExec(array('watch' => 'sentinel'));
            $multi->set('sentinel', 'client1');
            $multi->get('sentinel');
            $client2->set('sentinel', 'client2');
            $multi->execute();
        }
        catch (PredisException $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_AbortedMultiExec', $thrownException);
        $this->assertEquals('The current transaction has been aborted by the server', $thrownException->getMessage());

        $this->assertEquals('client2', $client1->get('sentinel'));
    }

    function testMultiExecBlock_CheckAndSet() {
        $client = RC::getConnection();
        $client->flushdb();
        $client->set('foo', 'bar');

        $options = array('watch' => 'foo', 'cas' => true);
        $replies = $client->multiExec($options, p_anon("\$tx", "
            \$tx->watch('foobar');
            \$foo = \$tx->get('foo');
            \$tx->multi();
            \$tx->set('foobar', \$foo);
            \$tx->mget('foo', 'foobar');
        "));
        $this->assertInternalType('array', $replies);
        $this->assertEquals(array(true, array('bar', 'bar')), $replies);

        $tx = $client->multiExec($options);
        $tx->watch('foobar');
        $foo = $tx->get('foo');
        $replies = $tx->multi()
                      ->set('foobar', $foo)
                      ->mget('foo', 'foobar')
                      ->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals(array(true, array('bar', 'bar')), $replies);
    }

    function testMultiExecBlock_RetryOnServerAbort() {
        $client1 = RC::getConnection();
        $client1->flushdb();

        $retry = 3;
        $thrownException = null;
        try {
            $options = array('watch' => 'sentinel', 'retry' => $retry);
            $client1->multiExec($options, p_anon("\$tx", "
                \$tx->set('sentinel', 'client1');
                \$tx->get('sentinel');
                \$client2 = RC::getConnection(true);
                \$client2->incr('attempts');
                \$client2->set('sentinel', 'client2');
            "));
        }
        catch (Predis_AbortedMultiExec $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_AbortedMultiExec', $thrownException);
        $this->assertEquals('The current transaction has been aborted by the server', $thrownException->getMessage());
        $this->assertEquals('client2', $client1->get('sentinel'));
        $this->assertEquals($retry + 1, $client1->get('attempts'));

        $client1->del('attempts', 'sentinel');
        $thrownException = null;
        try {
            $options = array(
                'watch' => 'sentinel',
                'cas'   => true,
                'retry' => $retry
            );
            $client1->multiExec($options, p_anon("\$tx", "
                \$tx->incr('attempts');
                \$tx->multi();
                \$tx->set('sentinel', 'client1');
                \$tx->get('sentinel');
                \$client2 = RC::getConnection(true);
                \$client2->set('sentinel', 'client2');
            "));
        }
        catch (Predis_AbortedMultiExec $exception) {
            $thrownException = $exception;
        }
        $this->assertInstanceOf('Predis_AbortedMultiExec', $thrownException);
        $this->assertEquals('The current transaction has been aborted by the server', $thrownException->getMessage());
        $this->assertEquals('client2', $client1->get('sentinel'));
        $this->assertEquals($retry + 1, $client1->get('attempts'));
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
        $replies = $client->multiExec($options, p_anon("\$tx", "
            \$tx->watch('foobar');
            \$foo = \$tx->get('foo');
            \$tx->multi();
            \$tx->set('foobar', \$foo);
            \$tx->discard();
            \$tx->mget('foo', 'foobar');
        "));
        $this->assertInternalType('array', $replies);
        $this->assertEquals(array(array('bar', null)), $replies);

        $hijack = true;
        $client->set('foo', 'bar');
        $options = array('watch' => 'foo', 'cas' => true, 'retry' => 1);
        $replies = $client->multiExec($options, p_anon("\$tx", "
            \$client2 = RC::getConnection(true);
            \$hijack = \$client2->get('foo') !== 'hijacked';
            \$foo = \$tx->get('foo');
            \$tx->multi();
            \$tx->set('foobar', \$foo);
            \$tx->discard();
            if (\$hijack) {
                \$client2->set('foo', 'hijacked!');
            }
            \$tx->mget('foo', 'foobar');
        "));
        $this->assertInternalType('array', $replies);
        $this->assertEquals(array(array('hijacked!', null)), $replies);
    }
}
?>
