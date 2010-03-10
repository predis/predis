<?php
define('I_AM_AWARE_OF_THE_DESTRUCTIVE_POWER_OF_THIS_TEST_SUITE', false);

require_once 'PHPUnit/Framework.php';
require_once 'PredisShared.php';

class RedisCommandTestSuite extends PHPUnit_Framework_TestCase {
    public $redis;

    protected function setUp() { 
        $this->redis = RC::getConnection();
        $this->redis->flushDatabase();
    }

    protected function tearDown() { 
    }

    protected function onNotSuccessfulTest($exception) {
        // drops and reconnect to a redis server on uncaught exceptions
        RC::resetConnection();
        parent::onNotSuccessfulTest($exception);
    }


    /* ConnectionParameters */

    function testConnectionParametersDefaultValues() {
        $params = new Predis\ConnectionParameters();

        $this->assertEquals(Predis\ConnectionParameters::DEFAULT_HOST, $params->host);
        $this->assertEquals(Predis\ConnectionParameters::DEFAULT_PORT, $params->port);
        $this->assertEquals(Predis\ConnectionParameters::DEFAULT_TIMEOUT, $params->connection_timeout);
        $this->assertNull($params->read_write_timeout);
        $this->assertNull($params->database);
        $this->assertNull($params->password);
        $this->assertNull($params->alias);
    }

    function testConnectionParametersSetupValuesArray() {
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

    function testConnectionParametersSetupValuesString() {
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
        $cmd = new \Predis\Commands\Ping();

        $this->assertType('\Predis\InlineCommand', $cmd);
        $this->assertEquals('PING', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNull($cmd->getHash());
        $this->assertEquals("PING\r\n", $cmd());
    }

    function testCommand_InlineWithArguments() {
        $cmd = new \Predis\Commands\Get();
        $cmd->setArgumentsArray(array('key'));

        $this->assertType('\Predis\InlineCommand', $cmd);
        $this->assertEquals('GET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash());
        $this->assertEquals("GET key\r\n", $cmd());
    }

    function testCommand_BulkWithArguments() {
        $cmd = new \Predis\Commands\Set();
        $cmd->setArgumentsArray(array('key', 'value'));

        $this->assertType('\Predis\BulkCommand', $cmd);
        $this->assertEquals('SET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertTrue($cmd->canBeHashed());
        $this->assertNotNull($cmd->getHash());
        $this->assertEquals("SET key 5\r\nvalue\r\n", $cmd());
    }

    function testCommand_MultiBulkWithArguments() {
        $cmd = new \Predis\Commands\SetMultiple();
        $cmd->setArgumentsArray(array('key1', 'value1', 'key2', 'value2'));

        $this->assertType('\Predis\MultiBulkCommand', $cmd);
        $this->assertEquals('MSET', $cmd->getCommandId());
        $this->assertFalse($cmd->closesConnection());
        $this->assertFalse($cmd->canBeHashed());
        $this->assertNull($cmd->getHash());
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
        $this->assertType('\Predis\Commands\Info', $cmdNoArgs);
        $this->assertNull($cmdNoArgs->getArgument());

        $args = array('key1', 'key2');
        $cmdWithArgs = $profile->createCommand('mget', $args);
        $this->assertType('\Predis\Commands\GetMultiple', $cmdWithArgs);
        $this->assertEquals($args[0], $cmdWithArgs->getArgument()); // TODO: why?
        $this->assertEquals($args[0], $cmdWithArgs->getArgument(0));
        $this->assertEquals($args[1], $cmdWithArgs->getArgument(1));

        $bogusCommand    = 'not_existing_command';
        $expectedMessage = "'$bogusCommand' is not a registered Redis command";
        RC::testForClientException($this, $expectedMessage, function($test) 
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
        $this->assertEquals(\Predis\ResponseReader::QUEUED, (string)$response);
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
        $connection = new \Predis\Connection(RC::getConnectionParameters());
        $this->assertEquals(RC::SERVER_HOST . ':' . RC::SERVER_PORT, (string) $connection);
    }

    function testConnection_ConnectDisconnect() {
        $connection = new \Predis\Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    function testConnection_WriteAndReadCommand() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\Connection(RC::getConnectionParameters());
        $connection->connect();

        $connection->writeCommand($cmd);
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_WriteCommandAndCloseConnection() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('quit');
        $connection = new \Predis\Connection(RC::getConnectionParameters());
        $connection->connect();

        $this->assertTrue($connection->isConnected());
        $connection->writeCommand($cmd);
        $exceptionMessage = 'An error has occurred while reading from the network stream';
        RC::testForClientException($this, $exceptionMessage, function($test) use($connection, $cmd) {
            $connection->readResponse($cmd);
        });
        //$this->assertFalse($connection->isConnected());
    }

    function testConnection_GetSocketOpensConnection() {
        $connection = new \Predis\Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $this->assertType('resource', $connection->getSocket());
        $this->assertTrue($connection->isConnected());
    }

    function testConnection_LazyConnect() {
        $cmd = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\Connection(RC::getConnectionParameters());

        $this->assertFalse($connection->isConnected());
        $connection->writeCommand($cmd);
        $this->assertTrue($connection->isConnected());
        $this->assertTrue($connection->readResponse($cmd));
    }

    function testConnection_RawCommand() {
        $connection = new \Predis\Connection(RC::getConnectionParameters());
        $this->assertEquals('PONG', $connection->rawCommand("PING\r\n"));
    }

    function testConnection_Alias() {
        $connection1 = new \Predis\Connection(RC::getConnectionParameters());
        $this->assertNull($connection1->getAlias());

        $args = array_merge(RC::getConnectionArguments(), array('alias' => 'servername'));
        $connection2 = new \Predis\Connection(new \Predis\ConnectionParameters($args));
        $this->assertEquals('servername', $connection2->getAlias());
    }

    function testConnection_ConnectionTimeout() {
        $timeout = 3;
        $args    = array('host' => '1.0.0.1', 'connection_timeout' => $timeout);
        $connection = new \Predis\Connection(new \Predis\ConnectionParameters($args));

        $start = time();
        RC::testForClientException($this, null, function($test) use($connection) {
            $connection->connect();
        });
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }

    function testConnection_ReadTimeout() {
        $timeout = 1;
        $args    = array_merge(RC::getConnectionArguments(), array('read_write_timeout' => $timeout));
        $cmdFake = \Predis\RedisServerProfile::getDefault()->createCommand('ping');
        $connection = new \Predis\Connection(new \Predis\ConnectionParameters($args));

        $start = time();
        RC::testForClientException($this, null, function($test) use($connection, $cmdFake) {
            $connection->readResponse($cmdFake);
        });
        $this->assertEquals((float)(time() - $start), $timeout, '', 1);
    }
}
?>