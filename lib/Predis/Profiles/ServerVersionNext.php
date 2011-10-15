<?php

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion24 {
    public function getVersion() { return '2.6'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            'info'                      => '\Predis\Commands\ServerInfoV26x',
            'eval'                      => '\Predis\Commands\ServerEval',
            'evalsha'                   => '\Predis\Commands\ServerEvalSHA',
        ));
    }
}
