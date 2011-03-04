<?php

namespace Predis\Commands;

class HashKeys extends Command {
    public function getId() { return 'HKEYS'; }
}
