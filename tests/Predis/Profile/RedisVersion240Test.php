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
class RedisVersion240Test extends PredisProfileTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getProfile($version = null)
    {
        return new RedisVersion240();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedVersion()
    {
        return '2.4';
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
            12 => 'SET',
            13 => 'SETNX',
            14 => 'MSET',
            15 => 'MSETNX',
            16 => 'GET',
            17 => 'MGET',
            18 => 'GETSET',
            19 => 'INCR',
            20 => 'INCRBY',
            21 => 'DECR',
            22 => 'DECRBY',
            23 => 'RPUSH',
            24 => 'LPUSH',
            25 => 'LLEN',
            26 => 'LRANGE',
            27 => 'LTRIM',
            28 => 'LINDEX',
            29 => 'LSET',
            30 => 'LREM',
            31 => 'LPOP',
            32 => 'RPOP',
            33 => 'RPOPLPUSH',
            34 => 'SADD',
            35 => 'SREM',
            36 => 'SPOP',
            37 => 'SMOVE',
            38 => 'SCARD',
            39 => 'SISMEMBER',
            40 => 'SINTER',
            41 => 'SINTERSTORE',
            42 => 'SUNION',
            43 => 'SUNIONSTORE',
            44 => 'SDIFF',
            45 => 'SDIFFSTORE',
            46 => 'SMEMBERS',
            47 => 'SRANDMEMBER',
            48 => 'ZADD',
            49 => 'ZINCRBY',
            50 => 'ZREM',
            51 => 'ZRANGE',
            52 => 'ZREVRANGE',
            53 => 'ZRANGEBYSCORE',
            54 => 'ZCARD',
            55 => 'ZDIFF',
            56 => 'ZSCORE',
            57 => 'ZREMRANGEBYSCORE',
            58 => 'PING',
            59 => 'AUTH',
            60 => 'SELECT',
            61 => 'ECHO',
            62 => 'QUIT',
            63 => 'INFO',
            64 => 'SLAVEOF',
            65 => 'MONITOR',
            66 => 'DBSIZE',
            67 => 'FLUSHDB',
            68 => 'FLUSHALL',
            69 => 'SAVE',
            70 => 'BGSAVE',
            71 => 'LASTSAVE',
            72 => 'SHUTDOWN',
            73 => 'BGREWRITEAOF',
            74 => 'SETEX',
            75 => 'APPEND',
            76 => 'SUBSTR',
            77 => 'BLPOP',
            78 => 'BRPOP',
            79 => 'ZUNIONSTORE',
            80 => 'ZINTERSTORE',
            81 => 'ZCOUNT',
            82 => 'ZRANK',
            83 => 'ZREVRANK',
            84 => 'ZREMRANGEBYRANK',
            85 => 'HSET',
            86 => 'HSETNX',
            87 => 'HMSET',
            88 => 'HINCRBY',
            89 => 'HGET',
            90 => 'HMGET',
            91 => 'HDEL',
            92 => 'HEXISTS',
            93 => 'HLEN',
            94 => 'HKEYS',
            95 => 'HVALS',
            96 => 'HGETALL',
            97 => 'MULTI',
            98 => 'EXEC',
            99 => 'DISCARD',
            100 => 'SUBSCRIBE',
            101 => 'UNSUBSCRIBE',
            102 => 'PSUBSCRIBE',
            103 => 'PUNSUBSCRIBE',
            104 => 'PUBLISH',
            105 => 'CONFIG',
            106 => 'PERSIST',
            107 => 'STRLEN',
            108 => 'SETRANGE',
            109 => 'GETRANGE',
            110 => 'SETBIT',
            111 => 'GETBIT',
            112 => 'RPUSHX',
            113 => 'LPUSHX',
            114 => 'LINSERT',
            115 => 'BRPOPLPUSH',
            116 => 'ZREVRANGEBYSCORE',
            117 => 'WATCH',
            118 => 'UNWATCH',
            119 => 'OBJECT',
            120 => 'SLOWLOG',
            121 => 'CLIENT',
        );
    }
}
