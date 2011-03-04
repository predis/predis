<?php

namespace Predis\Commands;

class ZSetCardinality extends Command {
    public function getId() { return 'ZCARD'; }
}
