<?php

namespace Predis\Commands;

class HashLength extends Command {
    public function getCommandId() { return 'HLEN'; }
}
