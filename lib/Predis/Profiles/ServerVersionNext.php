<?php

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion22 {
    public function getVersion() { return '2.4'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* commands operating on lists */
            'rpush'                 => '\Predis\Commands\ListPushTailV24x',
            'lpush'                 => '\Predis\Commands\ListPushHeadV24x',

            /* commands operating on sets */
            'sadd'                      => '\Predis\Commands\SetAddV24x',

            /* remote server control commands */
            'info'                  => '\Predis\Commands\InfoV24x',
        ));
    }
}
