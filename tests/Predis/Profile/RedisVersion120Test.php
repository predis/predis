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
class RedisVersion120Test extends PredisProfileTestCase
{
    /**
     * {@inheritdoc}
     */
    public function getProfile($version = null)
    {
        return new RedisVersion120();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedVersion()
    {
        return '1.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedCommands()
    {
        return array(
            0   => 'EXISTS',
            1   => 'DEL',
            2   => 'TYPE',
            3   => 'KEYS',
            4   => 'RANDOMKEY',
            5   => 'RENAME',
            6   => 'RENAMENX',
            7   => 'EXPIRE',
            8   => 'EXPIREAT',
            9   => 'TTL',
            10  => 'MOVE',
            11  => 'SORT',
            12  => 'SET',
            13  => 'SETNX',
            14  => 'MSET',
            15  => 'MSETNX',
            16  => 'GET',
            17  => 'MGET',
            18  => 'GETSET',
            19  => 'INCR',
            20  => 'INCRBY',
            21  => 'DECR',
            22  => 'DECRBY',
            23  => 'RPUSH',
            24  => 'LPUSH',
            25  => 'LLEN',
            26  => 'LRANGE',
            27  => 'LTRIM',
            28  => 'LINDEX',
            29  => 'LSET',
            30  => 'LREM',
            31  => 'LPOP',
            32  => 'RPOP',
            33  => 'RPOPLPUSH',
            34  => 'SADD',
            35  => 'SREM',
            36  => 'SPOP',
            37  => 'SMOVE',
            38  => 'SCARD',
            39  => 'SISMEMBER',
            40  => 'SINTER',
            41  => 'SINTERSTORE',
            42  => 'SUNION',
            43  => 'SUNIONSTORE',
            44  => 'SDIFF',
            45  => 'SDIFFSTORE',
            46  => 'SMEMBERS',
            47  => 'SRANDMEMBER',
            48  => 'ZADD',
            49  => 'ZINCRBY',
            50  => 'ZREM',
            51  => 'ZRANGE',
            52  => 'ZREVRANGE',
            53  => 'ZRANGEBYSCORE',
            54  => 'ZCARD',
            55  => 'ZSCORE',
            56  => 'ZREMRANGEBYSCORE',
            57  => 'PING',
            58  => 'AUTH',
            59  => 'SELECT',
            60  => 'ECHO',
            61  => 'QUIT',
            62  => 'INFO',
            63  => 'SLAVEOF',
            64  => 'MONITOR',
            65  => 'DBSIZE',
            66  => 'FLUSHDB',
            67  => 'FLUSHALL',
            68  => 'SAVE',
            69  => 'BGSAVE',
            70  => 'LASTSAVE',
            71  => 'SHUTDOWN',
            72  => 'BGREWRITEAOF',
        );
    }
}
