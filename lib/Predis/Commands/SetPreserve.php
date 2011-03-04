<?php

namespace Predis\Commands;

class SetPreserve extends Command {
    public function getCommandId() { return 'SETNX'; }
    public function parseResponse($data) { return (bool) $data; }
}
