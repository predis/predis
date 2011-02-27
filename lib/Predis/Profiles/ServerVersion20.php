<?php

namespace Predis\Profiles;

class ServerVersion20 extends ServerVersion12 {
    public function getVersion() { return '2.0'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* transactions */
            'multi'                     => '\Predis\Commands\Multi',
            'exec'                      => '\Predis\Commands\Exec',
            'discard'                   => '\Predis\Commands\Discard',

            /* commands operating on string values */
            'setex'                     => '\Predis\Commands\SetExpire',
            'append'                    => '\Predis\Commands\Append',
            'substr'                    => '\Predis\Commands\Substr',

            /* commands operating on the key space */
            'keys'                      => '\Predis\Commands\Keys',

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
        ));
    }
}
