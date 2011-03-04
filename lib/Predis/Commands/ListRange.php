<?php

namespace Predis\Commands;

class ListRange extends Command {
    public function getCommandId() { return 'LRANGE'; }
}
