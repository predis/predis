<?php

namespace Predis\Commands;

class BackgroundRewriteAppendOnlyFile extends Command {
    public function getId() {
        return 'BGREWRITEAOF';
    }

    protected function canBeHashed() {
        return false;
    }

    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}
