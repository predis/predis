<?php

namespace Predis\Command;

use \PHPUnit_Framework_TestCase as StandardTestCase;
use Predis\Client;
use Predis\Profile\ServerProfile;
use Predis\Profile\ServerProfileInterface;


class CommandWithPrefixTest extends StandardTestCase
{
    /**
     * Returns a new client instance with prefix.
     *
     * @param Boolean $connect Flush selected database before returning the client.
     * @return Client
     */
    protected function getClient()
    {
        $parameters = array(
            'host' => REDIS_SERVER_HOST,
            'port' => REDIS_SERVER_PORT,
        );

        $options = array(
            'prefix'  => 'myPrefix:',
        );

        $client = new Client($parameters, $options);
        $client->connect();
        $client->select(REDIS_SERVER_DBNUM);

        $client->flushall();

        return $client;
    }

    /**
     *
     */
    public function testCommandWithPrefix()
    {
        $redis = $this->getClient();
        $redis->getProfile()->defineCommand('myEval', '\Predis\Command\EvalScriptCommand');
        $redis->myEval('key1', 'key2', 1, 'a');
    }
}

class EvalScriptCommand extends ScriptedCommand
{
    public function getKeysCount()
    {
        return 2;
    }

    public function getScript()
    {
        // Added a random variable in the script, for generate a random script hash everytime.
        $randValue = mt_rand();

        return <<<LUA
local l
l = redis.call('HGET', KEYS[1], ARGV[1])
if not l then
    l = ARGV[2]
    redis.call('HSET', KEYS[1], $randValue, l)
end
redis.call('ZINCRBY', KEYS[2]..l, 1, ARGV[1])

LUA;
    }
}