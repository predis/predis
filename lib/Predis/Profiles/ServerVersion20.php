<?php

/*
 * This file is part of the Predis package.
 *
 * (c) Daniele Alessandri <suppakilla@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Predis\Profiles;

/**
 * Server profile for Redis v2.0.x.
 *
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */
class ServerVersion20 extends ServerProfile
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '2.0';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCommands()
    {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'exists'                    => 'Predis\Commands\KeyExists',
            'del'                       => 'Predis\Commands\KeyDelete',
            'type'                      => 'Predis\Commands\KeyType',
            'keys'                      => 'Predis\Commands\KeyKeys',
            'randomkey'                 => 'Predis\Commands\KeyRandom',
            'rename'                    => 'Predis\Commands\KeyRename',
            'renamenx'                  => 'Predis\Commands\KeyRenamePreserve',
            'expire'                    => 'Predis\Commands\KeyExpire',
            'expireat'                  => 'Predis\Commands\KeyExpireAt',
            'ttl'                       => 'Predis\Commands\KeyTimeToLive',
            'move'                      => 'Predis\Commands\KeyMove',
            'sort'                      => 'Predis\Commands\KeySort',

            /* commands operating on string values */
            'set'                       => 'Predis\Commands\StringSet',
            'setnx'                     => 'Predis\Commands\StringSetPreserve',
            'mset'                      => 'Predis\Commands\StringSetMultiple',
            'msetnx'                    => 'Predis\Commands\StringSetMultiplePreserve',
            'get'                       => 'Predis\Commands\StringGet',
            'mget'                      => 'Predis\Commands\StringGetMultiple',
            'getset'                    => 'Predis\Commands\StringGetSet',
            'incr'                      => 'Predis\Commands\StringIncrement',
            'incrby'                    => 'Predis\Commands\StringIncrementBy',
            'decr'                      => 'Predis\Commands\StringDecrement',
            'decrby'                    => 'Predis\Commands\StringDecrementBy',

            /* commands operating on lists */
            'rpush'                     => 'Predis\Commands\ListPushTail',
            'lpush'                     => 'Predis\Commands\ListPushHead',
            'llen'                      => 'Predis\Commands\ListLength',
            'lrange'                    => 'Predis\Commands\ListRange',
            'ltrim'                     => 'Predis\Commands\ListTrim',
            'lindex'                    => 'Predis\Commands\ListIndex',
            'lset'                      => 'Predis\Commands\ListSet',
            'lrem'                      => 'Predis\Commands\ListRemove',
            'lpop'                      => 'Predis\Commands\ListPopFirst',
            'rpop'                      => 'Predis\Commands\ListPopLast',
            'rpoplpush'                 => 'Predis\Commands\ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => 'Predis\Commands\SetAdd',
            'srem'                      => 'Predis\Commands\SetRemove',
            'spop'                      => 'Predis\Commands\SetPop',
            'smove'                     => 'Predis\Commands\SetMove',
            'scard'                     => 'Predis\Commands\SetCardinality',
            'sismember'                 => 'Predis\Commands\SetIsMember',
            'sinter'                    => 'Predis\Commands\SetIntersection',
            'sinterstore'               => 'Predis\Commands\SetIntersectionStore',
            'sunion'                    => 'Predis\Commands\SetUnion',
            'sunionstore'               => 'Predis\Commands\SetUnionStore',
            'sdiff'                     => 'Predis\Commands\SetDifference',
            'sdiffstore'                => 'Predis\Commands\SetDifferenceStore',
            'smembers'                  => 'Predis\Commands\SetMembers',
            'srandmember'               => 'Predis\Commands\SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => 'Predis\Commands\ZSetAdd',
            'zincrby'                   => 'Predis\Commands\ZSetIncrementBy',
            'zrem'                      => 'Predis\Commands\ZSetRemove',
            'zrange'                    => 'Predis\Commands\ZSetRange',
            'zrevrange'                 => 'Predis\Commands\ZSetReverseRange',
            'zrangebyscore'             => 'Predis\Commands\ZSetRangeByScore',
            'zcard'                     => 'Predis\Commands\ZSetCardinality',
            'zscore'                    => 'Predis\Commands\ZSetScore',
            'zremrangebyscore'          => 'Predis\Commands\ZSetRemoveRangeByScore',

            /* connection related commands */
            'ping'                      => 'Predis\Commands\ConnectionPing',
            'auth'                      => 'Predis\Commands\ConnectionAuth',
            'select'                    => 'Predis\Commands\ConnectionSelect',
            'echo'                      => 'Predis\Commands\ConnectionEcho',
            'quit'                      => 'Predis\Commands\ConnectionQuit',

            /* remote server control commands */
            'info'                      => 'Predis\Commands\ServerInfo',
            'slaveof'                   => 'Predis\Commands\ServerSlaveOf',
            'monitor'                   => 'Predis\Commands\ServerMonitor',
            'dbsize'                    => 'Predis\Commands\ServerDatabaseSize',
            'flushdb'                   => 'Predis\Commands\ServerFlushDatabase',
            'flushall'                  => 'Predis\Commands\ServerFlushAll',
            'save'                      => 'Predis\Commands\ServerSave',
            'bgsave'                    => 'Predis\Commands\ServerBackgroundSave',
            'lastsave'                  => 'Predis\Commands\ServerLastSave',
            'shutdown'                  => 'Predis\Commands\ServerShutdown',
            'bgrewriteaof'              => 'Predis\Commands\ServerBackgroundRewriteAOF',


            /* ---------------- Redis 2.0 ---------------- */

            /* commands operating on string values */
            'setex'                     => 'Predis\Commands\StringSetExpire',
            'append'                    => 'Predis\Commands\StringAppend',
            'substr'                    => 'Predis\Commands\StringSubstr',

            /* commands operating on lists */
            'blpop'                     => 'Predis\Commands\ListPopFirstBlocking',
            'brpop'                     => 'Predis\Commands\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => 'Predis\Commands\ZSetUnionStore',
            'zinterstore'               => 'Predis\Commands\ZSetIntersectionStore',
            'zcount'                    => 'Predis\Commands\ZSetCount',
            'zrank'                     => 'Predis\Commands\ZSetRank',
            'zrevrank'                  => 'Predis\Commands\ZSetReverseRank',
            'zremrangebyrank'           => 'Predis\Commands\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => 'Predis\Commands\HashSet',
            'hsetnx'                    => 'Predis\Commands\HashSetPreserve',
            'hmset'                     => 'Predis\Commands\HashSetMultiple',
            'hincrby'                   => 'Predis\Commands\HashIncrementBy',
            'hget'                      => 'Predis\Commands\HashGet',
            'hmget'                     => 'Predis\Commands\HashGetMultiple',
            'hdel'                      => 'Predis\Commands\HashDelete',
            'hexists'                   => 'Predis\Commands\HashExists',
            'hlen'                      => 'Predis\Commands\HashLength',
            'hkeys'                     => 'Predis\Commands\HashKeys',
            'hvals'                     => 'Predis\Commands\HashValues',
            'hgetall'                   => 'Predis\Commands\HashGetAll',

            /* transactions */
            'multi'                     => 'Predis\Commands\TransactionMulti',
            'exec'                      => 'Predis\Commands\TransactionExec',
            'discard'                   => 'Predis\Commands\TransactionDiscard',

            /* publish - subscribe */
            'subscribe'                 => 'Predis\Commands\PubSubSubscribe',
            'unsubscribe'               => 'Predis\Commands\PubSubUnsubscribe',
            'psubscribe'                => 'Predis\Commands\PubSubSubscribeByPattern',
            'punsubscribe'              => 'Predis\Commands\PubSubUnsubscribeByPattern',
            'publish'                   => 'Predis\Commands\PubSubPublish',

            /* remote server control commands */
            'config'                    => 'Predis\Commands\ServerConfig',
        );
    }
}
