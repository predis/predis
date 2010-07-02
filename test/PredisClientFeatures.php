<?php
define('I_AM_AWARE_OF_THE_DESTRUCTIVE_POWER_OF_THIS_TEST_SUITE', false);

require_once 'PHPUnit/Framework.php';
require_once 'PredisShared.php';
require_once '../lib/Predis_Compatibility.php';

Predis\Compatibility::loadRedis_v1_0();

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
        $this->assertEquals("PING\r\n", $cmd());
    }

    function testCommand_InlineWithArguments() {
        $cmd = new \Predis\Compatibility\v1_0\Commands\Get();
        $cmd->setArgumentsArray(array('key'));

        $this->assertType('\Predis\InlineCommand', $cmd);
        $this->assertEquals('GET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("GET key\r\n", $cmd());
    }

    function testCommand_BulkWithArguments() {
        $cmd = new \Predis\Compatibility\v1_0\Commands\Set();
        $cmd->setArgumentsArray(array('key', 'value'));

        $this->assertType('\Predis\BulkCommand', $cmd);
        $this->assertEquals('SET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("SET key 5\r\nvalue\r\n", $cmd());
    }

    function testCommand_MultiBulkWithArguments() {
        $cmd = new \Predis\Commands\SetMultiple();
        $cmd->setArgumentsArray(array('key1', 'value1', 'key2', 'value2'));

        $this->assertType('\Predis\MultiBulkCommand', $cmd);
        $this->assertEquals('MSET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash(new \Predis\Distribution\HashRing()));
        $this->assertEquals("*5\r\n$4\r\nMSET\r\n$4\r\nkey1\r\n$6\r\nvalue1\r\n$4\r\nkey2\r\n$6\r\nvalue2\r\n", $cmd());
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
        $connection = new \Predis\TcpConnection(RC::getConnectionParameters());
        $connection->connect();

        $this->assertTrue($connection->isConnected());
        $connection->writeCommand($cmd);
        $exceptionMessage = 'Error while reading line from the server';
        RC::testForCommunicationException($this, $exceptionMessage, function() use($connection, $cmd) {
            $connection->readResponse($cmd);
        });
        //$this->assertFalse($connection->isConnected());
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
        $connection->rawCommand("SET key 5\r\nvalue\r\n");
        $rawCmdUnexpected = "LPUSH key 5\r\nvalue\r\n";

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
}
?>
