<?php

namespace Predis\Commands;

class HashKeys extends Command {
    public function getCommandId() { return 'HKEYS'; }
}
