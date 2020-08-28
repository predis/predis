<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Replication;

use PredisTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Predis\Command\CommandInterface;

/**
 *
 */
class ReplicationStrategyTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testReadCommands(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        foreach ($this->getExpectedCommands('read') as $commandId) {
            $command = $commands->create($commandId);

            $this->assertTrue(
                $strategy->isReadOperation($command),
                "$commandId is expected to be a read operation."
            );
        }
    }

    /**
     * @group disconnected
     */
    public function testWriteRequests(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        foreach ($this->getExpectedCommands('write') as $commandId) {
            $command = $commands->create($commandId);

            $this->assertFalse(
                $strategy->isReadOperation($command),
                "$commandId is expected to be a write operation."
            );
        }
    }

    /**
     * @group disconnected
     */
    public function testDisallowedCommands(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        foreach ($this->getExpectedCommands('disallowed') as $commandId) {
            $command = $commands->create($commandId);

            $this->assertTrue(
                $strategy->isDisallowedOperation($command),
                "$commandId is expected to be a disallowed operation."
            );
        }
    }

    /**
     * @group disconnected
     */
    public function testSortCommand(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $cmdReturnSort = $commands->create('SORT', array('key:list'));
        $this->assertFalse(
            $strategy->isReadOperation($cmdReturnSort),
            'SORT is expected to be a write operation.'
        );

        $cmdStoreSort = $commands->create('SORT', array('key:list', array('store' => 'key:stored')));
        $this->assertFalse(
            $strategy->isReadOperation($cmdStoreSort),
            'SORT with STORE is expected to be a write operation.'
        );
    }

    /**
     * @group disconnected
     */
    public function testBitFieldCommand(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $command = $commands->create('BITFIELD', array('key'));
        $this->assertTrue(
            $strategy->isReadOperation($command),
            'BITFIELD with no modifiers is expected to be a read operation.'
        );

        $command = $commands->create('BITFIELD', array('key', 'GET', 'u4', '0'));
        $this->assertTrue(
            $strategy->isReadOperation($command),
            'BITFIELD with GET only is expected to be a read operation.'
        );

        $command = $commands->create('BITFIELD', array('key', 'SET', 'u4', '0', 1));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'BITFIELD with SET is expected to be a write operation.'
        );

        $command = $commands->create('BITFIELD', array('key', 'INCRBY', 'u4', '0', 1));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'BITFIELD with INCRBY is expected to be a write operation.'
        );

        $command = $commands->create('BITFIELD', array('key', 'GET', 'u4', '0', 'INCRBY', 'u4', '0', 1));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'BITFIELD with GET and INCRBY is expected to be a write operation.'
        );

        $command = $commands->create('BITFIELD', array('key', 'GET', 'u4', '0', 'SET', 'u4', '0', 1));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'BITFIELD with GET and SET is expected to be a write operation.'
        );
    }

    /**
     * @group disconnected
     */
    public function testGeoradiusCommand(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $command = $commands->create('GEORADIUS', array('key:geo', 15, 37, 200, 'km'));
        $this->assertTrue(
            $strategy->isReadOperation($command),
            'GEORADIUS is expected to be a read operation.'
        );

        $command = $commands->create('GEORADIUS', array('key:geo', 15, 37, 200, 'km', 'store', 'key:store'));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'GEORADIUS with STORE is expected to be a write operation.'
        );

        $command = $commands->create('GEORADIUS', array('key:geo', 15, 37, 200, 'km', 'storedist', 'key:storedist'));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'GEORADIUS with STOREDIST is expected to be a write operation.'
        );
    }

    /**
     * @group disconnected
     */
    public function testGeoradiusByMemberCommand(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $command = $commands->create('GEORADIUSBYMEMBER', array('key:geo', 15, 37, 200, 'km'));
        $this->assertTrue(
            $strategy->isReadOperation($command),
            'GEORADIUSBYMEMBER is expected to be a read operation.'
        );

        $command = $commands->create('GEORADIUSBYMEMBER', array('key:geo', 15, 37, 200, 'km', 'store', 'key:store'));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'GEORADIUSBYMEMBER with STORE is expected to be a write operation.'
        );

        $command = $commands->create('GEORADIUSBYMEMBER', array('key:geo', 15, 37, 200, 'km', 'storedist', 'key:storedist'));
        $this->assertFalse(
            $strategy->isReadOperation($command),
            'GEORADIUSBYMEMBER with STOREDIST is expected to be a write operation.'
        );
    }

    /**
     * @group disconnected
     */
    public function testUsingDisallowedCommandThrowsException(): void
    {
        $this->expectException('Predis\NotSupportedException');
        $this->expectExceptionMessage("The command 'INFO' is not allowed in replication mode");

        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $command = $commands->create('INFO');
        $strategy->isReadOperation($command);
    }

    /**
     * @group disconnected
     */
    public function testDefaultIsWriteOperation(): void
    {
        $strategy = new ReplicationStrategy();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->any())
            ->method('getId')
            ->willReturn('CMDTEST');

        $this->assertFalse($strategy->isReadOperation($command));
    }

    /**
     * @group disconnected
     */
    public function testCanSetCommandAsReadOperation(): void
    {
        $strategy = new ReplicationStrategy();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->any())
            ->method('getId')
            ->willReturn('CMDTEST');

        $strategy->setCommandReadOnly('CMDTEST', true);
        $this->assertTrue($strategy->isReadOperation($command));
    }

    /**
     * @group disconnected
     */
    public function testCanSetCommandAsWriteOperation(): void
    {
        $strategy = new ReplicationStrategy();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\CommandInterface')->getMock();
        $command
            ->expects($this->any())
            ->method('getId')
            ->willReturn('CMDTEST');

        $strategy->setCommandReadOnly('CMDTEST', false);
        $this->assertFalse($strategy->isReadOperation($command));

        $strategy->setCommandReadOnly('GET', false);
        $this->assertFalse($strategy->isReadOperation($command));
    }

    /**
     * @group disconnected
     */
    public function testCanUseCallableToCheckCommand(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $strategy->setCommandReadOnly('SET', function (CommandInterface $command) {
            return $command->getArgument(1) === true;
        });

        $command = $commands->create('SET', array('trigger', false));
        $this->assertFalse($strategy->isReadOperation($command));

        $command = $commands->create('SET', array('trigger', true));
        $this->assertTrue($strategy->isReadOperation($command));
    }

    /**
     * @group disconnected
     */
    public function testSetLuaScriptAsReadOperation(): void
    {
        $commands = $this->getCommandFactory();
        $strategy = new ReplicationStrategy();

        $writeScript = 'redis.call("set", "foo", "bar")';
        $readScript = 'return true';

        $strategy->setScriptReadOnly($readScript, true);

        $cmdEval = $commands->create('EVAL', array($writeScript));
        $cmdEvalSHA = $commands->create('EVALSHA', array(sha1($writeScript)));
        $this->assertFalse($strategy->isReadOperation($cmdEval));
        $this->assertFalse($strategy->isReadOperation($cmdEvalSHA));

        $cmdEval = $commands->create('EVAL', array($readScript));
        $cmdEvalSHA = $commands->create('EVALSHA', array(sha1($readScript)));
        $this->assertTrue($strategy->isReadOperation($cmdEval));
        $this->assertTrue($strategy->isReadOperation($cmdEvalSHA));
    }

    /**
     * @group disconnected
     */
    public function testSetLuaScriptAsReadOperationWorksWithScriptCommand(): void
    {
        $strategy = new ReplicationStrategy();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript'))
            ->getMock();
        $command
            ->expects($this->any())
            ->method('getScript')
            ->willReturn($script = 'return true');

        $strategy->setScriptReadOnly($script, function (CommandInterface $command) {
            return $command->getArgument(2) === true;
        });

        $command->setArguments(array(false));
        $this->assertFalse($strategy->isReadOperation($command));

        $command->setArguments(array(true));
        $this->assertTrue($strategy->isReadOperation($command));
    }

    /**
     * @group disconnected
     */
    public function testSetLuaScriptAsReadOperationWorksWithScriptCommandAndCallableCheck(): void
    {
        $strategy = new ReplicationStrategy();

        /** @var CommandInterface|MockObject */
        $command = $this->getMockBuilder('Predis\Command\ScriptCommand')
            ->onlyMethods(array('getScript'))
            ->getMock(array('getScript'));
        $command
            ->expects($this->any())
            ->method('getScript')
            ->willReturn($script = 'return true');

        $command->setArguments(array('trigger', false));

        $strategy->setScriptReadOnly($script, true);

        $this->assertTrue($strategy->isReadOperation($command));
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

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
            /* commands operating on the connection */
            'AUTH' => 'read',
            'SELECT' => 'read',
            'ECHO' => 'read',
            'QUIT' => 'read',
            'OBJECT' => 'read',
            'TIME' => 'read',
            'SHUTDOWN' => 'disallowed',
            'INFO' => 'disallowed',
            'DBSIZE' => 'disallowed',
            'LASTSAVE' => 'disallowed',
            'CONFIG' => 'disallowed',
            'MONITOR' => 'disallowed',
            'SLAVEOF' => 'disallowed',
            'SAVE' => 'disallowed',
            'BGSAVE' => 'disallowed',
            'BGREWRITEAOF' => 'disallowed',
            'SLOWLOG' => 'disallowed',

            /* commands operating on the key space */
            'EXISTS' => 'read',
            'DEL' => 'write',
            'TYPE' => 'read',
            'EXPIRE' => 'write',
            'EXPIREAT' => 'write',
            'PERSIST' => 'write',
            'PEXPIRE' => 'write',
            'PEXPIREAT' => 'write',
            'TTL' => 'read',
            'PTTL' => 'write',
            'SORT' => 'variable',
            'KEYS' => 'read',
            'SCAN' => 'read',
            'RANDOMKEY' => 'read',

            /* commands operating on string values */
            'APPEND' => 'write',
            'DECR' => 'write',
            'DECRBY' => 'write',
            'GET' => 'read',
            'GETBIT' => 'read',
            'BITCOUNT' => 'read',
            'BITPOS' => 'read',
            'BITOP' => 'write',
            'MGET' => 'read',
            'SET' => 'write',
            'GETRANGE' => 'read',
            'GETSET' => 'write',
            'INCR' => 'write',
            'INCRBY' => 'write',
            'INCRBYFLOAT' => 'write',
            'SETBIT' => 'write',
            'SETEX' => 'write',
            'MSET' => 'write',
            'MSETNX' => 'write',
            'SETNX' => 'write',
            'SETRANGE' => 'write',
            'STRLEN' => 'read',
            'SUBSTR' => 'read',
            'BITFIELD' => 'variable',

            /* commands operating on lists */
            'LINSERT' => 'write',
            'LINDEX' => 'read',
            'LLEN' => 'read',
            'LPOP' => 'write',
            'RPOP' => 'write',
            'BLPOP' => 'write',
            'BRPOP' => 'write',
            'LPUSH' => 'write',
            'LPUSHX' => 'write',
            'RPUSH' => 'write',
            'RPUSHX' => 'write',
            'LRANGE' => 'read',
            'LREM' => 'write',
            'LSET' => 'write',
            'LTRIM' => 'write',

            /* commands operating on sets */
            'SADD' => 'write',
            'SCARD' => 'read',
            'SISMEMBER' => 'read',
            'SMEMBERS' => 'read',
            'SSCAN' => 'read',
            'SRANDMEMBER' => 'read',
            'SPOP' => 'write',
            'SREM' => 'write',
            'SINTER' => 'read',
            'SUNION' => 'read',
            'SDIFF' => 'read',

            /* commands operating on sorted sets */
            'ZADD' => 'write',
            'ZCARD' => 'read',
            'ZCOUNT' => 'read',
            'ZINCRBY' => 'write',
            'ZRANGE' => 'read',
            'ZRANGEBYSCORE' => 'read',
            'ZRANK' => 'read',
            'ZREM' => 'write',
            'ZREMRANGEBYRANK' => 'write',
            'ZREMRANGEBYSCORE' => 'write',
            'ZREVRANGE' => 'read',
            'ZREVRANGEBYSCORE' => 'read',
            'ZREVRANK' => 'read',
            'ZSCORE' => 'read',
            'ZSCAN' => 'read',
            'ZLEXCOUNT' => 'read',
            'ZRANGEBYLEX' => 'read',
            'ZREMRANGEBYLEX' => 'write',
            'ZREVRANGEBYLEX' => 'read',

            /* commands operating on hashes */
            'HDEL' => 'write',
            'HEXISTS' => 'read',
            'HGET' => 'read',
            'HGETALL' => 'read',
            'HMGET' => 'read',
            'HINCRBY' => 'write',
            'HINCRBYFLOAT' => 'write',
            'HKEYS' => 'read',
            'HLEN' => 'read',
            'HSET' => 'write',
            'HSETNX' => 'write',
            'HVALS' => 'read',
            'HSCAN' => 'read',
            'HSTRLEN' => 'read',

            /* commands operating on HyperLogLog */
            'PFADD' => 'write',
            'PFMERGE' => 'write',
            'PFCOUNT' => 'read',

            /* scripting */
            'EVAL' => 'write',
            'EVALSHA' => 'write',

            /* commands performing geospatial operations */
            'GEOADD' => 'write',
            'GEOHASH' => 'read',
            'GEOPOS' => 'read',
            'GEODIST' => 'read',
            'GEORADIUS' => 'variable',
            'GEORADIUSBYMEMBER' => 'variable',
        );

        if (isset($type)) {
            $commands = array_filter($commands, function (string $expectedType) use ($type) {
                return $expectedType === $type;
            });
        }

        return array_keys($commands);
    }
}
