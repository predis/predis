<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!file_exists(__DIR__.'/enable.tests')) {
    exit(
        "Please create an empty file named 'enabled.tests' inside the test directory ".
        "in order to proceed.\n\n*** DO NOT *** run this test suite against servers in ".
        "a production environment or containing data you are interested in!\n"
    );
}

if (!function_exists('array_union')) {
    function array_union(Array $a, Array $b)
    {
        return array_merge($a, array_diff($b, $a));
    }
}

class RC
{
    const SERVER_VERSION   = '2.2';
    const SERVER_HOST      = '127.0.0.1';
    const SERVER_PORT      = 6379;
    const DEFAULT_DATABASE = 15;

    const WIPE_OUT         = 1;
    const EXCEPTION_WRONG_TYPE     = 'ERR Operation against a key holding the wrong kind of value';
    const EXCEPTION_NO_SUCH_KEY    = 'ERR no such key';
    const EXCEPTION_OUT_OF_RANGE   = 'ERR index out of range';
    const EXCEPTION_OFFSET_RANGE   = 'ERR offset is out of range';
    const EXCEPTION_INVALID_DB_IDX = 'ERR invalid DB index';
    const EXCEPTION_VALUE_NOT_INT  = 'ERR value is not an integer';
    const EXCEPTION_EXEC_NO_MULTI  = 'ERR EXEC without MULTI';
    const EXCEPTION_SETEX_TTL      = 'ERR invalid expire time in SETEX';
    const EXCEPTION_HASH_VALNOTINT = 'ERR hash value is not an integer';
    const EXCEPTION_BIT_VALUE      = 'ERR bit is not an integer or out of range';
    const EXCEPTION_BIT_OFFSET     = 'ERR bit offset is not an integer or out of range';

    private static $_connection;

    public static function getConnectionArguments(Array $additional = array())
    {
        return array_merge(array('host' => RC::SERVER_HOST, 'port' => RC::SERVER_PORT), $additional);
    }

    public static function getConnectionParameters(Array $additional = array())
    {
        return new Predis\ConnectionParameters(self::getConnectionArguments($additional));
    }

    public static function createConnection(Array $additional = array())
    {
        $serverProfile = Predis\Profiles\ServerProfile::get(self::SERVER_VERSION);

        $connection = new Predis\Client(RC::getConnectionArguments($additional), $serverProfile);
        $connection->connect();
        $connection->select(RC::DEFAULT_DATABASE);

        return $connection;
    }

    public static function getConnection($new = false)
    {
        if ($new == true) {
            return self::createConnection();
        }

        if (self::$_connection === null || !self::$_connection->isConnected()) {
            self::$_connection = self::createConnection();
        }

        return self::$_connection;
    }

    public static function resetConnection()
    {
        if (self::$_connection !== null && self::$_connection->isConnected()) {
            self::$_connection->disconnect();
            self::$_connection = self::createConnection();
        }
    }

    public static function helperForBlockingPops($op)
    {
        // TODO: I admit that this helper is kinda lame and it does not run
        //       in a separate process to properly test BLPOP/BRPOP
        $redisUri = sprintf('tcp://%s:%d/?database=%d', RC::SERVER_HOST, RC::SERVER_PORT, RC::DEFAULT_DATABASE);
        $handle = popen('php', 'w');

        fwrite($handle, "<?php
        require __DIR__.'/test/bootstrap.php';
        \$redis = new Predis\Client('$redisUri');
        \$redis->rpush('{$op}1', 'a');
        \$redis->rpush('{$op}2', 'b');
        \$redis->rpush('{$op}3', 'c');
        \$redis->rpush('{$op}1', 'd');
        ?>");

        pclose($handle);
    }

    public static function getArrayOfNumbers()
    {
        return array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
    }

    public static function getKeyValueArray()
    {
        return array(
            'foo'      => 'bar',
            'hoge'     => 'piyo',
            'foofoo'   => 'barbar',
        );
    }

    public static function getNamespacedKeyValueArray()
    {
        return array(
            'metavar:foo'      => 'bar',
            'metavar:hoge'     => 'piyo',
            'metavar:foofoo'   => 'barbar',
        );
    }

    public static function getZSetArray()
    {
        return array('a' => -10, 'b' => 0, 'c' => 10, 'd' => 20, 'e' => 20, 'f' => 30);
    }

    public static function sameValuesInArrays($arrayA, $arrayB)
    {
        if (count($arrayA) != count($arrayB)) {
            return false;
        }

        return count(array_diff($arrayA, $arrayB)) == 0;
    }

    public static function testForServerException($testcaseInstance, $expectedMessage, $wrapFunction)
    {
        $thrownException = null;

        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis\ServerException $exception) {
            $thrownException = $exception;
        }

        $testcaseInstance->assertInstanceOf('Predis\ServerException', $thrownException);

        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function testForClientException($testcaseInstance, $expectedMessage, $wrapFunction)
    {
        $thrownException = null;

        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis\ClientException $exception) {
            $thrownException = $exception;
        }

        $testcaseInstance->assertInstanceOf('Predis\ClientException', $thrownException);

        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function testForCommunicationException($testcaseInstance, $expectedMessage, $wrapFunction)
    {
        $thrownException = null;

        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis\CommunicationException $exception) {
            $thrownException = $exception;
        }

        $testcaseInstance->assertInstanceOf('Predis\CommunicationException', $thrownException);

        if (isset($expectedMessage)) {
            $testcaseInstance->assertEquals($expectedMessage, $thrownException->getMessage());
        }
    }

    public static function testForAbortedMultiExecException($testcaseInstance, $wrapFunction)
    {
        $thrownException = null;

        try {
            $wrapFunction($testcaseInstance);
        }
        catch (Predis\Transaction\AbortedMultiExecException $exception) {
            $thrownException = $exception;
        }

        $testcaseInstance->assertInstanceOf('Predis\Transaction\AbortedMultiExecException', $thrownException);
    }

    public static function pushTailAndReturn(Predis\Client $client, $keyName, Array $values, $wipeOut = 0)
    {
        if ($wipeOut == true) {
            $client->del($keyName);
        }

        foreach ($values as $value) {
            $client->rpush($keyName, $value);
        }

        return $values;
    }

    public static function setAddAndReturn(Predis\Client $client, $keyName, Array $values, $wipeOut = 0)
    {
        if ($wipeOut == true) {
            $client->del($keyName);
        }

        foreach ($values as $value) {
            $client->sadd($keyName, $value);
        }

        return $values;
    }

    public static function zsetAddAndReturn(Predis\Client $client, $keyName, Array $values, $wipeOut = 0)
    {
        // $values: array(SCORE => VALUE, ...);
        if ($wipeOut == true) {
            $client->del($keyName);
        }

        foreach ($values as $value => $score) {
            $client->zadd($keyName, $score, $value);
        }

        return $values;
    }

    public static function getConnectionParametersArgumentsArray()
    {
        return array(
            'host' => '10.0.0.1',
            'port' => 6380,
            'connection_timeout' => 10,
            'read_write_timeout' => 30,
            'database' => 5,
            'password' => 'dbpassword',
            'alias' => 'connection_alias',
        );
    }

    public static function getConnectionParametersArgumentsString($arguments = null)
    {
        // TODO: must be improved
        $args = $arguments ?: RC::getConnectionParametersArgumentsArray();
        $paramsString = "tcp://{$args['host']}:{$args['port']}/?";

        unset($args['host']);
        unset($args['port']);

        foreach($args as $k => $v) {
            $paramsString .= "$k=$v&";
        }

        return $paramsString;
    }
}
