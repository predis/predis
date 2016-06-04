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
 * Server profile for Redis 2.6.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class RedisVersion260 extends RedisProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.6';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'EXISTS' => 'Predis\Command\Redis\KeyExists',
            'DEL' => 'Predis\Command\Redis\KeyDelete',
            'TYPE' => 'Predis\Command\Redis\KeyType',
            'KEYS' => 'Predis\Command\Redis\KeyKeys',
            'RANDOMKEY' => 'Predis\Command\Redis\KeyRandom',
            'RENAME' => 'Predis\Command\Redis\KeyRename',
            'RENAMENX' => 'Predis\Command\Redis\KeyRenamePreserve',
            'EXPIRE' => 'Predis\Command\Redis\KeyExpire',
            'EXPIREAT' => 'Predis\Command\Redis\KeyExpireAt',
            'TTL' => 'Predis\Command\Redis\KeyTimeToLive',
            'MOVE' => 'Predis\Command\Redis\KeyMove',
            'SORT' => 'Predis\Command\Redis\KeySort',
            'DUMP' => 'Predis\Command\Redis\KeyDump',
            'RESTORE' => 'Predis\Command\Redis\KeyRestore',

            /* commands operating on string values */
            'SET' => 'Predis\Command\Redis\StringSet',
            'SETNX' => 'Predis\Command\Redis\StringSetPreserve',
            'MSET' => 'Predis\Command\Redis\StringSetMultiple',
            'MSETNX' => 'Predis\Command\Redis\StringSetMultiplePreserve',
            'GET' => 'Predis\Command\Redis\StringGet',
            'MGET' => 'Predis\Command\Redis\StringGetMultiple',
            'GETSET' => 'Predis\Command\Redis\StringGetSet',
            'INCR' => 'Predis\Command\Redis\StringIncrement',
            'INCRBY' => 'Predis\Command\Redis\StringIncrementBy',
            'DECR' => 'Predis\Command\Redis\StringDecrement',
            'DECRBY' => 'Predis\Command\Redis\StringDecrementBy',

            /* commands operating on lists */
            'RPUSH' => 'Predis\Command\Redis\ListPushTail',
            'LPUSH' => 'Predis\Command\Redis\ListPushHead',
            'LLEN' => 'Predis\Command\Redis\ListLength',
            'LRANGE' => 'Predis\Command\Redis\ListRange',
            'LTRIM' => 'Predis\Command\Redis\ListTrim',
            'LINDEX' => 'Predis\Command\Redis\ListIndex',
            'LSET' => 'Predis\Command\Redis\ListSet',
            'LREM' => 'Predis\Command\Redis\ListRemove',
            'LPOP' => 'Predis\Command\Redis\ListPopFirst',
            'RPOP' => 'Predis\Command\Redis\ListPopLast',
            'RPOPLPUSH' => 'Predis\Command\Redis\ListPopLastPushHead',

            /* commands operating on sets */
            'SADD' => 'Predis\Command\Redis\SetAdd',
            'SREM' => 'Predis\Command\Redis\SetRemove',
            'SPOP' => 'Predis\Command\Redis\SetPop',
            'SMOVE' => 'Predis\Command\Redis\SetMove',
            'SCARD' => 'Predis\Command\Redis\SetCardinality',
            'SISMEMBER' => 'Predis\Command\Redis\SetIsMember',
            'SINTER' => 'Predis\Command\Redis\SetIntersection',
            'SINTERSTORE' => 'Predis\Command\Redis\SetIntersectionStore',
            'SUNION' => 'Predis\Command\Redis\SetUnion',
            'SUNIONSTORE' => 'Predis\Command\Redis\SetUnionStore',
            'SDIFF' => 'Predis\Command\Redis\SetDifference',
            'SDIFFSTORE' => 'Predis\Command\Redis\SetDifferenceStore',
            'SMEMBERS' => 'Predis\Command\Redis\SetMembers',
            'SRANDMEMBER' => 'Predis\Command\Redis\SetRandomMember',

            /* commands operating on sorted sets */
            'ZADD' => 'Predis\Command\Redis\ZSetAdd',
            'ZINCRBY' => 'Predis\Command\Redis\ZSetIncrementBy',
            'ZREM' => 'Predis\Command\Redis\ZSetRemove',
            'ZRANGE' => 'Predis\Command\Redis\ZSetRange',
            'ZREVRANGE' => 'Predis\Command\Redis\ZSetReverseRange',
            'ZRANGEBYSCORE' => 'Predis\Command\Redis\ZSetRangeByScore',
            'ZCARD' => 'Predis\Command\Redis\ZSetCardinality',
            'ZSCORE' => 'Predis\Command\Redis\ZSetScore',
            'ZREMRANGEBYSCORE' => 'Predis\Command\Redis\ZSetRemoveRangeByScore',

            /* connection related commands */
            'PING' => 'Predis\Command\Redis\ConnectionPing',
            'AUTH' => 'Predis\Command\Redis\ConnectionAuth',
            'SELECT' => 'Predis\Command\Redis\ConnectionSelect',
            'ECHO' => 'Predis\Command\Redis\ConnectionEcho',
            'QUIT' => 'Predis\Command\Redis\ConnectionQuit',

            /* remote server control commands */
            'INFO' => 'Predis\Command\Redis\ServerInfoV26x',
            'SLAVEOF' => 'Predis\Command\Redis\ServerSlaveOf',
            'MONITOR' => 'Predis\Command\Redis\ServerMonitor',
            'DBSIZE' => 'Predis\Command\Redis\ServerDatabaseSize',
            'FLUSHDB' => 'Predis\Command\Redis\ServerFlushDatabase',
            'FLUSHALL' => 'Predis\Command\Redis\ServerFlushAll',
            'SAVE' => 'Predis\Command\Redis\ServerSave',
            'BGSAVE' => 'Predis\Command\Redis\ServerBackgroundSave',
            'LASTSAVE' => 'Predis\Command\Redis\ServerLastSave',
            'SHUTDOWN' => 'Predis\Command\Redis\ServerShutdown',
            'BGREWRITEAOF' => 'Predis\Command\Redis\ServerBackgroundRewriteAOF',

            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'SETEX' => 'Predis\Command\Redis\StringSetExpire',
            'APPEND' => 'Predis\Command\Redis\StringAppend',
            'SUBSTR' => 'Predis\Command\Redis\StringSubstr',

            /* commands operating on lists */
            'BLPOP' => 'Predis\Command\Redis\ListPopFirstBlocking',
            'BRPOP' => 'Predis\Command\Redis\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'ZUNIONSTORE' => 'Predis\Command\Redis\ZSetUnionStore',
            'ZINTERSTORE' => 'Predis\Command\Redis\ZSetIntersectionStore',
            'ZCOUNT' => 'Predis\Command\Redis\ZSetCount',
            'ZRANK' => 'Predis\Command\Redis\ZSetRank',
            'ZREVRANK' => 'Predis\Command\Redis\ZSetReverseRank',
            'ZREMRANGEBYRANK' => 'Predis\Command\Redis\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'HSET' => 'Predis\Command\Redis\HashSet',
            'HSETNX' => 'Predis\Command\Redis\HashSetPreserve',
            'HMSET' => 'Predis\Command\Redis\HashSetMultiple',
            'HINCRBY' => 'Predis\Command\Redis\HashIncrementBy',
            'HGET' => 'Predis\Command\Redis\HashGet',
            'HMGET' => 'Predis\Command\Redis\HashGetMultiple',
            'HDEL' => 'Predis\Command\Redis\HashDelete',
            'HEXISTS' => 'Predis\Command\Redis\HashExists',
            'HLEN' => 'Predis\Command\Redis\HashLength',
            'HKEYS' => 'Predis\Command\Redis\HashKeys',
            'HVALS' => 'Predis\Command\Redis\HashValues',
            'HGETALL' => 'Predis\Command\Redis\HashGetAll',

            /* transactions */
            'MULTI' => 'Predis\Command\Redis\TransactionMulti',
            'EXEC' => 'Predis\Command\Redis\TransactionExec',
            'DISCARD' => 'Predis\Command\Redis\TransactionDiscard',

            /* publish - subscribe */
            'SUBSCRIBE' => 'Predis\Command\Redis\PubSubSubscribe',
            'UNSUBSCRIBE' => 'Predis\Command\Redis\PubSubUnsubscribe',
            'PSUBSCRIBE' => 'Predis\Command\Redis\PubSubSubscribeByPattern',
            'PUNSUBSCRIBE' => 'Predis\Command\Redis\PubSubUnsubscribeByPattern',
            'PUBLISH' => 'Predis\Command\Redis\PubSubPublish',

            /* remote server control commands */
            'CONFIG' => 'Predis\Command\Redis\ServerConfig',

            /* ---------------- Redis 2.2 ---------------- */

            /* commands operating on the key space */
            'PERSIST' => 'Predis\Command\Redis\KeyPersist',

            /* commands operating on string values */
            'STRLEN' => 'Predis\Command\Redis\StringStrlen',
            'SETRANGE' => 'Predis\Command\Redis\StringSetRange',
            'GETRANGE' => 'Predis\Command\Redis\StringGetRange',
            'SETBIT' => 'Predis\Command\Redis\StringSetBit',
            'GETBIT' => 'Predis\Command\Redis\StringGetBit',

            /* commands operating on lists */
            'RPUSHX' => 'Predis\Command\Redis\ListPushTailX',
            'LPUSHX' => 'Predis\Command\Redis\ListPushHeadX',
            'LINSERT' => 'Predis\Command\Redis\ListInsert',
            'BRPOPLPUSH' => 'Predis\Command\Redis\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'ZREVRANGEBYSCORE' => 'Predis\Command\Redis\ZSetReverseRangeByScore',

            /* transactions */
            'WATCH' => 'Predis\Command\Redis\TransactionWatch',
            'UNWATCH' => 'Predis\Command\Redis\TransactionUnwatch',

            /* remote server control commands */
            'OBJECT' => 'Predis\Command\Redis\ServerObject',
            'SLOWLOG' => 'Predis\Command\Redis\ServerSlowlog',

            /* ---------------- Redis 2.4 ---------------- */

            /* remote server control commands */
            'CLIENT' => 'Predis\Command\Redis\ServerClient',

            /* ---------------- Redis 2.6 ---------------- */

            /* commands operating on the key space */
            'PTTL' => 'Predis\Command\Redis\KeyPreciseTimeToLive',
            'PEXPIRE' => 'Predis\Command\Redis\KeyPreciseExpire',
            'PEXPIREAT' => 'Predis\Command\Redis\KeyPreciseExpireAt',
            'MIGRATE' => 'Predis\Command\Redis\KeyMigrate',

            /* commands operating on string values */
            'PSETEX' => 'Predis\Command\Redis\StringPreciseSetExpire',
            'INCRBYFLOAT' => 'Predis\Command\Redis\StringIncrementByFloat',
            'BITOP' => 'Predis\Command\Redis\StringBitOp',
            'BITCOUNT' => 'Predis\Command\Redis\StringBitCount',

            /* commands operating on hashes */
            'HINCRBYFLOAT' => 'Predis\Command\Redis\HashIncrementByFloat',

            /* scripting */
            'EVAL' => 'Predis\Command\Redis\ServerEval',
            'EVALSHA' => 'Predis\Command\Redis\ServerEvalSHA',
            'SCRIPT' => 'Predis\Command\Redis\ServerScript',

            /* remote server control commands */
            'TIME' => 'Predis\Command\Redis\ServerTime',
            'SENTINEL' => 'Predis\Command\Redis\ServerSentinel',
        );
    }
}
