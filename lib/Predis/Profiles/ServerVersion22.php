<?php

namespace Predis\Profiles;

class ServerVersion22 extends ServerProfile {
    public function getVersion() { return '2.2'; }
    public function getSupportedCommands() {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* miscellaneous commands */
            'ping'                      => '\Predis\Commands\Ping',
            'echo'                      => '\Predis\Commands\DoEcho',
            'auth'                      => '\Predis\Commands\Auth',

            /* connection handling */
            'quit'                      => '\Predis\Commands\Quit',

            /* commands operating on string values */
            'set'                       => '\Predis\Commands\Set',
            'setnx'                     => '\Predis\Commands\SetPreserve',
            'mset'                      => '\Predis\Commands\SetMultiple',
            'msetnx'                    => '\Predis\Commands\SetMultiplePreserve',
            'get'                       => '\Predis\Commands\Get',
            'mget'                      => '\Predis\Commands\GetMultiple',
            'getset'                    => '\Predis\Commands\GetSet',
            'incr'                      => '\Predis\Commands\Increment',
            'incrby'                    => '\Predis\Commands\IncrementBy',
            'decr'                      => '\Predis\Commands\Decrement',
            'decrby'                    => '\Predis\Commands\DecrementBy',
            'exists'                    => '\Predis\Commands\Exists',
            'del'                       => '\Predis\Commands\Delete',
            'type'                      => '\Predis\Commands\Type',

            /* commands operating on the key space */
            'keys'                      => '\Predis\Commands\Keys',
            'randomkey'                 => '\Predis\Commands\RandomKey',
            'rename'                    => '\Predis\Commands\Rename',
            'renamenx'                  => '\Predis\Commands\RenamePreserve',
            'expire'                    => '\Predis\Commands\Expire',
            'expireat'                  => '\Predis\Commands\ExpireAt',
            'dbsize'                    => '\Predis\Commands\DatabaseSize',
            'ttl'                       => '\Predis\Commands\TimeToLive',

            /* commands operating on lists */
            'rpush'                     => '\Predis\Commands\ListPushTail',
            'lpush'                     => '\Predis\Commands\ListPushHead',
            'llen'                      => '\Predis\Commands\ListLength',
            'lrange'                    => '\Predis\Commands\ListRange',
            'ltrim'                     => '\Predis\Commands\ListTrim',
            'lindex'                    => '\Predis\Commands\ListIndex',
            'lset'                      => '\Predis\Commands\ListSet',
            'lrem'                      => '\Predis\Commands\ListRemove',
            'lpop'                      => '\Predis\Commands\ListPopFirst',
            'rpop'                      => '\Predis\Commands\ListPopLast',
            'rpoplpush'                 => '\Predis\Commands\ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => '\Predis\Commands\SetAdd',
            'srem'                      => '\Predis\Commands\SetRemove',
            'spop'                      => '\Predis\Commands\SetPop',
            'smove'                     => '\Predis\Commands\SetMove',
            'scard'                     => '\Predis\Commands\SetCardinality',
            'sismember'                 => '\Predis\Commands\SetIsMember',
            'sinter'                    => '\Predis\Commands\SetIntersection',
            'sinterstore'               => '\Predis\Commands\SetIntersectionStore',
            'sunion'                    => '\Predis\Commands\SetUnion',
            'sunionstore'               => '\Predis\Commands\SetUnionStore',
            'sdiff'                     => '\Predis\Commands\SetDifference',
            'sdiffstore'                => '\Predis\Commands\SetDifferenceStore',
            'smembers'                  => '\Predis\Commands\SetMembers',
            'srandmember'               => '\Predis\Commands\SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                      => '\Predis\Commands\ZSetAdd',
            'zincrby'                   => '\Predis\Commands\ZSetIncrementBy',
            'zrem'                      => '\Predis\Commands\ZSetRemove',
            'zrange'                    => '\Predis\Commands\ZSetRange',
            'zrevrange'                 => '\Predis\Commands\ZSetReverseRange',
            'zrangebyscore'             => '\Predis\Commands\ZSetRangeByScore',
            'zcard'                     => '\Predis\Commands\ZSetCardinality',
            'zscore'                    => '\Predis\Commands\ZSetScore',
            'zremrangebyscore'          => '\Predis\Commands\ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                    => '\Predis\Commands\SelectDatabase',
            'move'                      => '\Predis\Commands\MoveKey',
            'flushdb'                   => '\Predis\Commands\FlushDatabase',
            'flushall'                  => '\Predis\Commands\FlushAll',

            /* sorting */
            'sort'                      => '\Predis\Commands\Sort',

            /* remote server control commands */
            'info'                      => '\Predis\Commands\Info',
            'slaveof'                   => '\Predis\Commands\SlaveOf',

            /* persistence control commands */
            'save'                      => '\Predis\Commands\Save',
            'bgsave'                    => '\Predis\Commands\BackgroundSave',
            'lastsave'                  => '\Predis\Commands\LastSave',
            'shutdown'                  => '\Predis\Commands\Shutdown',
            'bgrewriteaof'              => '\Predis\Commands\BackgroundRewriteAppendOnlyFile',


            /* ---------------- Redis 2.0 ---------------- */

            /* transactions */
            'multi'                     => '\Predis\Commands\Multi',
            'exec'                      => '\Predis\Commands\Exec',
            'discard'                   => '\Predis\Commands\Discard',

            /* commands operating on string values */
            'setex'                     => '\Predis\Commands\SetExpire',
            'append'                    => '\Predis\Commands\Append',
            'substr'                    => '\Predis\Commands\Substr',

            /* commands operating on lists */
            'blpop'                     => '\Predis\Commands\ListPopFirstBlocking',
            'brpop'                     => '\Predis\Commands\ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => '\Predis\Commands\ZSetUnionStore',
            'zinterstore'               => '\Predis\Commands\ZSetIntersectionStore',
            'zcount'                    => '\Predis\Commands\ZSetCount',
            'zrank'                     => '\Predis\Commands\ZSetRank',
            'zrevrank'                  => '\Predis\Commands\ZSetReverseRank',
            'zremrangebyrank'           => '\Predis\Commands\ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => '\Predis\Commands\HashSet',
            'hsetnx'                    => '\Predis\Commands\HashSetPreserve',
            'hmset'                     => '\Predis\Commands\HashSetMultiple',
            'hincrby'                   => '\Predis\Commands\HashIncrementBy',
            'hget'                      => '\Predis\Commands\HashGet',
            'hmget'                     => '\Predis\Commands\HashGetMultiple',
            'hdel'                      => '\Predis\Commands\HashDelete',
            'hexists'                   => '\Predis\Commands\HashExists',
            'hlen'                      => '\Predis\Commands\HashLength',
            'hkeys'                     => '\Predis\Commands\HashKeys',
            'hvals'                     => '\Predis\Commands\HashValues',
            'hgetall'                   => '\Predis\Commands\HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => '\Predis\Commands\Subscribe',
            'unsubscribe'               => '\Predis\Commands\Unsubscribe',
            'psubscribe'                => '\Predis\Commands\SubscribeByPattern',
            'punsubscribe'              => '\Predis\Commands\UnsubscribeByPattern',
            'publish'                   => '\Predis\Commands\Publish',

            /* remote server control commands */
            'config'                    => '\Predis\Commands\Config',


            /* ---------------- Redis 2.2 ---------------- */

            /* transactions */
            'watch'                     => '\Predis\Commands\Watch',
            'unwatch'                   => '\Predis\Commands\Unwatch',

            /* commands operating on string values */
            'strlen'                    => '\Predis\Commands\Strlen',
            'setrange'                  => '\Predis\Commands\SetRange',
            'getrange'                  => '\Predis\Commands\GetRange',
            'setbit'                    => '\Predis\Commands\SetBit',
            'getbit'                    => '\Predis\Commands\GetBit',

            /* commands operating on the key space */
            'persist'                   => '\Predis\Commands\Persist',

            /* commands operating on lists */
            'rpushx'                    => '\Predis\Commands\ListPushTailX',
            'lpushx'                    => '\Predis\Commands\ListPushHeadX',
            'linsert'                   => '\Predis\Commands\ListInsert',
            'brpoplpush'                => '\Predis\Commands\ListPopLastPushHeadBlocking',

            /* commands operating on sorted sets */
            'zrevrangebyscore'          => '\Predis\Commands\ZSetReverseRangeByScore',
        );
    }
}
