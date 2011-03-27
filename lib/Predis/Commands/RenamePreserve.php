<?php

namespace Predis\Commands;

class RenamePreserve extends Command {
    protected function canBeHashed() { return false; }
    public function getId() { return 'RENAMENX'; }
    public function parseResponse($data) { return (bool) $data; }
}
