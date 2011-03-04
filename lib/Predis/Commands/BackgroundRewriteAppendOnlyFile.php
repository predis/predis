<?php

namespace Predis\Commands;

class BackgroundRewriteAppendOnlyFile extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'BGREWRITEAOF'; }
    public function parseResponse($data) {
        return $data == 'Background append only file rewriting started';
    }
}
