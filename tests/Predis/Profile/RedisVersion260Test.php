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
class RedisVersion260Test extends PredisProfileTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getProfile($version = null)
    {
        return new RedisVersion260();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedVersion()
    {
        return '2.6';
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
            57 => 'ZDIFF',
            58 => 'ZSCORE',
            59 => 'ZREMRANGEBYSCORE',
            60 => 'PING',
            61 => 'AUTH',
            62 => 'SELECT',
            63 => 'ECHO',
            64 => 'QUIT',
            65 => 'INFO',
            66 => 'SLAVEOF',
            67 => 'MONITOR',
            68 => 'DBSIZE',
            69 => 'FLUSHDB',
            70 => 'FLUSHALL',
            71 => 'SAVE',
            72 => 'BGSAVE',
            73 => 'LASTSAVE',
            74 => 'SHUTDOWN',
            75 => 'BGREWRITEAOF',
            76 => 'SETEX',
            77 => 'APPEND',
            78 => 'SUBSTR',
            79 => 'BLPOP',
            80 => 'BRPOP',
            81 => 'ZUNIONSTORE',
            82 => 'ZINTERSTORE',
            83 => 'ZCOUNT',
            84 => 'ZRANK',
            85 => 'ZREVRANK',
            86 => 'ZREMRANGEBYRANK',
            87 => 'HSET',
            88 => 'HSETNX',
            89 => 'HMSET',
            90 => 'HINCRBY',
            91 => 'HGET',
            92 => 'HMGET',
            93 => 'HDEL',
            94 => 'HEXISTS',
            95 => 'HLEN',
            96 => 'HKEYS',
            97 => 'HVALS',
            98 => 'HGETALL',
            99 => 'MULTI',
            100 => 'EXEC',
            101 => 'DISCARD',
            102 => 'SUBSCRIBE',
            103 => 'UNSUBSCRIBE',
            104 => 'PSUBSCRIBE',
            105 => 'PUNSUBSCRIBE',
            106 => 'PUBLISH',
            107 => 'CONFIG',
            108 => 'PERSIST',
            109 => 'STRLEN',
            110 => 'SETRANGE',
            111 => 'GETRANGE',
            112 => 'SETBIT',
            113 => 'GETBIT',
            114 => 'RPUSHX',
            115 => 'LPUSHX',
            116 => 'LINSERT',
            117 => 'BRPOPLPUSH',
            118 => 'ZREVRANGEBYSCORE',
            119 => 'WATCH',
            120 => 'UNWATCH',
            121 => 'OBJECT',
            122 => 'SLOWLOG',
            123 => 'CLIENT',
            124 => 'PTTL',
            125 => 'PEXPIRE',
            126 => 'PEXPIREAT',
            127 => 'MIGRATE',
            128 => 'PSETEX',
            129 => 'INCRBYFLOAT',
            130 => 'BITOP',
            131 => 'BITCOUNT',
            132 => 'HINCRBYFLOAT',
            133 => 'EVAL',
            134 => 'EVALSHA',
            135 => 'SCRIPT',
            136 => 'TIME',
            137 => 'SENTINEL',
        );
    }
}
