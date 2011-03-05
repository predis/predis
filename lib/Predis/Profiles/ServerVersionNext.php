<?php

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion22 {
    public function getVersion() { return 'DEV'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* remote server control commands */
            'info'                  => '\Predis\Commands\InfoV24x',
        ));
    }
}
