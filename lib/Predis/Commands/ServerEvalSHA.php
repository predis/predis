<?php

namespace Predis\Commands;

class ServerEvalSHA extends ServerEval {
    public function getId() {
        return 'EVALSHA';
    }
}
