<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profile;

/**
 *
 */
class RedisVersion280Test extends PredisProfileTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getProfile($version = null)
    {
        return new RedisVersion280();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedVersion()
    {
        return '2.8';
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedCommands()
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
        );
    }
}
