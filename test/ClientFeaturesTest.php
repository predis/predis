<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class ClientFeaturesTestSuite extends PHPUnit_Framework_TestCase
{
    public $redis;

    protected function setUp()
    {
        $this->redis = RC::getConnection();
        $this->redis->flushdb();
    }

    protected function tearDown()
    {
    }

    protected function onNotSuccessfulTest(Exception $exception)
    {
        // drops and reconnect to a redis server on uncaught exceptions
        RC::resetConnection();

        parent::onNotSuccessfulTest($exception);
    }

    /* Predis\ConnectionParameters */

    function testConnectionParametersDefaultValues()
    {
        $params = new Predis\ConnectionParameters();

        $this->assertEquals('127.0.0.1', $params->host);
        $this->assertEquals(6379, $params->port);
        $this->assertEquals(5, $params->connection_timeout);
        $this->assertNull($params->read_write_timeout);
        $this->assertNull($params->database);
        $this->assertNull($params->password);
        $this->assertNull($params->alias);
    }

    function testConnectionParametersSetupValuesArray()
    {
        $paramsArray = RC::getConnectionParametersArgumentsArray();
        $params = new Predis\ConnectionParameters($paramsArray);

        $this->assertEquals($paramsArray['host'], $params->host);
        $this->assertEquals($paramsArray['port'], $params->port);
        $this->assertEquals($paramsArray['connection_timeout'], $params->connection_timeout);
        $this->assertEquals($paramsArray['read_write_timeout'], $params->read_write_timeout);
        $this->assertEquals($paramsArray['database'], $params->database);
        $this->assertEquals($paramsArray['password'], $params->password);
        $this->assertEquals($paramsArray['alias'], $params->alias);
    }

    function testConnectionParametersSetupValuesString()
    {
        $paramsArray  = RC::getConnectionParametersArgumentsArray();
        $paramsString = RC::getConnectionParametersArgumentsString($paramsArray);
        $params = new Predis\ConnectionParameters($paramsArray);

        $this->assertEquals($paramsArray['host'], $params->host);
        $this->assertEquals($paramsArray['port'], $params->port);
        $this->assertEquals($paramsArray['connection_timeout'], $params->connection_timeout);
        $this->assertEquals($paramsArray['read_write_timeout'], $params->read_write_timeout);
        $this->assertEquals($paramsArray['database'], $params->database);
        $this->assertEquals($paramsArray['password'], $params->password);
        $this->assertEquals($paramsArray['alias'], $params->alias);
    }

    /* Predis\Commands\Command and derivates */

    function testCommand_TestArguments()
    {
        $cmdArgs = array('key1', 'key2', 'key3');

        $cmd = new Predis\Commands\StringGetMultiple();
        $cmd->setArguments($cmdArgs);
        $this->assertEquals($cmdArgs[0], $cmd->getArgument(0));
        $this->assertEquals($cmdArgs[1], $cmd->getArgument(1));
        $this->assertEquals($cmdArgs[2], $cmd->getArgument(2));

        $cmd = new Predis\Commands\ConnectionPing();
        $this->assertNull($cmd->getArgument(0));
    }

    function testCommand_ParseResponse()
    {
        // default parser
        $cmd = new Predis\Commands\StringGet();
        $this->assertEquals('test', $cmd->parseResponse('test'));

        // overridden parser (boolean)
        $cmd = new Predis\Commands\KeyExists();
        $this->assertTrue($cmd->parseResponse('1'));
        $this->assertFalse($cmd->parseResponse('0'));

        // overridden parser (boolean)
        $cmd = new Predis\Commands\ConnectionPing();
        $this->assertTrue($cmd->parseResponse('PONG'));

        // overridden parser (complex)
        // TODO: emulate a respons to INFO
    }


    /* Predis\Profiles\ServerProfile and derivates */

    function testServerProfile_GetSpecificVersions()
    {
        $this->assertInstanceOf('\Predis\Profiles\ServerVersion12', Predis\Profiles\ServerProfile::get('1.2'));
        $this->assertInstanceOf('\Predis\Profiles\ServerVersion20', Predis\Profiles\ServerProfile::get('2.0'));
        $this->assertInstanceOf('\Predis\Profiles\ServerVersion22', Predis\Profiles\ServerProfile::get('2.2'));
        $this->assertInstanceOf('\Predis\Profiles\ServerVersionNext', Predis\Profiles\ServerProfile::get('dev'));
        $this->assertInstanceOf('\Predis\Profiles\ServerProfile', Predis\Profiles\ServerProfile::get('default'));
        $this->assertEquals(Predis\Profiles\ServerProfile::get('default'), Predis\Profiles\ServerProfile::getDefault());
    }

    function testServerProfile_SupportedCommands()
    {
        $profile_12 = Predis\Profiles\ServerProfile::get('1.2');
        $profile_20 = Predis\Profiles\ServerProfile::get('2.0');

        $this->assertTrue($profile_12->supportsCommand('info'));
        $this->assertTrue($profile_20->supportsCommand('info'));

        $this->assertFalse($profile_12->supportsCommand('multi'));
        $this->assertTrue($profile_20->supportsCommand('multi'));

        $this->assertFalse($profile_12->supportsCommand('watch'));
        $this->assertFalse($profile_20->supportsCommand('watch'));
    }

    function testServerProfile_CommandsCreation()
    {
        $profile = Predis\Profiles\ServerProfile::get('2.0');

        $cmdNoArgs = $profile->createCommand('info');
        $this->assertInstanceOf('\Predis\Commands\ServerInfo', $cmdNoArgs);
        $this->assertNull($cmdNoArgs->getArgument());

        $args = array('key1', 'key2');
        $cmdWithArgs = $profile->createCommand('mget', $args);

        $this->assertInstanceOf('\Predis\Commands\StringGetMultiple', $cmdWithArgs);
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

    function testServerProfile_CommandsRegistration()
    {
        $profile  = Predis\Profiles\ServerProfile::get('1.2');
        $cmdId    = 'multi';
        $cmdClass = '\Predis\Commands\TransactionMulti';

        $this->assertFalse($profile->supportsCommand($cmdId));
        $profile->defineCommand($cmdId, new $cmdClass());
        $this->assertTrue($profile->supportsCommand($cmdId));
        $this->assertInstanceOf($cmdClass, $profile->createCommand($cmdId));
    }

    function testServerProfile_CaseInsensitiveCommandsNames() {
        $profile = $this->redis->getProfile();

        $uppercase = $profile->supportsCommand('INFO');
        $lowercase = $profile->supportsCommand('info');
        $this->assertEquals($uppercase, $lowercase);

        $uppercase = $profile->createCommand('INFO');
        $lowercase = $profile->createCommand('info');
        $this->assertEquals($uppercase, $lowercase);
    }

    /* Predis\ResponseQueued */

    function testResponseQueued()
    {
        $response = new Predis\ResponseQueued();
        $this->assertInstanceOf('\Predis\IReplyObject', $response);
        $this->assertEquals(\Predis\Protocol\Text\TextProtocol::QUEUED, (string)$response);
    }

    /* Predis\ResponseError */

    function testResponseError()
    {
        $errorMessage = 'ERROR MESSAGE';
        $response = new Predis\ResponseError($errorMessage);

        $this->assertInstanceOf('\Predis\IReplyObject', $response);
        $this->assertInstanceOf('\Predis\IRedisServerError', $response);

        $this->assertEquals($errorMessage, $response->getMessage());
        $this->assertEquals($errorMessage, (string)$response);
    }

    /* Predis\Network\StreamConnection */

    function testStreamConnection_StringCastReturnsIPAndPort()
    {
        $connection = new Predis\Network\StreamConnection(RC::getConnectionParameters());
        $this->assertEquals(RC::SERVER_HOST . ':' . RC::SERVER_PORT, (string) $connection);
    }

    function testStreamConnection_ConnectDisconnect()
    {
        $connection = new Predis\Network\StreamConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    function testStreamConnection_WriteAndReadCommand()
    {
        $cmd = Predis\Profiles\ServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis\Network\StreamConnection(RC::getConnectionParameters());
        $connection->connect();

        $connection->writeCommand($cmd);
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testStreamConnection_WriteCommandAndCloseConnection()
    {
        $cmd = Predis\Profiles\ServerProfile::getDefault()->createCommand('quit');
        $connection = new Predis\Network\StreamConnection(new Predis\ConnectionParameters(
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

    function testStreamConnection_GetSocketOpensConnection()
    {
        $connection = new Predis\Network\StreamConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $this->assertInternalType('resource', $connection->getResource());
        $this->assertTrue($connection->isConnected());
    }

    function testStreamConnection_LazyConnect()
    {
        $cmd = Predis\Profiles\ServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis\Network\StreamConnection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());

        $connection->writeCommand($cmd);

        $this->assertTrue($connection->isConnected());
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testStreamConnection_Alias()
    {
        $connection1 = new Predis\Network\StreamConnection(RC::getConnectionParameters());
        $this->assertNull($connection1->getParameters()->alias);

        $args = array_merge(RC::getConnectionArguments(), array('alias' => 'servername'));
        $connection2 = new Predis\Network\StreamConnection(new Predis\ConnectionParameters($args));

        $this->assertEquals('servername', $connection2->getParameters()->alias);
    }

    function testStreamConnection_ConnectionTimeout()
    {
        $timeout = 3;
        $args = array('host' => '1.0.0.1', 'connection_timeout' => $timeout);
        $connection = new Predis\Network\StreamConnection(new Predis\ConnectionParameters($args));

        $start = time();
        RC::testForCommunicationException($this, null, function() use($connection) {
            $connection->connect();
        });

        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }

    function testStreamConnection_ReadTimeout()
    {
        $timeout = 1;
        $args = array_merge(RC::getConnectionArguments(), array('read_write_timeout' => $timeout));
        $cmdFake = Predis\Profiles\ServerProfile::getDefault()->createCommand('ping');
        $connection = new Predis\Network\StreamConnection(new Predis\ConnectionParameters($args));

        $expectedMessage = 'Error while reading line from the server';
        $start = time();
        RC::testForCommunicationException($this, $expectedMessage, function() use($connection, $cmdFake) {
            $connection->readResponse($cmdFake);
        });
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }

    /* Predis\Protocol\TextResponseReader */

    function testResponseReader_OptionIterableMultiBulkReplies()
    {
        $protocol = new Predis\Protocol\Text\ComposableTextProtocol();
        $reader = $protocol->getReader();
        $connection = new Predis\Network\ComposableStreamConnection(RC::getConnectionParameters(), $protocol);

        $reader->setHandler(
            Predis\Protocol\Text\TextProtocol::PREFIX_MULTI_BULK,
            new Predis\Protocol\Text\ResponseMultiBulkHandler()
        );
        $connection->writeBytes("KEYS *\r\n");
        $this->assertInternalType('array', $reader->read($connection));

        $reader->setHandler(
            Predis\Protocol\Text\TextProtocol::PREFIX_MULTI_BULK,
            new Predis\Protocol\Text\ResponseMultiBulkStreamHandler()
        );
        $connection->writeBytes("KEYS *\r\n");
        $this->assertInstanceOf('\Iterator', $reader->read($connection));
    }

    function testResponseReader_OptionExceptionOnError()
    {
        $protocol = new Predis\Protocol\Text\ComposableTextProtocol();
        $reader = $protocol->getReader();
        $connection = new Predis\Network\ComposableStreamConnection(RC::getConnectionParameters(), $protocol);

        $rawCmdUnexpected = "*3\r\n$5\r\nLPUSH\r\n$3\r\nkey\r\n$5\r\nvalue\r\n";
        $connection->writeBytes("*3\r\n$3\r\nSET\r\n$3\r\nkey\r\n$5\r\nvalue\r\n");
        $reader->read($connection);

        $reader->setHandler(
            Predis\Protocol\Text\TextProtocol::PREFIX_ERROR,
            new Predis\Protocol\Text\ResponseErrorSilentHandler()
        );
        $connection->writeBytes($rawCmdUnexpected);
        $errorReply = $reader->read($connection);
        $this->assertInstanceOf('\Predis\ResponseError', $errorReply);
        $this->assertEquals(RC::EXCEPTION_WRONG_TYPE, $errorReply->getMessage());

        $reader->setHandler(
            Predis\Protocol\Text\TextProtocol::PREFIX_ERROR,
            new Predis\Protocol\Text\ResponseErrorHandler()
        );
        RC::testForServerException($this, RC::EXCEPTION_WRONG_TYPE, function()
            use ($connection, $rawCmdUnexpected) {

            $connection->writeBytes($rawCmdUnexpected);
            $connection->getProtocol()->read($connection);
        });
    }

    function testResponseReader_EmptyBulkResponse()
    {
        $protocol = new Predis\Protocol\Text\ComposableTextProtocol();
        $connection = new Predis\Network\ComposableStreamConnection(RC::getConnectionParameters(), $protocol);
        $client = new Predis\Client($connection);

        $this->assertTrue($client->set('foo', ''));
        $this->assertEquals('', $client->get('foo'));
        $this->assertEquals('', $client->get('foo'));
    }

    /* Client initialization */

    function testClientInitialization_SingleConnectionParameters()
    {
        $params1 = array_merge(RC::getConnectionArguments(), array(
            'connection_timeout' => 10,
            'read_write_timeout' => 30,
            'alias' => 'connection_alias',
        ));
        $params2 = RC::getConnectionParametersArgumentsString($params1);
        $params3 = new Predis\ConnectionParameters($params1);
        $params4 = new Predis\Network\StreamConnection($params3);

        foreach (array($params1, $params2, $params3, $params4) as $params) {
            $client = new Predis\Client($params);
            $parameters = $client->getConnection()->getParameters();
            $this->assertEquals($params1['host'], $parameters->host);
            $this->assertEquals($params1['port'], $parameters->port);
            $this->assertEquals($params1['connection_timeout'], $parameters->connection_timeout);
            $this->assertEquals($params1['read_write_timeout'], $parameters->read_write_timeout);
            $this->assertEquals($params1['alias'], $parameters->alias);
            $this->assertNull($parameters->password);
        }
    }

    function testClientInitialization_ClusterConnectionParameters()
    {
        $params1 = array_merge(RC::getConnectionArguments(), array(
            'connection_timeout' => 10,
            'read_write_timeout' => 30,
        ));
        $params2 = RC::getConnectionParametersArgumentsString($params1);
        $params3 = new Predis\ConnectionParameters($params1);
        $params4 = new Predis\Network\StreamConnection($params3);

        $client1 = new Predis\Client(array($params1, $params2, $params3, $params4));

        foreach ($client1->getConnection() as $connection) {
            $parameters = $connection->getParameters();
            $this->assertEquals($params1['host'], $parameters->host);
            $this->assertEquals($params1['port'], $parameters->port);
            $this->assertEquals($params1['connection_timeout'], $parameters->connection_timeout);
            $this->assertEquals($params1['read_write_timeout'], $parameters->read_write_timeout);
            $this->assertNull($parameters->password);
        }

        $connectionCluster = $client1->getConnection();
        $client2 = new Predis\Client($connectionCluster);
        $this->assertSame($connectionCluster, $client2->getConnection());
    }

    /* Client + PipelineContext */

    function testPipelineContext_Simple()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $pipe = $client->pipeline();
        $pipelineClass = '\Predis\Pipeline\PipelineContext';

        $this->assertInstanceOf($pipelineClass, $pipe);
        $this->assertInstanceOf($pipelineClass, $pipe->set('foo', 'bar'));
        $this->assertInstanceOf($pipelineClass, $pipe->set('hoge', 'piyo'));
        $this->assertInstanceOf($pipelineClass, $pipe->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertInstanceOf($pipelineClass, $pipe->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $pipe->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testPipelineContext_FluentInterface()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testPipelineContext_CallableAnonymousBlock()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->pipeline(function($pipe) {
            $pipe->ping();
            $pipe->set('foo', 'bar');
            $pipe->get('foo');
        });

        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testPipelineContext_ClientExceptionInCallableBlock()
    {
        $client = RC::getConnection();
        $client->flushdb();

        RC::testForClientException($this, 'TEST', function() use($client) {
            $client->pipeline(function($pipe) {
                $pipe->ping();
                $pipe->set('foo', 'bar');
                throw new Predis\ClientException("TEST");
            });
        });

        $this->assertFalse($client->exists('foo'));
    }

    function testPipelineContext_ServerExceptionInCallableBlock()
    {
        $client = RC::createConnection(array('throw_errors' => false));
        $client->flushdb();

        $replies = $client->pipeline(function($pipe) {
            $pipe->set('foo', 'bar');
            $pipe->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
            $pipe->set('hoge', 'piyo');
        });

        $this->assertInternalType('array', $replies);
        $this->assertInstanceOf('\Predis\ResponseError', $replies[1]);
        $this->assertTrue($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testPipelineContext_Flush()
    {
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

    /* Predis\Client + Predis\MultiExecContext  */

    function testMultiExecContext_Simple()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $multi = $client->multiExec();
        $transactionClass = '\Predis\Transaction\MultiExecContext';

        $this->assertInstanceOf($transactionClass, $multi);
        $this->assertInstanceOf($transactionClass, $multi->set('foo', 'bar'));
        $this->assertInstanceOf($transactionClass, $multi->set('hoge', 'piyo'));
        $this->assertInstanceOf($transactionClass, $multi->mset(array(
            'foofoo' => 'barbar', 'hogehoge' => 'piyopiyo'
        )));
        $this->assertInstanceOf($transactionClass, $multi->mget(array(
            'foo', 'hoge', 'foofoo', 'hogehoge'
        )));

        $replies = $multi->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals(4, count($replies));
        $this->assertEquals(4, count($replies[3]));
        $this->assertEquals('barbar', $replies[3][2]);
    }

    function testMultiExecContext_FluentInterface()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec()->ping()->set('foo', 'bar')->get('foo')->execute();
        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    function testMultiExecContext_CallableAnonymousBlock()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->ping();
            $multi->set('foo', 'bar');
            $multi->get('foo');
        });

        $this->assertInternalType('array', $replies);
        $this->assertEquals('bar', $replies[2]);
    }

    /**
     * @expectedException Predis\ClientException
     */
    function testMultiExecContext_CannotMixFluentInterfaceAndAnonymousBlock()
    {
        $emptyBlock = function($tx) { };
        $tx = RC::getConnection()->multiExec()->get('foo')->execute($emptyBlock);
    }

    function testMultiExecContext_EmptyCallableBlock()
    {
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

    function testMultiExecContext_ClientExceptionInCallableBlock()
    {
        $client = RC::getConnection();
        $client->flushdb();

        RC::testForClientException($this, 'TEST', function() use($client) {
            $client->multiExec(function($multi) {
                $multi->ping();
                $multi->set('foo', 'bar');
                throw new Predis\ClientException("TEST");
            });
        });

        $this->assertFalse($client->exists('foo'));
    }

    function testMultiExecContext_ServerExceptionInCallableBlock()
    {
        $client = RC::createConnection(array('throw_errors' => false));
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->set('foo', 'bar');
            $multi->lpush('foo', 'piyo'); // LIST operation on STRING type returns an ERROR
            $multi->set('hoge', 'piyo');
        });

        $this->assertInternalType('array', $replies);
        $this->assertInstanceOf('\Predis\ResponseError', $replies[1]);
        $this->assertTrue($client->exists('foo'));
        $this->assertTrue($client->exists('hoge'));
    }

    function testMultiExecContext_Discard()
    {
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

    function testMultiExecContext_DiscardEmpty()
    {
        $client = RC::getConnection();
        $client->flushdb();

        $replies = $client->multiExec(function($multi) {
            $multi->discard();
        });

        $this->assertEquals(0, count($replies));
    }

    function testMultiExecContext_Watch()
    {
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

    function testMultiExecContext_CheckAndSet()
    {
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

    function testMultiExecContext_RetryOnServerAbort()
    {
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
    function testMultiExecContext_RetryNotAvailableWithoutBlock()
    {
        $options = array('watch' => 'foo', 'retry' => 1);
        $tx = RC::getConnection()->multiExec($options);
        $tx->multi()->get('foo')->exec();
    }

    function testMultiExecContext_CheckAndSet_Discard()
    {
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
        $this->assertInternalType('array', $replies);
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
        $this->assertInternalType('array', $replies);
        $this->assertEquals(array(array('hijacked!', null)), $replies);
    }
}
