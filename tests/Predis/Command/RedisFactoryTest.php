<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\CommandInterface;
use Predis\Command\Processor\ProcessorChain;
use PredisTestCase;

/**
 *
 */
class RedisFactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testGetVersion()
    {
        $factory = new RedisFactory();

        $this->assertSame('3.2', $factory->getVersion());
    }

    /**
     * @group disconnected
     */
    public function testSupportedCommands()
    {
        $factory = new RedisFactory();

        $expected = $this->getExpectedCommands();
        $commands = $this->getCommands($factory);

        $this->assertSame($expected, $commands);
    }

    /**
     * @group disconnected
     */
    public function testSupportCommand()
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supportsCommand('info'));
        $this->assertTrue($factory->supportsCommand('INFO'));

        $this->assertFalse($factory->supportsCommand('unknown'));
        $this->assertFalse($factory->supportsCommand('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testSupportCommands()
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supportsCommands(array('get', 'set')));
        $this->assertTrue($factory->supportsCommands(array('GET', 'SET')));

        $this->assertFalse($factory->supportsCommands(array('get', 'unknown')));

        $this->assertFalse($factory->supportsCommands(array('unknown1', 'unknown2')));
    }

    /**
     * @group disconnected
     */
    public function testGetCommandClass()
    {
        $factory = new RedisFactory();

        $this->assertSame('Predis\Command\Redis\ConnectionPing', $factory->getCommandClass('ping'));
        $this->assertSame('Predis\Command\Redis\ConnectionPing', $factory->getCommandClass('PING'));

        $this->assertNull($factory->getCommandClass('unknown'));
        $this->assertNull($factory->getCommandClass('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testDefineCommand()
    {
        $factory = new RedisFactory();
        $command = $this->getMock('Predis\Command\CommandInterface');

        $factory->defineCommand('mock', get_class($command));

        $this->assertTrue($factory->supportsCommand('mock'));
        $this->assertTrue($factory->supportsCommand('MOCK'));

        $this->assertSame(get_class($command), $factory->getCommandClass('mock'));
    }

    /**
     * @group disconnected
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The class 'stdClass' is not a valid command class.
     */
    public function testDefineInvalidCommand()
    {
        $factory = new RedisFactory();

        $factory->defineCommand('mock', 'stdClass');
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments()
    {
        $factory = new RedisFactory();

        $command = $factory->createCommand('info');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('INFO', $command->getId());
        $this->assertEquals(array(), $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithArguments()
    {
        $factory = new RedisFactory();

        $arguments = array('foo', 'bar');
        $command = $factory->createCommand('set', $arguments);

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     * @expectedException \Predis\ClientException
     * @expectedExceptionMessage Command 'UNKNOWN' is not a registered Redis command.
     */
    public function testCreateUndefinedCommand()
    {
        $factory = new RedisFactory();

        $factory->createCommand('unknown');
    }

    /**
     * @group disconnected
     */
    public function testGetDefaultProcessor()
    {
        $factory = new RedisFactory();

        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');

        $factory = new RedisFactory();
        $factory->setProcessor($processor);

        $this->assertSame($processor, $factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetAndUnsetProcessor()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $factory = new RedisFactory();

        $factory->setProcessor($processor);
        $this->assertSame($processor, $factory->getProcessor());

        $factory->setProcessor(null);
        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSingleProcessor()
    {
        // Could it be that objects passed to the return callback of a mocked
        // method are cloned instead of being passed by reference?
        $argsRef = null;

        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $processor->expects($this->once())
                  ->method('process')
                  ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
                  ->will($this->returnCallback(function (CommandInterface $cmd) use (&$argsRef) {
                        $cmd->setRawArguments($argsRef = array_map('strtoupper', $cmd->getArguments()));
                    }));

        $factory = new RedisFactory();
        $factory->setProcessor($processor);
        $factory->createCommand('set', array('foo', 'bar'));

        $this->assertSame(array('FOO', 'BAR'), $argsRef);
    }

    /**
     * @group disconnected
     */
    public function testChainOfProcessors()
    {
        $processor = $this->getMock('Predis\Command\Processor\ProcessorInterface');
        $processor->expects($this->exactly(2))
                  ->method('process');

        $chain = new ProcessorChain();
        $chain->add($processor);
        $chain->add($processor);

        $factory = new RedisFactory();
        $factory->setProcessor($chain);

        $factory->createCommand('info');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns the list of commands supported by the specified command factory.
     *
     * @param FactoryInterface $factory Command factory instance.
     *
     * @return array
     */
    protected function getCommands(FactoryInterface $factory)
    {
        $commands = $factory->getSupportedCommands();

        return array_keys($commands);
    }

    /**
     * Returns the expected list of commands supported by the tested factory.
     *
     * @return array List of supported commands.
     */
    protected function getExpectedCommands()
    {
        return array(
            0 => 'EXISTS',
            1 => 'DEL',
            2 => 'TYPE',
            3 => 'KEYS',
            4 => 'RANDOMKEY',
            5 => 'RENAME',
            6 => 'RENAMENX',
            7 => 'EXPIRE',
            8 => 'EXPIREAT',
            9 => 'TTL',
            10 => 'MOVE',
            11 => 'SORT',
            12 => 'DUMP',
            13 => 'RESTORE',
            14 => 'SET',
            15 => 'SETNX',
            16 => 'MSET',
            17 => 'MSETNX',
            18 => 'GET',
            19 => 'MGET',
            20 => 'GETSET',
            21 => 'INCR',
            22 => 'INCRBY',
            23 => 'DECR',
            24 => 'DECRBY',
            25 => 'RPUSH',
            26 => 'LPUSH',
            27 => 'LLEN',
            28 => 'LRANGE',
            29 => 'LTRIM',
            30 => 'LINDEX',
            31 => 'LSET',
            32 => 'LREM',
            33 => 'LPOP',
            34 => 'RPOP',
            35 => 'RPOPLPUSH',
            36 => 'SADD',
            37 => 'SREM',
            38 => 'SPOP',
            39 => 'SMOVE',
            40 => 'SCARD',
            41 => 'SISMEMBER',
            42 => 'SINTER',
            43 => 'SINTERSTORE',
            44 => 'SUNION',
            45 => 'SUNIONSTORE',
            46 => 'SDIFF',
            47 => 'SDIFFSTORE',
            48 => 'SMEMBERS',
            49 => 'SRANDMEMBER',
            50 => 'ZADD',
            51 => 'ZINCRBY',
            52 => 'ZREM',
            53 => 'ZRANGE',
            54 => 'ZREVRANGE',
            55 => 'ZRANGEBYSCORE',
            56 => 'ZCARD',
            57 => 'ZSCORE',
            58 => 'ZREMRANGEBYSCORE',
            59 => 'PING',
            60 => 'AUTH',
            61 => 'SELECT',
            62 => 'ECHO',
            63 => 'QUIT',
            64 => 'INFO',
            65 => 'SLAVEOF',
            66 => 'MONITOR',
            67 => 'DBSIZE',
            68 => 'FLUSHDB',
            69 => 'FLUSHALL',
            70 => 'SAVE',
            71 => 'BGSAVE',
            72 => 'LASTSAVE',
            73 => 'SHUTDOWN',
            74 => 'BGREWRITEAOF',
            75 => 'SETEX',
            76 => 'APPEND',
            77 => 'SUBSTR',
            78 => 'BLPOP',
            79 => 'BRPOP',
            80 => 'ZUNIONSTORE',
            81 => 'ZINTERSTORE',
            82 => 'ZCOUNT',
            83 => 'ZRANK',
            84 => 'ZREVRANK',
            85 => 'ZREMRANGEBYRANK',
            86 => 'HSET',
            87 => 'HSETNX',
            88 => 'HMSET',
            89 => 'HINCRBY',
            90 => 'HGET',
            91 => 'HMGET',
            92 => 'HDEL',
            93 => 'HEXISTS',
            94 => 'HLEN',
            95 => 'HKEYS',
            96 => 'HVALS',
            97 => 'HGETALL',
            98 => 'MULTI',
            99 => 'EXEC',
            100 => 'DISCARD',
            101 => 'SUBSCRIBE',
            102 => 'UNSUBSCRIBE',
            103 => 'PSUBSCRIBE',
            104 => 'PUNSUBSCRIBE',
            105 => 'PUBLISH',
            106 => 'CONFIG',
            107 => 'PERSIST',
            108 => 'STRLEN',
            109 => 'SETRANGE',
            110 => 'GETRANGE',
            111 => 'SETBIT',
            112 => 'GETBIT',
            113 => 'RPUSHX',
            114 => 'LPUSHX',
            115 => 'LINSERT',
            116 => 'BRPOPLPUSH',
            117 => 'ZREVRANGEBYSCORE',
            118 => 'WATCH',
            119 => 'UNWATCH',
            120 => 'OBJECT',
            121 => 'SLOWLOG',
            122 => 'CLIENT',
            123 => 'PTTL',
            124 => 'PEXPIRE',
            125 => 'PEXPIREAT',
            126 => 'MIGRATE',
            127 => 'PSETEX',
            128 => 'INCRBYFLOAT',
            129 => 'BITOP',
            130 => 'BITCOUNT',
            131 => 'HINCRBYFLOAT',
            132 => 'EVAL',
            133 => 'EVALSHA',
            134 => 'SCRIPT',
            135 => 'TIME',
            136 => 'SENTINEL',
            137 => 'SCAN',
            138 => 'BITPOS',
            139 => 'SSCAN',
            140 => 'ZSCAN',
            141 => 'ZLEXCOUNT',
            142 => 'ZRANGEBYLEX',
            143 => 'ZREMRANGEBYLEX',
            144 => 'ZREVRANGEBYLEX',
            145 => 'HSCAN',
            146 => 'PUBSUB',
            147 => 'PFADD',
            148 => 'PFCOUNT',
            149 => 'PFMERGE',
            150 => 'COMMAND',
            151 => 'HSTRLEN',
            152 => 'BITFIELD',
            153 => 'GEOADD',
            154 => 'GEOHASH',
            155 => 'GEOPOS',
            156 => 'GEODIST',
            157 => 'GEORADIUS',
            158 => 'GEORADIUSBYMEMBER',
        );
    }
}
