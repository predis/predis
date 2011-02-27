<?php

namespace Predis\Profiles;

class ServerVersion22 extends ServerVersion20 {
    public function getVersion() { return '2.2'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
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
        ));
    }
}
