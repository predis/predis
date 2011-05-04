<?php

namespace Predis\Commands;

class ServerBackgroundRewriteAOF extends Command {
    public function getId() {
        return 'BGREWRITEAOF';
    }

    protected function onPrefixKeys(Array $arguments, $prefix) {
        /* NOOP */
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}
