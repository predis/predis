<?php

namespace Predis\Profiles;

class ServerVersion12 extends ServerProfile {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array(
            /* ---------------- Redis 1.2 ---------------- */

            /* commands operating on the key space */
            'exists'                    => '\Predis\Commands\KeyExists',
            'del'                       => '\Predis\Commands\KeyDelete',
            'type'                      => '\Predis\Commands\KeyType',
            'keys'                      => '\Predis\Commands\KeyKeysV12x',
            'randomkey'                 => '\Predis\Commands\KeyRandom',
            'rename'                    => '\Predis\Commands\KeyRename',
            'renamenx'                  => '\Predis\Commands\KeyRenamePreserve',
            'expire'                    => '\Predis\Commands\KeyExpire',
            'expireat'                  => '\Predis\Commands\KeyExpireAt',
            'ttl'                       => '\Predis\Commands\KeyTimeToLive',
            'move'                      => '\Predis\Commands\KeyMove',
            'sort'                      => '\Predis\Commands\KeySort',

            /* commands operating on string values */
            'set'                       => '\Predis\Commands\StringSet',
            'setnx'                     => '\Predis\Commands\StringSetPreserve',
            'mset'                      => '\Predis\Commands\StringSetMultiple',
            'msetnx'                    => '\Predis\Commands\StringSetMultiplePreserve',
            'get'                       => '\Predis\Commands\StringGet',
            'mget'                      => '\Predis\Commands\StringGetMultiple',
            'getset'                    => '\Predis\Commands\StringGetSet',
            'incr'                      => '\Predis\Commands\StringIncrement',
            'incrby'                    => '\Predis\Commands\StringIncrementBy',
            'decr'                      => '\Predis\Commands\StringDecrement',
            'decrby'                    => '\Predis\Commands\StringDecrementBy',

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

            /* connection related commands */
            'ping'                      => '\Predis\Commands\ConnectionPing',
            'auth'                      => '\Predis\Commands\ConnectionAuth',
            'select'                    => '\Predis\Commands\ConnectionSelect',
            'echo'                      => '\Predis\Commands\ConnectionEcho',
            'quit'                      => '\Predis\Commands\ConnectionQuit',

            /* remote server control commands */
            'info'                      => '\Predis\Commands\ServerInfo',
            'slaveof'                   => '\Predis\Commands\ServerSlaveOf',
            'monitor'                   => '\Predis\Commands\ServerMonitor',
            'dbsize'                    => '\Predis\Commands\ServerDatabaseSize',
            'flushdb'                   => '\Predis\Commands\ServerFlushDatabase',
            'flushall'                  => '\Predis\Commands\ServerFlushAll',
            'save'                      => '\Predis\Commands\ServerSave',
            'bgsave'                    => '\Predis\Commands\ServerBackgroundSave',
            'lastsave'                  => '\Predis\Commands\ServerLastSave',
            'shutdown'                  => '\Predis\Commands\ServerShutdown',
            'bgrewriteaof'              => '\Predis\Commands\ServerBackgroundRewriteAOF',
        );
    }
}
