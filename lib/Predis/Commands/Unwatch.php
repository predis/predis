<?php

namespace Predis\Commands;

class Unwatch extends Command {
    public function canBeHashed()  { return false; }
    public function getId() { return 'UNWATCH'; }
    public function parseResponse($data) { return (bool) $data; }
}
