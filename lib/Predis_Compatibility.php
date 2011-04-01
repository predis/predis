<?php

Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v1_0', '1.0');
Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v1_2_LongNames', '1.2');
Predis_RedisServerProfile::registerProfile('Predis_RedisServer_v2_0_LongNames', '2.0');

class Predis_RedisServer_v1_0 extends Predis_RedisServerProfile {
    public function getVersion() { return '1.0'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'      => 'Predis_Compatibility_v1_0_Commands_Ping',
            'echo'      => 'Predis_Compatibility_v1_0_Commands_DoEcho',
            'auth'      => 'Predis_Compatibility_v1_0_Commands_Auth',

            /* connection handling */
            'quit'      => 'Predis_Compatibility_v1_0_Commands_Quit',

            /* commands operating on string values */
            'set'                     => 'Predis_Compatibility_v1_0_Commands_Set',
            'setnx'                   => 'Predis_Compatibility_v1_0_Commands_SetPreserve',
                'setPreserve'         => 'Predis_Compatibility_v1_0_Commands_SetPreserve',
            'get'                     => 'Predis_Compatibility_v1_0_Commands_Get',
            'mget'                    => 'Predis_Compatibility_v1_0_Commands_GetMultiple',
                'getMultiple'         => 'Predis_Compatibility_v1_0_Commands_GetMultiple',
            'getset'                  => 'Predis_Compatibility_v1_0_Commands_GetSet',
                'getSet'              => 'Predis_Compatibility_v1_0_Commands_GetSet',
            'incr'                    => 'Predis_Compatibility_v1_0_Commands_Increment',
                'increment'           => 'Predis_Compatibility_v1_0_Commands_Increment',
            'incrby'                  => 'Predis_Compatibility_v1_0_Commands_IncrementBy',
                'incrementBy'         => 'Predis_Compatibility_v1_0_Commands_IncrementBy',
            'decr'                    => 'Predis_Compatibility_v1_0_Commands_Decrement',
                'decrement'           => 'Predis_Compatibility_v1_0_Commands_Decrement',
            'decrby'                  => 'Predis_Compatibility_v1_0_Commands_DecrementBy',
                'decrementBy'         => 'Predis_Compatibility_v1_0_Commands_DecrementBy',
            'exists'                  => 'Predis_Compatibility_v1_0_Commands_Exists',
            'del'                     => 'Predis_Compatibility_v1_0_Commands_Delete',
                'delete'              => 'Predis_Compatibility_v1_0_Commands_Delete',
            'type'                    => 'Predis_Compatibility_v1_0_Commands_Type',

            /* commands operating on the key space */
            'keys'               => 'Predis_Compatibility_v1_0_Commands_Keys',
            'randomkey'          => 'Predis_Compatibility_v1_0_Commands_RandomKey',
                'randomKey'      => 'Predis_Compatibility_v1_0_Commands_RandomKey',
            'rename'             => 'Predis_Compatibility_v1_0_Commands_Rename',
            'renamenx'           => 'Predis_Compatibility_v1_0_Commands_RenamePreserve',
                'renamePreserve' => 'Predis_Compatibility_v1_0_Commands_RenamePreserve',
            'expire'             => 'Predis_Compatibility_v1_0_Commands_Expire',
            'expireat'           => 'Predis_Compatibility_v1_0_Commands_ExpireAt',
                'expireAt'       => 'Predis_Compatibility_v1_0_Commands_ExpireAt',
            'dbsize'             => 'Predis_Compatibility_v1_0_Commands_DatabaseSize',
                'databaseSize'   => 'Predis_Compatibility_v1_0_Commands_DatabaseSize',
            'ttl'                => 'Predis_Compatibility_v1_0_Commands_TimeToLive',
                'timeToLive'     => 'Predis_Compatibility_v1_0_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'            => 'Predis_Compatibility_v1_0_Commands_ListPushTail',
                'pushTail'     => 'Predis_Compatibility_v1_0_Commands_ListPushTail',
            'lpush'            => 'Predis_Compatibility_v1_0_Commands_ListPushHead',
                'pushHead'     => 'Predis_Compatibility_v1_0_Commands_ListPushHead',
            'llen'             => 'Predis_Compatibility_v1_0_Commands_ListLength',
                'listLength'   => 'Predis_Compatibility_v1_0_Commands_ListLength',
            'lrange'           => 'Predis_Compatibility_v1_0_Commands_ListRange',
                'listRange'    => 'Predis_Compatibility_v1_0_Commands_ListRange',
            'ltrim'            => 'Predis_Compatibility_v1_0_Commands_ListTrim',
                'listTrim'     => 'Predis_Compatibility_v1_0_Commands_ListTrim',
            'lindex'           => 'Predis_Compatibility_v1_0_Commands_ListIndex',
                'listIndex'    => 'Predis_Compatibility_v1_0_Commands_ListIndex',
            'lset'             => 'Predis_Compatibility_v1_0_Commands_ListSet',
                'listSet'      => 'Predis_Compatibility_v1_0_Commands_ListSet',
            'lrem'             => 'Predis_Compatibility_v1_0_Commands_ListRemove',
                'listRemove'   => 'Predis_Compatibility_v1_0_Commands_ListRemove',
            'lpop'             => 'Predis_Compatibility_v1_0_Commands_ListPopFirst',
                'popFirst'     => 'Predis_Compatibility_v1_0_Commands_ListPopFirst',
            'rpop'             => 'Predis_Compatibility_v1_0_Commands_ListPopLast',
                'popLast'      => 'Predis_Compatibility_v1_0_Commands_ListPopLast',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Compatibility_v1_0_Commands_SetAdd', 
                'setAdd'                => 'Predis_Compatibility_v1_0_Commands_SetAdd',
            'srem'                      => 'Predis_Compatibility_v1_0_Commands_SetRemove', 
                'setRemove'             => 'Predis_Compatibility_v1_0_Commands_SetRemove',
            'spop'                      => 'Predis_Compatibility_v1_0_Commands_SetPop',
                'setPop'                => 'Predis_Compatibility_v1_0_Commands_SetPop',
            'smove'                     => 'Predis_Compatibility_v1_0_Commands_SetMove', 
                'setMove'               => 'Predis_Compatibility_v1_0_Commands_SetMove',
            'scard'                     => 'Predis_Compatibility_v1_0_Commands_SetCardinality', 
                'setCardinality'        => 'Predis_Compatibility_v1_0_Commands_SetCardinality',
            'sismember'                 => 'Predis_Compatibility_v1_0_Commands_SetIsMember', 
                'setIsMember'           => 'Predis_Compatibility_v1_0_Commands_SetIsMember',
            'sinter'                    => 'Predis_Compatibility_v1_0_Commands_SetIntersection', 
                'setIntersection'       => 'Predis_Compatibility_v1_0_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Compatibility_v1_0_Commands_SetIntersectionStore', 
                'setIntersectionStore'  => 'Predis_Compatibility_v1_0_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Compatibility_v1_0_Commands_SetUnion', 
                'setUnion'              => 'Predis_Compatibility_v1_0_Commands_SetUnion',
            'sunionstore'               => 'Predis_Compatibility_v1_0_Commands_SetUnionStore', 
                'setUnionStore'         => 'Predis_Compatibility_v1_0_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Compatibility_v1_0_Commands_SetDifference', 
                'setDifference'         => 'Predis_Compatibility_v1_0_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Compatibility_v1_0_Commands_SetDifferenceStore', 
                'setDifferenceStore'    => 'Predis_Compatibility_v1_0_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Compatibility_v1_0_Commands_SetMembers', 
                'setMembers'            => 'Predis_Compatibility_v1_0_Commands_SetMembers',
            'srandmember'               => 'Predis_Compatibility_v1_0_Commands_SetRandomMember', 
                'setRandomMember'       => 'Predis_Compatibility_v1_0_Commands_SetRandomMember',

            /* multiple databases handling commands */
            'select'                => 'Predis_Compatibility_v1_0_Commands_SelectDatabase', 
                'selectDatabase'    => 'Predis_Compatibility_v1_0_Commands_SelectDatabase',
            'move'                  => 'Predis_Compatibility_v1_0_Commands_MoveKey', 
                'moveKey'           => 'Predis_Compatibility_v1_0_Commands_MoveKey',
            'flushdb'               => 'Predis_Compatibility_v1_0_Commands_FlushDatabase', 
                'flushDatabase'     => 'Predis_Compatibility_v1_0_Commands_FlushDatabase',
            'flushall'              => 'Predis_Compatibility_v1_0_Commands_FlushAll', 
                'flushDatabases'    => 'Predis_Compatibility_v1_0_Commands_FlushAll',

            /* sorting */
            'sort'                  => 'Predis_Compatibility_v1_0_Commands_Sort',

            /* remote server control commands */
            'info'                  => 'Predis_Compatibility_v1_0_Commands_Info',
            'slaveof'               => 'Predis_Compatibility_v1_0_Commands_SlaveOf', 
                'slaveOf'           => 'Predis_Compatibility_v1_0_Commands_SlaveOf',

            /* persistence control commands */
            'save'                  => 'Predis_Compatibility_v1_0_Commands_Save',
            'bgsave'                => 'Predis_Compatibility_v1_0_Commands_BackgroundSave', 
                'backgroundSave'    => 'Predis_Compatibility_v1_0_Commands_BackgroundSave',
            'lastsave'              => 'Predis_Compatibility_v1_0_Commands_LastSave', 
                'lastSave'          => 'Predis_Compatibility_v1_0_Commands_LastSave',
            'shutdown'              => 'Predis_Compatibility_v1_0_Commands_Shutdown',
        );
    }
}

class Predis_RedisServer_v1_2_LongNames extends Predis_RedisServerProfile {
    public function getVersion() { return '1.2'; }
    public function getSupportedCommands() {
        return array(
            /* miscellaneous commands */
            'ping'      => 'Predis_Commands_Ping',
            'echo'      => 'Predis_Commands_DoEcho',
            'auth'      => 'Predis_Commands_Auth',

            /* connection handling */
            'quit'      => 'Predis_Commands_Quit',

            /* commands operating on string values */
            'set'                     => 'Predis_Commands_Set',
            'setnx'                   => 'Predis_Commands_SetPreserve',
                'setPreserve'         => 'Predis_Commands_SetPreserve',
            'mset'                    => 'Predis_Commands_SetMultiple',
                'setMultiple'         => 'Predis_Commands_SetMultiple',
            'msetnx'                  => 'Predis_Commands_SetMultiplePreserve',
                'setMultiplePreserve' => 'Predis_Commands_SetMultiplePreserve',
            'get'                     => 'Predis_Commands_Get',
            'mget'                    => 'Predis_Commands_GetMultiple',
                'getMultiple'         => 'Predis_Commands_GetMultiple',
            'getset'                  => 'Predis_Commands_GetSet',
                'getSet'              => 'Predis_Commands_GetSet',
            'incr'                    => 'Predis_Commands_Increment',
                'increment'           => 'Predis_Commands_Increment',
            'incrby'                  => 'Predis_Commands_IncrementBy',
                'incrementBy'         => 'Predis_Commands_IncrementBy',
            'decr'                    => 'Predis_Commands_Decrement',
                'decrement'           => 'Predis_Commands_Decrement',
            'decrby'                  => 'Predis_Commands_DecrementBy',
                'decrementBy'         => 'Predis_Commands_DecrementBy',
            'exists'                  => 'Predis_Commands_Exists',
            'del'                     => 'Predis_Commands_Delete',
                'delete'              => 'Predis_Commands_Delete',
            'type'                    => 'Predis_Commands_Type',

            /* commands operating on the key space */
            'keys'               => 'Predis_Commands_Keys_v1_2',
            'randomkey'          => 'Predis_Commands_RandomKey',
                'randomKey'      => 'Predis_Commands_RandomKey',
            'rename'             => 'Predis_Commands_Rename',
            'renamenx'           => 'Predis_Commands_RenamePreserve',
                'renamePreserve' => 'Predis_Commands_RenamePreserve',
            'expire'             => 'Predis_Commands_Expire',
            'expireat'           => 'Predis_Commands_ExpireAt',
                'expireAt'       => 'Predis_Commands_ExpireAt',
            'dbsize'             => 'Predis_Commands_DatabaseSize',
                'databaseSize'   => 'Predis_Commands_DatabaseSize',
            'ttl'                => 'Predis_Commands_TimeToLive',
                'timeToLive'     => 'Predis_Commands_TimeToLive',

            /* commands operating on lists */
            'rpush'            => 'Predis_Commands_ListPushTail',
                'pushTail'     => 'Predis_Commands_ListPushTail',
            'lpush'            => 'Predis_Commands_ListPushHead',
                'pushHead'     => 'Predis_Commands_ListPushHead',
            'llen'             => 'Predis_Commands_ListLength',
                'listLength'   => 'Predis_Commands_ListLength',
            'lrange'           => 'Predis_Commands_ListRange',
                'listRange'    => 'Predis_Commands_ListRange',
            'ltrim'            => 'Predis_Commands_ListTrim',
                'listTrim'     => 'Predis_Commands_ListTrim',
            'lindex'           => 'Predis_Commands_ListIndex',
                'listIndex'    => 'Predis_Commands_ListIndex',
            'lset'             => 'Predis_Commands_ListSet',
                'listSet'      => 'Predis_Commands_ListSet',
            'lrem'             => 'Predis_Commands_ListRemove',
                'listRemove'   => 'Predis_Commands_ListRemove',
            'lpop'             => 'Predis_Commands_ListPopFirst',
                'popFirst'     => 'Predis_Commands_ListPopFirst',
            'rpop'             => 'Predis_Commands_ListPopLast',
                'popLast'      => 'Predis_Commands_ListPopLast',
            'rpoplpush'        => 'Predis_Commands_ListPopLastPushHead',
                'listPopLastPushHead'  => 'Predis_Commands_ListPopLastPushHead',

            /* commands operating on sets */
            'sadd'                      => 'Predis_Commands_SetAdd',
                'setAdd'                => 'Predis_Commands_SetAdd',
            'srem'                      => 'Predis_Commands_SetRemove',
                'setRemove'             => 'Predis_Commands_SetRemove',
            'spop'                      => 'Predis_Commands_SetPop',
                'setPop'                => 'Predis_Commands_SetPop',
            'smove'                     => 'Predis_Commands_SetMove',
                'setMove'               => 'Predis_Commands_SetMove',
            'scard'                     => 'Predis_Commands_SetCardinality',
                'setCardinality'        => 'Predis_Commands_SetCardinality',
            'sismember'                 => 'Predis_Commands_SetIsMember',
                'setIsMember'           => 'Predis_Commands_SetIsMember',
            'sinter'                    => 'Predis_Commands_SetIntersection',
                'setIntersection'       => 'Predis_Commands_SetIntersection',
            'sinterstore'               => 'Predis_Commands_SetIntersectionStore',
                'setIntersectionStore'  => 'Predis_Commands_SetIntersectionStore',
            'sunion'                    => 'Predis_Commands_SetUnion',
                'setUnion'              => 'Predis_Commands_SetUnion',
            'sunionstore'               => 'Predis_Commands_SetUnionStore',
                'setUnionStore'         => 'Predis_Commands_SetUnionStore',
            'sdiff'                     => 'Predis_Commands_SetDifference',
                'setDifference'         => 'Predis_Commands_SetDifference',
            'sdiffstore'                => 'Predis_Commands_SetDifferenceStore',
                'setDifferenceStore'    => 'Predis_Commands_SetDifferenceStore',
            'smembers'                  => 'Predis_Commands_SetMembers',
                'setMembers'            => 'Predis_Commands_SetMembers',
            'srandmember'               => 'Predis_Commands_SetRandomMember',
                'setRandomMember'       => 'Predis_Commands_SetRandomMember',

            /* commands operating on sorted sets */
            'zadd'                          => 'Predis_Commands_ZSetAdd',
                'zsetAdd'                   => 'Predis_Commands_ZSetAdd',
            'zincrby'                       => 'Predis_Commands_ZSetIncrementBy',
                'zsetIncrementBy'           => 'Predis_Commands_ZSetIncrementBy',
            'zrem'                          => 'Predis_Commands_ZSetRemove',
                'zsetRemove'                => 'Predis_Commands_ZSetRemove',
            'zrange'                        => 'Predis_Commands_ZSetRange',
                'zsetRange'                 => 'Predis_Commands_ZSetRange',
            'zrevrange'                     => 'Predis_Commands_ZSetReverseRange',
                'zsetReverseRange'          => 'Predis_Commands_ZSetReverseRange',
            'zrangebyscore'                 => 'Predis_Commands_ZSetRangeByScore',
                'zsetRangeByScore'          => 'Predis_Commands_ZSetRangeByScore',
            'zcard'                         => 'Predis_Commands_ZSetCardinality',
                'zsetCardinality'           => 'Predis_Commands_ZSetCardinality',
            'zscore'                        => 'Predis_Commands_ZSetScore',
                'zsetScore'                 => 'Predis_Commands_ZSetScore',
            'zremrangebyscore'              => 'Predis_Commands_ZSetRemoveRangeByScore',
                'zsetRemoveRangeByScore'    => 'Predis_Commands_ZSetRemoveRangeByScore',

            /* multiple databases handling commands */
            'select'                => 'Predis_Commands_SelectDatabase',
                'selectDatabase'    => 'Predis_Commands_SelectDatabase',
            'move'                  => 'Predis_Commands_MoveKey',
                'moveKey'           => 'Predis_Commands_MoveKey',
            'flushdb'               => 'Predis_Commands_FlushDatabase',
                'flushDatabase'     => 'Predis_Commands_FlushDatabase',
            'flushall'              => 'Predis_Commands_FlushAll',
                'flushDatabases'    => 'Predis_Commands_FlushAll',

            /* sorting */
            'sort'                  => 'Predis_Commands_Sort',

            /* remote server control commands */
            'info'                  => 'Predis_Commands_Info',
            'slaveof'               => 'Predis_Commands_SlaveOf',
                'slaveOf'           => 'Predis_Commands_SlaveOf',

            /* persistence control commands */
            'save'                  => 'Predis_Commands_Save',
            'bgsave'                => 'Predis_Commands_BackgroundSave',
                'backgroundSave'    => 'Predis_Commands_BackgroundSave',
            'lastsave'              => 'Predis_Commands_LastSave',
                'lastSave'          => 'Predis_Commands_LastSave',
            'shutdown'              => 'Predis_Commands_Shutdown',
            'bgrewriteaof'                      =>  'Predis_Commands_BackgroundRewriteAppendOnlyFile',
            'backgroundRewriteAppendOnlyFile'   =>  'Predis_Commands_BackgroundRewriteAppendOnlyFile',
        );
    }
}

class Predis_RedisServer_v2_0_LongNames extends Predis_RedisServer_v1_2_LongNames {
    public function getVersion() { return '2.0'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'multi'                     => 'Predis_Commands_Multi',
            'exec'                      => 'Predis_Commands_Exec',
            'discard'                   => 'Predis_Commands_Discard',

            /* commands operating on string values */
            'setex'                     => 'Predis_Commands_SetExpire',
                'setExpire'             => 'Predis_Commands_SetExpire',
            'append'                    => 'Predis_Commands_Append',
            'substr'                    => 'Predis_Commands_Substr',

            /* commands operating on the key space */
            'keys'                      => 'Predis_Commands_Keys',

            /* commands operating on lists */
            'blpop'                     => 'Predis_Commands_ListPopFirstBlocking',
                'popFirstBlocking'      => 'Predis_Commands_ListPopFirstBlocking',
            'brpop'                     => 'Predis_Commands_ListPopLastBlocking',
                'popLastBlocking'       => 'Predis_Commands_ListPopLastBlocking',

            /* commands operating on sorted sets */
            'zunionstore'               => 'Predis_Commands_ZSetUnionStore',
                'zsetUnionStore'        => 'Predis_Commands_ZSetUnionStore',
            'zinterstore'               => 'Predis_Commands_ZSetIntersectionStore',
                'zsetIntersectionStore' => 'Predis_Commands_ZSetIntersectionStore',
            'zcount'                    => 'Predis_Commands_ZSetCount',
                'zsetCount'             => 'Predis_Commands_ZSetCount',
            'zrank'                     => 'Predis_Commands_ZSetRank',
                'zsetRank'              => 'Predis_Commands_ZSetRank',
            'zrevrank'                  => 'Predis_Commands_ZSetReverseRank',
                'zsetReverseRank'       => 'Predis_Commands_ZSetReverseRank',
            'zremrangebyrank'           => 'Predis_Commands_ZSetRemoveRangeByRank',
                'zsetRemoveRangeByRank' => 'Predis_Commands_ZSetRemoveRangeByRank',

            /* commands operating on hashes */
            'hset'                      => 'Predis_Commands_HashSet',
                'hashSet'               => 'Predis_Commands_HashSet',
            'hsetnx'                    => 'Predis_Commands_HashSetPreserve',
                'hashSetPreserve'       => 'Predis_Commands_HashSetPreserve',
            'hmset'                     => 'Predis_Commands_HashSetMultiple',
                'hashSetMultiple'       => 'Predis_Commands_HashSetMultiple',
            'hincrby'                   => 'Predis_Commands_HashIncrementBy',
                'hashIncrementBy'       => 'Predis_Commands_HashIncrementBy',
            'hget'                      => 'Predis_Commands_HashGet',
                'hashGet'               => 'Predis_Commands_HashGet',
            'hmget'                     => 'Predis_Commands_HashGetMultiple',
                'hashGetMultiple'       => 'Predis_Commands_HashGetMultiple',
            'hdel'                      => 'Predis_Commands_HashDelete',
                'hashDelete'            => 'Predis_Commands_HashDelete',
            'hexists'                   => 'Predis_Commands_HashExists',
                'hashExists'            => 'Predis_Commands_HashExists',
            'hlen'                      => 'Predis_Commands_HashLength',
                'hashLength'            => 'Predis_Commands_HashLength',
            'hkeys'                     => 'Predis_Commands_HashKeys',
                'hashKeys'              => 'Predis_Commands_HashKeys',
            'hvals'                     => 'Predis_Commands_HashValues',
                'hashValues'            => 'Predis_Commands_HashValues',
            'hgetall'                   => 'Predis_Commands_HashGetAll',
                'hashGetAll'            => 'Predis_Commands_HashGetAll',

            /* publish - subscribe */
            'subscribe'                 => 'Predis_Commands_Subscribe',
            'unsubscribe'               => 'Predis_Commands_Unsubscribe',
            'psubscribe'                => 'Predis_Commands_SubscribeByPattern',
            'punsubscribe'              => 'Predis_Commands_UnsubscribeByPattern',
            'publish'                   => 'Predis_Commands_Publish',

            /* remote server control commands */
            'config'                    => 'Predis_Commands_Config',
                'configuration'         => 'Predis_Commands_Config',
        ));
    }
}

/* ------------------------------------------------------------------------- */

/* miscellaneous commands */
class Predis_Compatibility_v1_0_Commands_Ping extends  Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'PING'; }
    public function parseResponse($data) {
        return $data === 'PONG' ? true : false;
    }
}

class Predis_Compatibility_v1_0_Commands_DoEcho extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'ECHO'; }
}

class Predis_Compatibility_v1_0_Commands_Auth extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'AUTH'; }
}

/* connection handling */
class Predis_Compatibility_v1_0_Commands_Quit extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'QUIT'; }
    public function closesConnection() { return true; }
}

/* commands operating on string values */
class Predis_Compatibility_v1_0_Commands_Set extends Predis_BulkCommand {
    public function getCommandId() { return 'SET'; }
}

class Predis_Compatibility_v1_0_Commands_SetPreserve extends Predis_BulkCommand {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Get extends Predis_InlineCommand {
    public function getCommandId() { return 'GET'; }
}

class Predis_Compatibility_v1_0_Commands_GetMultiple extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MGET'; }
}

class Predis_Compatibility_v1_0_Commands_GetSet extends Predis_BulkCommand {
    public function getCommandId() { return 'GETSET'; }
}

class Predis_Compatibility_v1_0_Commands_Increment extends Predis_InlineCommand {
    public function getCommandId() { return 'INCR'; }
}

class Predis_Compatibility_v1_0_Commands_IncrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'INCRBY'; }
}

class Predis_Compatibility_v1_0_Commands_Decrement extends Predis_InlineCommand {
    public function getCommandId() { return 'DECR'; }
}

class Predis_Compatibility_v1_0_Commands_DecrementBy extends Predis_InlineCommand {
    public function getCommandId() { return 'DECRBY'; }
}

class Predis_Compatibility_v1_0_Commands_Exists extends Predis_InlineCommand {
    public function getCommandId() { return 'EXISTS'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Delete extends Predis_InlineCommand {
    public function getCommandId() { return 'DEL'; }
}

class Predis_Compatibility_v1_0_Commands_Type extends Predis_InlineCommand {
    public function getCommandId() { return 'TYPE'; }
}

/* commands operating on the key space */
class Predis_Compatibility_v1_0_Commands_Keys extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'KEYS'; }
    public function parseResponse($data) { 
        return strlen($data) > 0 ? explode(' ', $data) : array();
    }
}

class Predis_Compatibility_v1_0_Commands_RandomKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RANDOMKEY'; }
    public function parseResponse($data) { return $data !== '' ? $data : null; }
}

class Predis_Compatibility_v1_0_Commands_Rename extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAME'; }
}

class Predis_Compatibility_v1_0_Commands_RenamePreserve extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_Expire extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIRE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_ExpireAt extends Predis_InlineCommand {
    public function getCommandId() { return 'EXPIREAT'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_DatabaseSize extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'DBSIZE'; }
}

class Predis_Compatibility_v1_0_Commands_TimeToLive extends Predis_InlineCommand {
    public function getCommandId() { return 'TTL'; }
}

/* commands operating on lists */
class Predis_Compatibility_v1_0_Commands_ListPushTail extends Predis_BulkCommand {
    public function getCommandId() { return 'RPUSH'; }
}

class Predis_Compatibility_v1_0_Commands_ListPushHead extends Predis_BulkCommand {
    public function getCommandId() { return 'LPUSH'; }
}

class Predis_Compatibility_v1_0_Commands_ListLength extends Predis_InlineCommand {
    public function getCommandId() { return 'LLEN'; }
}

class Predis_Compatibility_v1_0_Commands_ListRange extends Predis_InlineCommand {
    public function getCommandId() { return 'LRANGE'; }
}

class Predis_Compatibility_v1_0_Commands_ListTrim extends Predis_InlineCommand {
    public function getCommandId() { return 'LTRIM'; }
}

class Predis_Compatibility_v1_0_Commands_ListIndex extends Predis_InlineCommand {
    public function getCommandId() { return 'LINDEX'; }
}

class Predis_Compatibility_v1_0_Commands_ListSet extends Predis_BulkCommand {
    public function getCommandId() { return 'LSET'; }
}

class Predis_Compatibility_v1_0_Commands_ListRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'LREM'; }
}

class Predis_Compatibility_v1_0_Commands_ListPopFirst extends Predis_InlineCommand {
    public function getCommandId() { return 'LPOP'; }
}

class Predis_Compatibility_v1_0_Commands_ListPopLast extends Predis_InlineCommand {
    public function getCommandId() { return 'RPOP'; }
}

/* commands operating on sets */
class Predis_Compatibility_v1_0_Commands_SetAdd extends Predis_BulkCommand {
    public function getCommandId() { return 'SADD'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetRemove extends Predis_BulkCommand {
    public function getCommandId() { return 'SREM'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetPop  extends Predis_InlineCommand {
    public function getCommandId() { return 'SPOP'; }
}

class Predis_Compatibility_v1_0_Commands_SetMove extends Predis_BulkCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SMOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetCardinality extends Predis_InlineCommand {
    public function getCommandId() { return 'SCARD'; }
}

class Predis_Compatibility_v1_0_Commands_SetIsMember extends Predis_BulkCommand {
    public function getCommandId() { return 'SISMEMBER'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_SetIntersection extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTER'; }
}

class Predis_Compatibility_v1_0_Commands_SetIntersectionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SINTERSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetUnion extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNION'; }
}

class Predis_Compatibility_v1_0_Commands_SetUnionStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SUNIONSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetDifference extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFF'; }
}

class Predis_Compatibility_v1_0_Commands_SetDifferenceStore extends Predis_InlineCommand {
    public function getCommandId() { return 'SDIFFSTORE'; }
}

class Predis_Compatibility_v1_0_Commands_SetMembers extends Predis_InlineCommand {
    public function getCommandId() { return 'SMEMBERS'; }
}

class Predis_Compatibility_v1_0_Commands_SetRandomMember extends Predis_InlineCommand {
    public function getCommandId() { return 'SRANDMEMBER'; }
}

/* multiple databases handling commands */
class Predis_Compatibility_v1_0_Commands_SelectDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SELECT'; }
}

class Predis_Compatibility_v1_0_Commands_MoveKey extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'MOVE'; }
    public function parseResponse($data) { return (bool) $data; }
}

class Predis_Compatibility_v1_0_Commands_FlushDatabase extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHDB'; }
}

class Predis_Compatibility_v1_0_Commands_FlushAll extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'FLUSHALL'; }
}

/* sorting */
class Predis_Compatibility_v1_0_Commands_Sort extends Predis_InlineCommand {
    public function getCommandId() { return 'SORT'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 1) {
            return $arguments;
        }

        // TODO: add more parameters checks
        $query = array($arguments[0]);
        $sortParams = $arguments[1];

        if (isset($sortParams['by'])) {
            $query[] = 'BY';
            $query[] = $sortParams['by'];
        }
        if (isset($sortParams['get'])) {
            $getargs = $sortParams['get'];
            if (is_array($getargs)) {
                foreach ($getargs as $getarg) {
                    $query[] = 'GET';
                    $query[] = $getarg;
                }
            }
            else {
                $query[] = 'GET';
                $query[] = $getargs;
            }
        }
        if (isset($sortParams['limit']) && is_array($sortParams['limit'])) {
            $query[] = 'LIMIT';
            $query[] = $sortParams['limit'][0];
            $query[] = $sortParams['limit'][1];
        }
        if (isset($sortParams['sort'])) {
            $query[] = strtoupper($sortParams['sort']);
        }
        if (isset($sortParams['alpha']) && $sortParams['alpha'] == true) {
            $query[] = 'ALPHA';
        }
        if (isset($sortParams['store']) && $sortParams['store'] == true) {
            $query[] = 'STORE';
            $query[] = $sortParams['store'];
        }

        return $query;
    }
}

/* persistence control commands */
class Predis_Compatibility_v1_0_Commands_Save extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SAVE'; }
}

class Predis_Compatibility_v1_0_Commands_BackgroundSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'BGSAVE'; }
    public function parseResponse($data) {
        if ($data == 'Background saving started') {
            return true;
        }
        return $data;
    }
}

class Predis_Compatibility_v1_0_Commands_LastSave extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'LASTSAVE'; }
}

class Predis_Compatibility_v1_0_Commands_Shutdown extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SHUTDOWN'; }
    public function closesConnection() { return true; }
}

/* remote server control commands */
class Predis_Compatibility_v1_0_Commands_Info extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'INFO'; }
    public function parseResponse($data) {
        $info      = array();
        $infoLines = explode("\r\n", $data, -1);
        foreach ($infoLines as $row) {
            list($k, $v) = explode(':', $row);
            if (!preg_match('/^db\d+$/', $k)) {
                $info[$k] = $v;
            }
            else {
                $db = array();
                foreach (explode(',', $v) as $dbvar) {
                    list($dbvk, $dbvv) = explode('=', $dbvar);
                    $db[trim($dbvk)] = $dbvv;
                }
                $info[$k] = $db;
            }
        }
        return $info;
    }
}

class Predis_Compatibility_v1_0_Commands_SlaveOf extends Predis_InlineCommand {
    public function canBeHashed()  { return false; }
    public function getCommandId() { return 'SLAVEOF'; }
    public function filterArguments(Array $arguments) {
        if (count($arguments) === 0 || $arguments[0] === 'NO ONE') {
            return array('NO', 'ONE');
        }
        return $arguments;
    }
}
?>
