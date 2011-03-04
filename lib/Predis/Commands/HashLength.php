<?php

namespace Predis\Commands;

class HashLength extends Command {
    public function getId() { return 'HLEN'; }
}
