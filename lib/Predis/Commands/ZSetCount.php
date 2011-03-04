<?php

namespace Predis\Commands;

class ZSetCount extends Command {
    public function getId() { return 'ZCOUNT'; }
}
