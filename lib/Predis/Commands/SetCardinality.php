<?php

namespace Predis\Commands;

class SetCardinality extends Command {
    public function getId() { return 'SCARD'; }
}
