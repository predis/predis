<?php

namespace Predis\Commands;

class HashGet extends Command {
    public function getCommandId() { return 'HGET'; }
}
