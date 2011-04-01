<?php
// -------------------------------------------------------------------------- //

define('I_AM_AWARE_OF_THE_DESTRUCTIVE_POWER_OF_THIS_TEST_SUITE', false);

// -------------------------------------------------------------------------- //

Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v1_2', '1.2');
Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v2_0', '2.0');
Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v2_2', '2.2');

if (I_AM_AWARE_OF_THE_DESTRUCTIVE_POWER_OF_THIS_TEST_SUITE !== true) {
    exit(
        "Please set the I_AM_AWARE_OF_THE_DESTRUCTIVE_POWER_OF_THIS_TEST_SUITE " .
        "constant to TRUE in PredisShared.php if you want to proceed.\n"
    );
}

if (!function_exists('array_union')) {
    function array_union(Array $a, Array $b) {
        return array_merge($a, array_diff($b, $a));
    }
}

function p_anon($param, $function) {
    return create_function($param, $function);
}

class RC {
    const SERVER_HOST      = '127.0.0.1';
    const SERVER_PORT      = 6379;
    const DEFAULT_DATABASE = 15;

    const WIPE_OUT         = 1;
    const EXCEPTION_WRONG_TYPE     = 'Operation against a key holding the wrong kind of value';
    const EXCEPTION_NO_SUCH_KEY    = 'no such key';
    const EXCEPTION_OUT_OF_RANGE   = 'index out of range';
    const EXCEPTION_OFFSET_RANGE   = 'offset is out of range';
    const EXCEPTION_INVALID_DB_IDX = 'invalid DB index';
    const EXCEPTION_VALUE_NOT_INT  = 'value is not an integer';
    const EXCEPTION_EXEC_NO_MULTI  = 'EXEC without MULTI';
    const EXCEPTION_SETEX_TTL      = 'invalid expire time in SETEX';
    const EXCEPTION_HASH_VALNOTINT = 'hash value is not an integer';
    const EXCEPTION_BIT_VALUE      = 'bit is not an integer or out of range';
    const EXCEPTION_BIT_OFFSET     = 'bit offset is not an integer or out of range';

    private static $_connection;

    public static function getConnectionArguments() {
        return array('host' => RC::SERVER_HOST, 'port' => RC::SERVER_PORT);
    }

    public static function getConnectionParameters() {
        return new Predis_ConnectionParameters(array('host' => RC::SERVER_HOST, 'port' => RC::SERVER_PORT));
    }

    private static function createConnection() {
        $serverProfile = Predis_RedisServerProfile::get('2.2');
        $connection = new Predis_Client(RC::getConnectionArguments(), $serverProfile);
        $connection->connect();
        $connection->select(RC::DEFAULT_DATABASE);
        return $connection;
    }

    public static function getConnection($new = false) {
        if ($new == true) {
            return self::createConnection();
        }
        if (self::$_connection === null || !self::$_connection->isConnected()) {
            self::$_connection = self::createConnection();
        }
        return self::$_connection;
    }

    public static function resetConnection() {
        if (self::$_connection !== null && self::$_connection->isConnected()) {
            self::$_connection->disconnect();
            self::$_connection = self::createConnection();
        }
    }

    public static function helperForBlockingPops($op) {
        // TODO: I admit that this helper is kinda lame and it does not run 
        //       in a separate process to properly test BLPOP/BRPOP
        $redisUri = sprintf('redis://%s:%d/?database=%d', RC::SERVER_HOST, RC::SERVER_PORT, RC::DEFAULT_DATABASE);
        $handle = popen('php', 'w');
        $dir = __DIR__;
        fwrite($handle, "<?php
        require '{$dir}/../lib/Predis.php';
        \$redis = Predis_Client::create('$redisUri');
        \$redis->rpush('{$op}1', 'a');
        \$redis->rpush('{$op}2', 'b');
        \$redis->rpush('{$op}3', 'c');
        \$redis->rpush('{$op}1', 'd');
        ?>");
        pclose($handle);
    }

    public static function getArrayOfNumbers() {
        return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    }

    public static function getKeyValueArray() {
        return array(
            'foo'      => 'bar', 
            'hoge'     => 'piyo', 
            'foofoo'   => 'barbar', 
        );
    }

    public static function getNamespacedKeyValueArray() {
        return array(
            'metavar:foo'      => 'bar', 
            'metavar:hoge'     => 'piyo', 
            'metavar:foofoo'   => 'barbar', 
        );
    }

    public static function getZSetArray() {
        return array(
            'a' => -10, 'b' => 0, 'c' => 10, 'd' => 20, 'e' => 20, 'f' => 30
        );
    }

    public static function sameValuesInArrays($arrayA, $arrayB) {
        if (count($arrayA) != count($arrayB)) {
            return false;
        }
        return count(array_diff($arrayA, $arrayB)) == 0;
    }

    public static function testForServerException($testcaseInstance, $expectedMessage, $wrapFunction) {
        $thrownException = null;
        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis_ServerException $exception) {
            $thrownException = $exception;
        }
        $testcaseInstance->assertInstanceOf('Predis_ServerException', $thrownException);
        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function testForClientException($testcaseInstance, $expectedMessage, $wrapFunction) {
        $thrownException = null;
        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis_ClientException $exception) {
            $thrownException = $exception;
        }
        $testcaseInstance->assertInstanceOf('Predis_ClientException', $thrownException);
        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function testForCommunicationException($testcaseInstance, $expectedMessage, $wrapFunction) {
        $thrownException = null;
        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis_CommunicationException $exception) {
            $thrownException = $exception;
        }
        $testcaseInstance->assertInstanceOf('Predis_CommunicationException', $thrownException);
        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function pushTailAndReturn(Predis_Client $client, $keyName, Array $values, $wipeOut = 0) {
        if ($wipeOut == true) {
            $client->del($keyName);
        }
        foreach ($values as $value) {
            $client->rpush($keyName, $value);
        }
        return $values;
    }

    public static function setAddAndReturn(Predis_Client $client, $keyName, Array $values, $wipeOut = 0) {
        if ($wipeOut == true) {
            $client->del($keyName);
        }
        foreach ($values as $value) {
            $client->sadd($keyName, $value);
        }
        return $values;
    }

    public static function zsetAddAndReturn(Predis_Client $client, $keyName, Array $values, $wipeOut = 0) {
        // $values: array(SCORE => VALUE, ...);
        if ($wipeOut == true) {
            $client->del($keyName);
        }
        foreach ($values as $value => $score) {
            $client->zadd($keyName, $score, $value);
        }
        return $values;
    }

    public static function getConnectionParametersArgumentsArray() {
        return array(
            'host' => '10.0.0.1', 'port' => 6380, 'connection_timeout' => 10, 'read_write_timeout' => 30, 
            'database' => 5, 'password' => 'dbpassword', 'alias' => 'connection_alias'
        );
    }

    public static function getConnectionParametersArgumentsString($arguments = null) {
        // TODO: must be improved
        $args = $arguments !== null ? $arguments : RC::getConnectionParametersArgumentsArray();
        $paramsString = "redis://{$args['host']}:{$args['port']}/";
        $paramsString .= "?connection_timeout={$args['connection_timeout']}&read_write_timeout={$args['read_write_timeout']}";
        $paramsString .= "&database={$args['database']}&password={$args['password']}&alias={$args['alias']}";
        return $paramsString;
    }
}
?>
