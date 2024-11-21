<?php

/*
 * This file is part of the Predis package.
 *
 * (c) 2009-2020 Daniele Alessandri
 * (c) 2021-2024 Till Krüss
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Command;

use Predis\Command\Processor\ProcessorChain;
use Predis\Command\Processor\ProcessorInterface;
use PredisTestCase;

class RedisFactoryTest extends PredisTestCase
{
    /**
     * @group disconnected
     */
    public function testSupportedCommands(): void
    {
        $factory = new RedisFactory();

        foreach ($this->getExpectedCommands() as $commandID) {
            $this->assertTrue($factory->supports($commandID), "Command factory does not support $commandID");
        }
    }

    /**
     * @group disconnected
     */
    public function testSupportCommand(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('info'));
        $this->assertTrue($factory->supports('INFO'));

        $this->assertFalse($factory->supports('unknown'));
        $this->assertFalse($factory->supports('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testSupportCommands(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('get', 'set'));
        $this->assertTrue($factory->supports('GET', 'SET'));

        $this->assertFalse($factory->supports('get', 'unknown'));

        $this->assertFalse($factory->supports('unknown1', 'unknown2'));
    }

    /**
     * @group disconnected
     */
    public function testGetCommandClass(): void
    {
        $factory = new RedisFactory();

        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('ping'));
        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('PING'));

        $this->assertNull($factory->getCommandClass('unknown'));
        $this->assertNull($factory->getCommandClass('UNKNOWN'));
    }

    /**
     * @group disconnected
     */
    public function testDefineCommand(): void
    {
        $factory = new RedisFactory();

        $command = $this->getMockBuilder('Predis\Command\CommandInterface')
            ->getMock();

        $factory->define('mock', get_class($command));

        $this->assertTrue($factory->supports('mock'));
        $this->assertTrue($factory->supports('MOCK'));

        $this->assertInstanceOf($factory->getCommandClass('mock'), $command);
    }

    /**
     * @group disconnected
     */
    public function testUndefineCommandInClassAutoload(): void
    {
        $factory = new RedisFactory();

        $this->assertTrue($factory->supports('PING'));
        $this->assertSame('Predis\Command\Redis\PING', $factory->getCommandClass('PING'));

        $factory->undefine('PING');

        $this->assertFalse($factory->supports('PING'));
        $this->assertNull($factory->getCommandClass('PING'));
    }

    /**
     * @group disconnected
     */
    public function testUndefineCommandInClassMap(): void
    {
        $factory = new RedisFactory();

        $commandClass = get_class($this->getMockBuilder('Predis\Command\CommandInterface')->getMock());
        $factory->define('MOCK', $commandClass);

        $this->assertTrue($factory->supports('MOCK'));
        $this->assertSame($commandClass, $factory->getCommandClass('MOCK'));

        $factory->undefine('MOCK');

        $this->assertFalse($factory->supports('MOCK'));
        $this->assertNull($factory->getCommandClass('MOCK'));
    }

    /**
     * @group disconnected
     */
    public function testDefineInvalidCommand(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Class stdClass must implement Predis\Command\CommandInterface");

        $factory = new RedisFactory();

        $factory->define('mock', 'stdClass');
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithoutArguments(): void
    {
        $factory = new RedisFactory();

        $command = $factory->create('info');

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('INFO', $command->getId());
        $this->assertEquals([], $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateCommandWithArguments(): void
    {
        $factory = new RedisFactory();

        $arguments = ['foo', 'bar'];
        $command = $factory->create('set', $arguments);

        $this->assertInstanceOf('Predis\Command\CommandInterface', $command);
        $this->assertEquals('SET', $command->getId());
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @group disconnected
     */
    public function testCreateUndefinedCommand(): void
    {
        $this->expectException('Predis\ClientException');
        $this->expectExceptionMessage('Command `UNKNOWN` is not a registered Redis command.');

        $factory = new RedisFactory();

        $factory->create('unknown');
    }

    /**
     * @group disconnected
     */
    public function testGetDefaultProcessor(): void
    {
        $factory = new RedisFactory();

        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetProcessor(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();

        $factory = new RedisFactory();
        $factory->setProcessor($processor);

        $this->assertSame($processor, $factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSetAndUnsetProcessor(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();

        $factory = new RedisFactory();

        $factory->setProcessor($processor);
        $this->assertSame($processor, $factory->getProcessor());

        $factory->setProcessor(null);
        $this->assertNull($factory->getProcessor());
    }

    /**
     * @group disconnected
     */
    public function testSingleProcessor(): void
    {
        // Could it be that objects passed to the return callback of a mocked
        // method are cloned instead of being passed by reference?
        $argsRef = null;

        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();
        $processor
            ->expects($this->once())
            ->method('process')
            ->with($this->isInstanceOf('Predis\Command\CommandInterface'))
            ->willReturnCallback(
                function (CommandInterface $cmd) use (&$argsRef) {
                    $cmd->setRawArguments($argsRef = array_map('strtoupper', $cmd->getArguments()));
                }
            );

        $factory = new RedisFactory();
        $factory->setProcessor($processor);
        $factory->create('set', ['foo', 'bar']);

        $this->assertSame(['FOO', 'BAR'], $argsRef);
    }

    /**
     * @group disconnected
     */
    public function testChainOfProcessors(): void
    {
        /** @var ProcessorInterface */
        $processor = $this
            ->getMockBuilder('Predis\Command\Processor\ProcessorInterface')
            ->getMock();
        $processor
            ->expects($this->exactly(2))
            ->method('process');

        $chain = new ProcessorChain();
        $chain->add($processor);
        $chain->add($processor);

        $factory = new RedisFactory();
        $factory->setProcessor($chain);

        $factory->create('info');
    }

    // ******************************************************************** //
    // ---- HELPER METHODS ------------------------------------------------ //
    // ******************************************************************** //

    /**
     * Returns the expected list of commands supported by the tested factory.
     *
     * @return array List of supported commands.
     */
    protected function getExpectedCommands(): array
    {
        return [
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
            159 => 'JSONSET',
            160 => 'JSONGET',
            161 => 'JSONARRAPPEND',
            162 => 'JSONARRINDEX',
            163 => 'JSONARRINSERT',
            164 => 'JSONARRLEN',
            165 => 'JSONARRPOP',
            166 => 'JSONARRTRIM',
            167 => 'JSONCLEAR',
            168 => 'JSONDEBUG',
            169 => 'JSONDEL',
            170 => 'JSONFORGET',
            171 => 'JSONMGET',
            172 => 'JSONNUMINCRBY',
            173 => 'JSONOBJKEYS',
            174 => 'JSONOBJLEN',
            175 => 'JSONRESP',
            176 => 'JSONSTRAPPEND',
            177 => 'JSONSTRLEN',
            178 => 'JSONTOGGLE',
            179 => 'JSONTYPE',
            180 => 'BFADD',
            181 => 'BFEXISTS',
            182 => 'BFINFO',
            183 => 'BFINSERT',
            184 => 'BFLOADCHUNK',
            185 => 'BFMADD',
            186 => 'BFMEXISTS',
            187 => 'BFRESERVE',
            188 => 'BFSCANDUMP',
            189 => 'CMSINCRBY',
            190 => 'CMSINFO',
            191 => 'CMSINITBYDIM',
            192 => 'CMSINITBYPROB',
            193 => 'CMSMERGE',
            194 => 'CMSQUERY',
            195 => 'CFADD',
            196 => 'CFADDNX',
            197 => 'CFCOUNT',
            198 => 'CFDEL',
            199 => 'CFEXISTS',
            200 => 'CFINFO',
            201 => 'CFINSERT',
            202 => 'CFINSERTNX',
            203 => 'CFLOADCHUNK',
            204 => 'CFMEXISTS',
            205 => 'CFRESERVE',
            206 => 'CFSCANDUMP',
            207 => 'TDIGESTADD',
            208 => 'TDIGESTBYRANK',
            209 => 'TDIGESTBYREVRANK',
            210 => 'TDIGESTCDF',
            211 => 'TDIGESTCREATE',
            212 => 'TDIGESTINFO',
            213 => 'TDIGESTMAX',
            214 => 'TDIGESTMERGE',
            215 => 'TDIGESTMIN',
            216 => 'TDIGESTQUANTILE',
            217 => 'TDIGESTRANK',
            218 => 'TDIGESTRESET',
            219 => 'TDIGESTREVRANK',
            220 => 'TDIGESTTRIMMED_MEAN',
            221 => 'TOPKADD',
            222 => 'TOPKINCRBY',
            223 => 'TOPKINFO',
            224 => 'TOPKLIST',
            225 => 'TOPKQUERY',
            226 => 'TOPKRESERVE',
            227 => 'FTALIASADD',
            228 => 'FTALIASDEL',
            229 => 'FTALIASUPDATE',
            230 => 'FTALTER',
            231 => 'FTCONFIG',
            232 => 'FTCREATE',
            233 => 'FTDICTADD',
            234 => 'FTDICTDEL',
            235 => 'FTDICTDUMP',
            236 => 'FTDROPINDEX',
            237 => 'FTINFO',
            238 => 'FTPROFILE',
            239 => 'FTSEARCH',
            240 => 'FTSPELLCHECK',
            241 => 'FTSUGADD',
            242 => 'FTSUGDEL',
            243 => 'FTSUGGET',
            244 => 'FTSUGLEN',
            245 => 'FTSYNDUMP',
            246 => 'FTSYNUPDATE',
            247 => 'FTTAGVALS',
        ];
    }
}
