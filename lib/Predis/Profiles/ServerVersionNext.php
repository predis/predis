<?php

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion22 {
    public function getVersion() { return '2.4'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            /* remote server control commands */
            'info'                      => '\Predis\Commands\ServerInfoV24x',
            'client'                    => '\Predis\Commands\ServerClient',
            'eval'                      => '\Predis\Commands\ServerEval',
            'evalsha'                   => '\Predis\Commands\ServerEvalSHA',
        ));
    }
}
