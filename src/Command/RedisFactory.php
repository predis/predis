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

/**
 * Command factory for the mainline Redis server.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisFactory extends Factory
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '3.2';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\Redis\EXISTS',
            'DEL' => 'Predis\Command\Redis\DEL',
            'TYPE' => 'Predis\Command\Redis\TYPE',
            'KEYS' => 'Predis\Command\Redis\KEYS',
            'RANDOMKEY' => 'Predis\Command\Redis\RANDOMKEY',
            'RENAME' => 'Predis\Command\Redis\RENAME',
            'RENAMENX' => 'Predis\Command\Redis\RENAMENX',
            'EXPIRE' => 'Predis\Command\Redis\EXPIRE',
            'EXPIREAT' => 'Predis\Command\Redis\EXPIREAT',
            'TTL' => 'Predis\Command\Redis\TTL',
            'MOVE' => 'Predis\Command\Redis\MOVE',
            'SORT' => 'Predis\Command\Redis\SORT',
            'DUMP' => 'Predis\Command\Redis\DUMP',
            'RESTORE' => 'Predis\Command\Redis\RESTORE',

            /* commands operating on string values */
            'SET' => 'Predis\Command\Redis\SET',
            'SETNX' => 'Predis\Command\Redis\SETNX',
            'MSET' => 'Predis\Command\Redis\MSET',
            'MSETNX' => 'Predis\Command\Redis\MSETNX',
            'GET' => 'Predis\Command\Redis\GET',
            'MGET' => 'Predis\Command\Redis\MGET',
            'GETSET' => 'Predis\Command\Redis\GETSET',
            'INCR' => 'Predis\Command\Redis\INCR',
            'INCRBY' => 'Predis\Command\Redis\INCRBY',
            'DECR' => 'Predis\Command\Redis\DECR',
            'DECRBY' => 'Predis\Command\Redis\DECRBY',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\Redis\RPUSH',
            'LPUSH' => 'Predis\Command\Redis\LPUSH',
            'LLEN' => 'Predis\Command\Redis\LLEN',
            'LRANGE' => 'Predis\Command\Redis\LRANGE',
            'LTRIM' => 'Predis\Command\Redis\LTRIM',
            'LINDEX' => 'Predis\Command\Redis\LINDEX',
            'LSET' => 'Predis\Command\Redis\LSET',
            'LREM' => 'Predis\Command\Redis\LREM',
            'LPOP' => 'Predis\Command\Redis\LPOP',
            'RPOP' => 'Predis\Command\Redis\RPOP',
            'RPOPLPUSH' => 'Predis\Command\Redis\RPOPLPUSH',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\Redis\SADD',
            'SREM' => 'Predis\Command\Redis\SREM',
            'SPOP' => 'Predis\Command\Redis\SPOP',
            'SMOVE' => 'Predis\Command\Redis\SMOVE',
            'SCARD' => 'Predis\Command\Redis\SCARD',
            'SISMEMBER' => 'Predis\Command\Redis\SISMEMBER',
            'SINTER' => 'Predis\Command\Redis\SINTER',
            'SINTERSTORE' => 'Predis\Command\Redis\SINTERSTORE',
            'SUNION' => 'Predis\Command\Redis\SUNION',
            'SUNIONSTORE' => 'Predis\Command\Redis\SUNIONSTORE',
            'SDIFF' => 'Predis\Command\Redis\SDIFF',
            'SDIFFSTORE' => 'Predis\Command\Redis\SDIFFSTORE',
            'SMEMBERS' => 'Predis\Command\Redis\SMEMBERS',
            'SRANDMEMBER' => 'Predis\Command\Redis\SRANDMEMBER',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\Redis\ZADD',
            'ZINCRBY' => 'Predis\Command\Redis\ZINCRBY',
            'ZREM' => 'Predis\Command\Redis\ZREM',
            'ZRANGE' => 'Predis\Command\Redis\ZRANGE',
            'ZREVRANGE' => 'Predis\Command\Redis\ZREVRANGE',
            'ZRANGEBYSCORE' => 'Predis\Command\Redis\ZRANGEBYSCORE',
            'ZCARD' => 'Predis\Command\Redis\ZCARD',
            'ZSCORE' => 'Predis\Command\Redis\ZSCORE',
            'ZREMRANGEBYSCORE' => 'Predis\Command\Redis\ZREMRANGEBYSCORE',

            /* connection related commands */
            'PING' => 'Predis\Command\Redis\PING',
            'AUTH' => 'Predis\Command\Redis\AUTH',
            'SELECT' => 'Predis\Command\Redis\SELECT',
            'ECHO' => 'Predis\Command\Redis\ECHO_',
            'QUIT' => 'Predis\Command\Redis\QUIT',

            /* remote server control commands */
            'INFO' => 'Predis\Command\Redis\INFO',
            'SLAVEOF' => 'Predis\Command\Redis\SLAVEOF',
            'MONITOR' => 'Predis\Command\Redis\MONITOR',
            'DBSIZE' => 'Predis\Command\Redis\DBSIZE',
            'FLUSHDB' => 'Predis\Command\Redis\FLUSHDB',
            'FLUSHALL' => 'Predis\Command\Redis\FLUSHALL',
            'SAVE' => 'Predis\Command\Redis\SAVE',
            'BGSAVE' => 'Predis\Command\Redis\BGSAVE',
            'LASTSAVE' => 'Predis\Command\Redis\LASTSAVE',
            'SHUTDOWN' => 'Predis\Command\Redis\SHUTDOWN',
            'BGREWRITEAOF' => 'Predis\Command\Redis\BGREWRITEAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\Redis\SETEX',
            'APPEND' => 'Predis\Command\Redis\APPEND',
            'SUBSTR' => 'Predis\Command\Redis\SUBSTR',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\Redis\BLPOP',
            'BRPOP' => 'Predis\Command\Redis\BRPOP',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\Redis\ZUNIONSTORE',
            'ZINTERSTORE' => 'Predis\Command\Redis\ZINTERSTORE',
            'ZCOUNT' => 'Predis\Command\Redis\ZCOUNT',
            'ZRANK' => 'Predis\Command\Redis\ZRANK',
            'ZREVRANK' => 'Predis\Command\Redis\ZREVRANK',
            'ZREMRANGEBYRANK' => 'Predis\Command\Redis\ZREMRANGEBYRANK',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\Redis\HSET',
            'HSETNX' => 'Predis\Command\Redis\HSETNX',
            'HMSET' => 'Predis\Command\Redis\HMSET',
            'HINCRBY' => 'Predis\Command\Redis\HINCRBY',
            'HGET' => 'Predis\Command\Redis\HGET',
            'HMGET' => 'Predis\Command\Redis\HMGET',
            'HDEL' => 'Predis\Command\Redis\HDEL',
            'HEXISTS' => 'Predis\Command\Redis\HEXISTS',
            'HLEN' => 'Predis\Command\Redis\HLEN',
            'HKEYS' => 'Predis\Command\Redis\HKEYS',
            'HVALS' => 'Predis\Command\Redis\HVALS',
            'HGETALL' => 'Predis\Command\Redis\HGETALL',

            /* transactions */
            'MULTI' => 'Predis\Command\Redis\MULTI',
            'EXEC' => 'Predis\Command\Redis\EXEC',
            'DISCARD' => 'Predis\Command\Redis\DISCARD',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\Redis\SUBSCRIBE',
            'UNSUBSCRIBE' => 'Predis\Command\Redis\UNSUBSCRIBE',
            'PSUBSCRIBE' => 'Predis\Command\Redis\PSUBSCRIBE',
            'PUNSUBSCRIBE' => 'Predis\Command\Redis\PUNSUBSCRIBE',
            'PUBLISH' => 'Predis\Command\Redis\PUBLISH',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\Redis\CONFIG',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\Redis\PERSIST',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\Redis\STRLEN',
            'SETRANGE' => 'Predis\Command\Redis\SETRANGE',
            'GETRANGE' => 'Predis\Command\Redis\GETRANGE',
            'SETBIT' => 'Predis\Command\Redis\SETBIT',
            'GETBIT' => 'Predis\Command\Redis\GETBIT',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\Redis\RPUSHX',
            'LPUSHX' => 'Predis\Command\Redis\LPUSHX',
            'LINSERT' => 'Predis\Command\Redis\LINSERT',
            'BRPOPLPUSH' => 'Predis\Command\Redis\BRPOPLPUSH',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\Redis\ZREVRANGEBYSCORE',

            /* transactions */
            'WATCH' => 'Predis\Command\Redis\WATCH',
            'UNWATCH' => 'Predis\Command\Redis\UNWATCH',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\Redis\OBJECT',
            'SLOWLOG' => 'Predis\Command\Redis\SLOWLOG',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\Redis\CLIENT',

            /* ---------------- Redis 2.6 ---------------- */

            /* commands operating on the key space */
            'PTTL' => 'Predis\Command\Redis\PTTL',
            'PEXPIRE' => 'Predis\Command\Redis\PEXPIRE',
            'PEXPIREAT' => 'Predis\Command\Redis\PEXPIREAT',
            'MIGRATE' => 'Predis\Command\Redis\MIGRATE',

            /* commands operating on string values */
            'PSETEX' => 'Predis\Command\Redis\PSETEX',
            'INCRBYFLOAT' => 'Predis\Command\Redis\INCRBYFLOAT',
            'BITOP' => 'Predis\Command\Redis\BITOP',
            'BITCOUNT' => 'Predis\Command\Redis\BITCOUNT',

            /* commands operating on hashes */
            'HINCRBYFLOAT' => 'Predis\Command\Redis\HINCRBYFLOAT',

            /* scripting */
            'EVAL' => 'Predis\Command\Redis\EVAL_',
            'EVALSHA' => 'Predis\Command\Redis\EVALSHA',
            'SCRIPT' => 'Predis\Command\Redis\SCRIPT',

            /* remote server control commands */
            'TIME' => 'Predis\Command\Redis\TIME',
            'SENTINEL' => 'Predis\Command\Redis\SENTINEL',

            /* ---------------- Redis 2.8 ---------------- */

            /* commands operating on the key space */
            'SCAN' => 'Predis\Command\Redis\SCAN',

            /* commands operating on string values */
            'BITPOS' => 'Predis\Command\Redis\BITPOS',

            /* commands operating on sets */
            'SSCAN' => 'Predis\Command\Redis\SSCAN',

            /* commands operating on sorted sets */
            'ZSCAN' => 'Predis\Command\Redis\ZSCAN',
            'ZLEXCOUNT' => 'Predis\Command\Redis\ZLEXCOUNT',
            'ZRANGEBYLEX' => 'Predis\Command\Redis\ZRANGEBYLEX',
            'ZREMRANGEBYLEX' => 'Predis\Command\Redis\ZREMRANGEBYLEX',
            'ZREVRANGEBYLEX' => 'Predis\Command\Redis\ZREVRANGEBYLEX',

            /* commands operating on hashes */
            'HSCAN' => 'Predis\Command\Redis\HSCAN',

            /* publish - subscribe */
            'PUBSUB' => 'Predis\Command\Redis\PUBSUB',

            /* commands operating on HyperLogLog */
            'PFADD' => 'Predis\Command\Redis\PFADD',
            'PFCOUNT' => 'Predis\Command\Redis\PFCOUNT',
            'PFMERGE' => 'Predis\Command\Redis\PFMERGE',

            /* remote server control commands */
            'COMMAND' => 'Predis\Command\Redis\COMMAND',

            /* ---------------- Redis 3.2 ---------------- */

            /* commands operating on hashes */
            'HSTRLEN' => 'Predis\Command\Redis\HSTRLEN',
            'BITFIELD' => 'Predis\Command\Redis\BITFIELD',

            /* commands performing geospatial operations */
            'GEOADD' => 'Predis\Command\Redis\GEOADD',
            'GEOHASH' => 'Predis\Command\Redis\GEOHASH',
            'GEOPOS' => 'Predis\Command\Redis\GEOPOS',
            'GEODIST' => 'Predis\Command\Redis\GEODIST',
            'GEORADIUS' => 'Predis\Command\Redis\GEORADIUS',
            'GEORADIUSBYMEMBER' => 'Predis\Command\Redis\GEORADIUSBYMEMBER',
        );
    }
}
