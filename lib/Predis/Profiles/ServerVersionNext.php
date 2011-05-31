<?php

namespace Predis\Profiles;

class ServerVersionNext extends ServerVersion24 {
    public function getVersion() { return '2.6'; }
    public function getSupportedCommands() {
        return array_merge(parent::getSupportedCommands(), array(
            'eval'                      => '\Predis\Commands\ServerEval',
            'evalsha'                   => '\Predis\Commands\ServerEvalSHA',
        ));
    }
}
