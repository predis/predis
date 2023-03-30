<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2023 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Cluster;

use PHPUnit\Framework\MockObject\MockObject;
use PredisTestCase;

class PredisStrategyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportsKeyTags(): void
    {
        // NOTE: 32 and 64 bits PHP runtimes can produce different hash values.
        $expected = PHP_INT_SIZE == 4 ? -1954026732 : 2340940564;
        $strategy = $this->getClusterStrategy();

        $this->assertSame($expected, $strategy->getSlotByKey('{foo}'));
        $this->assertSame($expected, $strategy->getSlotByKey('{foo}:bar'));
        $this->assertSame($expected, $strategy->getSlotByKey('{foo}:baz'));
        $this->assertSame($expected, $strategy->getSlotByKey('bar:{foo}:baz'));
        $this->assertSame($expected, $strategy->getSlotByKey('bar:{foo}:{baz}'));

        $this->assertSame($expected, $strategy->getSlotByKey('bar:{foo}:baz{}'));
        $this->assertSame(PHP_INT_SIZE == 4 ? -1355751440 : 2939215856, $strategy->getSlotByKey('{}bar:{foo}:baz'));

        $this->assertSame(PHP_INT_SIZE == 4 ? -18873278 : 4276094018, $strategy->getSlotByKey(''));
        $this->assertSame(PHP_INT_SIZE == 4 ? -1574052038 : 2720915258, $strategy->getSlotByKey('{}'));
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands(): void
    {
        /** @var PredisStrategy */
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
        $arguments = ['key'];

        foreach ($this->getExpectedCommands('keys-first') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testAllKeysCommands(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['{key}:1', '{key}:2', '{key}:3', '{key}:4'];

        foreach ($this->getExpectedCommands('keys-all') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testInterleavedKeysCommands(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['{key}:1', 'value1', '{key}:2', 'value2'];

        foreach ($this->getExpectedCommands('keys-interleaved') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForSortCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['{key}:1', 'value1', '{key}:2', 'value2'];

        $commandID = 'SORT';

        $command = $commands->create($commandID, ['{key}:1']);
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, ['{key}:1', ['STORE' => '{key}:2']]);
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForBlockingListCommands(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['{key}:1', '{key}:2', 10];

        foreach ($this->getExpectedCommands('keys-blockinglist') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForZsetAggregationCommands(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['{key}:destination', ['{key}:1', '{key}:1'], [], 'sum'];

        foreach ($this->getExpectedCommands('keys-zaggregated') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
        }
    }

    /**
     * @group disconnected
     */
    public function testKeysForBitOpCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['AND', '{key}:destination', '{key}:src:1', '{key}:src:2'];

        foreach ($this->getExpectedCommands('keys-bitop') as $commandID) {
            $command = $commands->create($commandID, $arguments);
            $this->assertNotNull($strategy->getSlot($command), $commandID);
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

        $command = $commands->create($commandID, ['{key}:1', 10, 10, 1, 'km']);
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, ['{key}:1', 10, 10, 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3']);
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

        $command = $commands->create($commandID, ['{key}:1', 'member', 1, 'km']);
        $this->assertNotNull($strategy->getSlot($command), $commandID);

        $command = $commands->create($commandID, ['{key}:1', 'member', 1, 'km', 'store', '{key}:2', 'storedist', '{key}:3']);
        $this->assertNotNull($strategy->getSlot($command), $commandID);
    }

    /**
     * @group disconnected
     */
    public function testKeysForEvalCommand(): void
    {
        $strategy = $this->getClusterStrategy();
        $commands = $this->getCommandFactory();
        $arguments = ['%SCRIPT%', 2, '{key}:1', '{key}:2', 'value1', 'value2'];

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
        $arguments = ['{key}:1', '{key}:2', 'value1', 'value2'];

        /** @var \Predis\Command\CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(['getScript', 'getKeysCount'])
            ->getMock();
        $command
            ->expects($this->once())
            ->method('getScript')
            ->willReturn('return true');
        $command
            ->expects($this->exactly(2))
            ->method('getKeysCount')
            ->willReturn(2);
        $command->setArguments($arguments);

        $this->assertNotNull($strategy->getSlot($command), "Script Command [{$command->getId()}]");
    }

    /**
     * @group disconnected
     */
    public function testUnsettingCommandHandler(): void
    {
        /** @var PredisStrategy */
        $strategy = $this->getClusterStrategy();
        $strategy->setCommandHandler('set');
        $strategy->setCommandHandler('get', null);

        $commands = $this->getCommandFactory();

        $command = $commands->create('set', ['key', 'value']);
        $this->assertNull($strategy->getSlot($command));

        $command = $commands->create('get', ['key']);
        $this->assertNull($strategy->getSlot($command));
    }

    /**
     * @group disconnected
     */
    public function testSettingCustomCommandHandler(): void
    {
        $callable = $this->getMockBuilder('stdClass')
            ->addMethods(['__invoke'])
            ->getMock();
        $callable
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
            ->willReturn('key');

        /** @var PredisStrategy */
        $strategy = $this->getClusterStrategy();
        $strategy->setCommandHandler('get', $callable);

        $commands = $this->getCommandFactory();
        $command = $commands->create('get', ['key']);

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
    protected function getClusterStrategy(): StrategyInterface
    {
        $strategy = new PredisStrategy();

        $connection = $this->getMockBuilder('Predis\Connection\NodeConnectionInterface')->getMock();
        $strategy->getDistributor()->add($connection);

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
        $commands = [
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
            'SORT' => 'variable',
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

            /* RedisJSON */
            'JSON.ARRAPPEND' => 'keys-first',
            'JSON.ARRINDEX' => 'keys-first',
            'JSON.ARRINSERT' => 'keys-first',
            'JSON.ARRLEN' => 'keys-first',
            'JSON.ARRPOP' => 'keys-first',
            'JSON.ARRTRIM' => 'keys-first',
            'JSON.CLEAR' => 'keys-first',
            'JSON.DEBUG MEMORY' => 'keys-first',
            'JSON.DEL' => 'keys-first',
            'JSON.FORGET' => 'keys-first',
            'JSON.GET' => 'keys-first',
            'JSON.MGET' => 'keys-all',
            'JSON.NUMINCRBY' => 'keys-first',
            'JSON.OBJKEYS' => 'keys-first',
            'JSON.OBJLEN' => 'keys-first',
            'JSON.RESP' => 'keys-first',
            'JSON.SET' => 'keys-first',
            'JSON.STRAPPEND' => 'keys-first',
            'JSON.STRLEN' => 'keys-first',
            'JSON.TOGGLE' => 'keys-first',
            'JSON.TYPE' => 'keys-first',

            /* RedisBloom */
            'BF.ADD' => 'keys-first',
            'BF.EXISTS' => 'keys-first',
            'BF.INFO' => 'keys-first',
            'BF.INSERT' => 'keys-first',
            'BF.LOADCHUNK' => 'keys-first',
            'BF.MADD' => 'keys-first',
            'BF.MEXISTS' => 'keys-first',
            'BF.RESERVE' => 'keys-first',
            'BF.SCANDUMP' => 'keys-first',
            'CF.ADD' => 'keys-first',
            'CF.ADDNX' => 'keys-first',
            'CF.COUNT' => 'keys-first',
            'CF.DEL' => 'keys-first',
            'CF.EXISTS' => 'keys-first',
            'CF.INFO' => 'keys-first',
            'CF.INSERT' => 'keys-first',
            'CF.INSERTNX' => 'keys-first',
            'CF.LOADCHUNK' => 'keys-first',
            'CF.MEXISTS' => 'keys-first',
            'CF.RESERVE' => 'keys-first',
            'CF.SCANDUMP' => 'keys-first',
            'CMS.INCRBY' => 'keys-first',
            'CMS.INFO' => 'keys-first',
            'CMS.INITBYDIM' => 'keys-first',
            'CMS.INITBYPROB' => 'keys-first',
            'CMS.QUERY' => 'keys-first',
            'TDIGEST.ADD' => 'keys-first',
            'TDIGEST.BYRANK' => 'keys-first',
            'TDIGEST.BYREVRANK' => 'keys-first',
            'TDIGEST.CDF' => 'keys-first',
            'TDIGEST.CREATE' => 'keys-first',
            'TDIGEST.INFO' => 'keys-first',
            'TDIGEST.MAX' => 'keys-first',
            'TDIGEST.MIN' => 'keys-first',
            'TDIGEST.QUANTILE' => 'keys-first',
            'TDIGEST.RANK' => 'keys-first',
            'TDIGEST.RESET' => 'keys-first',
            'TDIGEST.REVRANK' => 'keys-first',
            'TDIGEST.TRIMMED_MEAN' => 'keys-first',
            'TOPK.ADD' => 'keys-first',
            'TOPK.INCRBY' => 'keys-first',
            'TOPK.INFO' => 'keys-first',
            'TOPK.LIST' => 'keys-first',
            'TOPK.QUERY' => 'keys-first',
            'TOPK.RESERVE' => 'keys-first',

            /* RediSearch */
            'FT.AGGREGATE' => 'keys-first',
            'FT.ALTER' => 'keys-first',
            'FT.CREATE' => 'keys-first',
            'FT.CURSOR DEL' => 'keys-first',
            'FT.CURSOR READ' => 'keys-first',
            'FT.DROPINDEX' => 'keys-first',
            'FT.EXPLAIN' => 'keys-first',
            'FT.INFO' => 'keys-first',
            'FT.PROFILE' => 'keys-first',
            'FT.SEARCH' => 'keys-first',
            'FT.SPELLCHECK' => 'keys-first',
            'FT.SYNDUMP' => 'keys-first',
            'FT.SYNUPDATE' => 'keys-first',
            'FT.TAGVALS' => 'keys-first',

            /* Redis TimeSeries */
            'TS.ADD' => 'keys-first',
            'TS.ALTER' => 'keys-first',
            'TS.CREATE' => 'keys-first',
            'TS.DECRBY' => 'keys-first',
            'TS.DEL' => 'keys-first',
            'TS.GET' => 'keys-first',
            'TS.INCRBY' => 'keys-first',
            'TS.INFO' => 'keys-first',
            'TS.MGET' => 'keys-first',
            'TS.MRANGE' => 'keys-first',
            'TS.MREVRANGE' => 'keys-first',
            'TS.QUERYINDEX' => 'keys-first',
            'TS.RANGE' => 'keys-first',
            'TS.REVRANGE' => 'keys-first',
        ];

        if (isset($type)) {
            $commands = array_filter($commands, function (string $expectedType) use ($type) {
                return $expectedType === $type;
            });
        }

        return array_keys($commands);
    }
}
