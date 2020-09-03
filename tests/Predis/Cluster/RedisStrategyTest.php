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

use PredisTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 *
 */
class RedisStrategyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportsKeyTags(): void
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
    public function testSupportedCommands(): void
    {
        /** @var RedisStrategy */
        $strategy = $this->getClusterStrategy();

        $this->assertSame($this->getExpectedCommands(), $strategy->getSupportedCommands());
    }

    /**
     * @group disconnected
     */
    public function testReturnsNullOnUnsupportedCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $command = $this->getCommandFactory()->create('ping');

        $this->assertNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testFirstKeyCommands(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key');

        foreach ($this->getExpectedCommands('keys-first') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommandsWithOneKey(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key');

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommandsWithMoreKeys(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key1', 'key2');

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommandsWithOneKey(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key:1', 'value1');

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommandsWithMoreKeys(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key:1', 'value1', 'key:2', 'value2');

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForSortCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('{key}:1', 'value1', '{key}:2', 'value2');

        $commandID = 'SORT';

        $command = $commands->create($commandID, array('{key}:1'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, array('{key}:1', array('STORE' => '{key}:2')));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommandsWithOneKey(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key:1', 10);

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommandsWithMoreKeys(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('key:1', 'key:2', 10);

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForGeoradiusCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();

        $commandID = 'GEORADIUS';

        $command = $commands->create($commandID, array('{key}:1', 10, 10, 1, 'km'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, array('{key}:1', 10, 10, 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForGeoradiusByMemberCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();

        $commandID = 'GEORADIUSBYMEMBER';

        $command = $commands->create($commandID, array('{key}:1', 'member', 1, 'km'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, array('{key}:1', 'member', 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3'));
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForEvalCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = array('%SCRIPT%', 1, 'key:1', 'value1');

        foreach ($this->getExpectedCommands('keys-script') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForScriptCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $arguments = array('key:1', 'value1');

        /** @var \Predis\Command\CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript', 'getKeysCount'))
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn('return true');
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->willReturn(1);
        $command->setArguments($arguments);

        $this->assertNotNull($strategy->getSlot($command), "Script Command [{$command->getId()}]");
    }

    /**
     * @group disconnected
     */
    public function testUnsettingCommandHandler(): void
    {
        /** @var RedisStrategy */
        $strategy = $this->getClusterStrategy();
        $strategy->setCommandHandler('set');
        $strategy->setCommandHandler('get', null);

        $commands = $this->getCommandFactory();
        $command = $commands->create('set', array('key', 'value'));
        $this->assertNull($strategy->getSlot($command));

        $command = $commands->create('get', array('key'));
        $this->assertNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testSettingCustomCommandHandler(): void
    {
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(array('__invoke'))
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
            ->willReturn('key');

        /** @var RedisStrategy */
        $strategy = $this->getClusterStrategy();
        $strategy->setCommandHandler('get', $callable);

        $commands = $this->getCommandFactory();
        $command = $commands->create('get', array('key'));

        $this->assertNotNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testThrowsExceptionOnGetDistributorMethod(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage('Predis\Cluster\RedisStrategy does not provide an external distributor');

        $strategy = $this->getClusterStrategy();
        $strategy->getDistributor();
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Creates the default cluster strategy object.
     *
     * @return StrategyInterface
     */
    protected function getClusterStrategy(): StrategyInterface
    {
        $strategy = new RedisStrategy();

        return $strategy;
    }

    /**
     * Returns the list of expected supported commands.
     *
     * @param ?string $type Optional type of command (based on its keys)
     *
     * @return array
     */
    protected function getExpectedCommands(?string $type = null): array
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
            $commands = array_filter($commands, function (string $expectedType) use ($type) {
                return $expectedType === $type;
            });
        }

        return array_keys($commands);
    }
}
