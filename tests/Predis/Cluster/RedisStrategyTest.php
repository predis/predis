<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use Predis\Profile;
use PredisTestCase;

/**
 *
 */
class RedisStrategyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportsKeyTags()
    {
        $strategy = $this->getClusterStrategy();

        $this->assertSame(12182, $strategy->getSlotByKey('{foo}'));
        $this->assertSame(12182, $strategy->getSlotByKey('{foo}:bar'));
        $this->assertSame(12182, $strategy->getSlotByKey('{foo}:baz'));
        $this->assertSame(12182, $strategy->getSlotByKey('bar:{foo}:baz'));
        $this->assertSame(12182, $strategy->getSlotByKey('bar:{foo}:{baz}'));

        $this->assertSame(12182, $strategy->getSlotByKey('bar:{foo}:baz{}'));
        $this->assertSame(9415,  $strategy->getSlotByKey('{}bar:{foo}:baz'));

        $this->assertSame(0,     $strategy->getSlotByKey(''));
        $this->assertSame(15257, $strategy->getSlotByKey('{}'));
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $strategy = $this->getClusterStrategy();

        $this->assertSame($this->getExpectedCommands(), $strategy->getSupportedCommands());
    }

    /**
     * @group disconnected
     */
    public function testReturnsNullOnUnsupportedCommand()
    {
        $strategy = $this->getClusterStrategy();
        $command = Profile\Factory::getDevelopment()->createCommand('ping');

        $this->assertNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testFirstKeyCommands()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key');

        foreach ($this->getExpectedCommands('keys-first') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommandsWithOneKey()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key');

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommandsWithMoreKeys()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key1', 'key2');

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommandsWithOneKey()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key:1', 'value1');

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommandsWithMoreKeys()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key:1', 'value1', 'key:2', 'value2');

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForSortCommand()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('{key}:1', 'value1', '{key}:2', 'value2');

        $commandID = 'SORT';

        $command = $profile->createCommand($commandID, array('{key}:1'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $profile->createCommand($commandID, array('{key}:1', array('STORE' => '{key}:2')));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommandsWithOneKey()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key:1', 10);

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommandsWithMoreKeys()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('key:1', 'key:2', 10);

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForGeoradiusCommand()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();

        $commandID = 'GEORADIUS';

        $command = $profile->createCommand($commandID, array('{key}:1', 10, 10, 1, 'km'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $profile->createCommand($commandID, array('{key}:1', 10, 10, 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForGeoradiusByMemberCommand()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();

        $commandID = 'GEORADIUSBYMEMBER';

        $command = $profile->createCommand($commandID, array('{key}:1', 'member', 1, 'km'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $profile->createCommand($commandID, array('{key}:1', 'member', 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForEvalCommand()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();
        $arguments = array('%SCRIPT%', 1, 'key:1', 'value1');

        foreach ($this->getExpectedCommands('keys-script') as $commandID) {
            $command = $profile->createCommand($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForScriptCommand()
    {
        $strategy = $this->getClusterStrategy();
        $arguments = array('key:1', 'value1');

        $command = $this->getMock('Predis\Command\ScriptCommand', array('getScript', 'getKeysCount'));
        $command->expects($this->once())
                ->method('getScript')
                ->will($this->returnValue('return true'));
        $command->expects($this->exactly(2))
                ->method('getKeysCount')
                ->will($this->returnValue(1));
        $command->setArguments($arguments);

        $this->assertNotNull($strategy->getSlot($command), "Script Command [{$command->getId()}]");
    }

    /**
     * @group disconnected
     */
    public function testUnsettingCommandHandler()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();

        $strategy->setCommandHandler('set');
        $strategy->setCommandHandler('get', null);

        $command = $profile->createCommand('set', array('key', 'value'));
        $this->assertNull($strategy->getSlot($command));

        $command = $profile->createCommand('get', array('key'));
        $this->assertNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testSettingCustomCommandHandler()
    {
        $strategy = $this->getClusterStrategy();
        $profile = Profile\Factory::getDevelopment();

        $callable = $this->getMock('stdClass', array('__invoke'));
        $callable->expects($this->once())
                 ->method('__invoke')
                 ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
                 ->will($this->returnValue('key'));

        $strategy->setCommandHandler('get', $callable);

        $command = $profile->createCommand('get', array('key'));
        $this->assertNotNull($strategy->getSlot($command));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Creates the default cluster strategy object.
     *
     * @return StrategyInterface
     */
    protected function getClusterStrategy()
    {
        $strategy = new RedisStrategy();

        return $strategy;
    }

    /**
     * Returns the list of expected supported commands.
     *
     * @param string $type Optional type of command (based on its keys)
     *
     * @return array
     */
    protected function getExpectedCommands($type = null)
    {
        $commands = array(
            /* commands operating on the key space */
            'EXISTS' => 'keys-all',
            'DEL' => 'keys-all',
            'TYPE' => 'keys-first',
            'EXPIRE' => 'keys-first',
            'EXPIREAT' => 'keys-first',
            'PERSIST' => 'keys-first',
            'PEXPIRE' => 'keys-first',
            'PEXPIREAT' => 'keys-first',
            'TTL' => 'keys-first',
            'PTTL' => 'keys-first',
            'SORT' => 'keys-first', // TODO
            'DUMP' => 'keys-first',
            'RESTORE' => 'keys-first',

            /* commands operating on string values */
            'APPEND' => 'keys-first',
            'DECR' => 'keys-first',
            'DECRBY' => 'keys-first',
            'GET' => 'keys-first',
            'GETBIT' => 'keys-first',
            'MGET' => 'keys-all',
            'SET' => 'keys-first',
            'GETRANGE' => 'keys-first',
            'GETSET' => 'keys-first',
            'INCR' => 'keys-first',
            'INCRBY' => 'keys-first',
            'INCRBYFLOAT' => 'keys-first',
            'SETBIT' => 'keys-first',
            'SETEX' => 'keys-first',
            'MSET' => 'keys-interleaved',
            'MSETNX' => 'keys-interleaved',
            'SETNX' => 'keys-first',
            'SETRANGE' => 'keys-first',
            'STRLEN' => 'keys-first',
            'SUBSTR' => 'keys-first',
            'BITOP' => 'keys-bitop',
            'BITCOUNT' => 'keys-first',
            'BITFIELD' => 'keys-first',

            /* commands operating on lists */
            'LINSERT' => 'keys-first',
            'LINDEX' => 'keys-first',
            'LLEN' => 'keys-first',
            'LPOP' => 'keys-first',
            'RPOP' => 'keys-first',
            'RPOPLPUSH' => 'keys-all',
            'BLPOP' => 'keys-blockinglist',
            'BRPOP' => 'keys-blockinglist',
            'BRPOPLPUSH' => 'keys-blockinglist',
            'LPUSH' => 'keys-first',
            'LPUSHX' => 'keys-first',
            'RPUSH' => 'keys-first',
            'RPUSHX' => 'keys-first',
            'LRANGE' => 'keys-first',
            'LREM' => 'keys-first',
            'LSET' => 'keys-first',
            'LTRIM' => 'keys-first',

            /* commands operating on sets */
            'SADD' => 'keys-first',
            'SCARD' => 'keys-first',
            'SDIFF' => 'keys-all',
            'SDIFFSTORE' => 'keys-all',
            'SINTER' => 'keys-all',
            'SINTERSTORE' => 'keys-all',
            'SUNION' => 'keys-all',
            'SUNIONSTORE' => 'keys-all',
            'SISMEMBER' => 'keys-first',
            'SMEMBERS' => 'keys-first',
            'SSCAN' => 'keys-first',
            'SPOP' => 'keys-first',
            'SRANDMEMBER' => 'keys-first',
            'SREM' => 'keys-first',

            /* commands operating on sorted sets */
            'ZADD' => 'keys-first',
            'ZCARD' => 'keys-first',
            'ZCOUNT' => 'keys-first',
            'ZINCRBY' => 'keys-first',
            'ZINTERSTORE' => 'keys-zaggregated',
            'ZRANGE' => 'keys-first',
            'ZRANGEBYSCORE' => 'keys-first',
            'ZRANK' => 'keys-first',
            'ZREM' => 'keys-first',
            'ZREMRANGEBYRANK' => 'keys-first',
            'ZREMRANGEBYSCORE' => 'keys-first',
            'ZREVRANGE' => 'keys-first',
            'ZREVRANGEBYSCORE' => 'keys-first',
            'ZREVRANK' => 'keys-first',
            'ZSCORE' => 'keys-first',
            'ZUNIONSTORE' => 'keys-zaggregated',
            'ZSCAN' => 'keys-first',
            'ZLEXCOUNT' => 'keys-first',
            'ZRANGEBYLEX' => 'keys-first',
            'ZREMRANGEBYLEX' => 'keys-first',
            'ZREVRANGEBYLEX' => 'keys-first',

            /* commands operating on hashes */
            'HDEL' => 'keys-first',
            'HEXISTS' => 'keys-first',
            'HGET' => 'keys-first',
            'HGETALL' => 'keys-first',
            'HMGET' => 'keys-first',
            'HMSET' => 'keys-first',
            'HINCRBY' => 'keys-first',
            'HINCRBYFLOAT' => 'keys-first',
            'HKEYS' => 'keys-first',
            'HLEN' => 'keys-first',
            'HSET' => 'keys-first',
            'HSETNX' => 'keys-first',
            'HVALS' => 'keys-first',
            'HSCAN' => 'keys-first',
            'HSTRLEN' => 'keys-first',

            /* commands operating on HyperLogLog */
            'PFADD' => 'keys-first',
            'PFCOUNT' => 'keys-all',
            'PFMERGE' => 'keys-all',

            /* scripting */
            'EVAL' => 'keys-script',
            'EVALSHA' => 'keys-script',

            /* commands performing geospatial operations */
            'GEOADD' => 'keys-first',
            'GEOHASH' => 'keys-first',
            'GEOPOS' => 'keys-first',
            'GEODIST' => 'keys-first',
            'GEORADIUS' => 'keys-georadius',
            'GEORADIUSBYMEMBER' => 'keys-georadius',
        );

        if (isset($type)) {
            $commands = array_filter($commands, function ($expectedType) use ($type) {
                return $expectedType === $type;
            });
        }

        return array_keys($commands);
    }
}
