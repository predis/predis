<?php

namespace Predis\Commands;

class ListRemove extends Command {
    public function getCommandId() { return 'LREM'; }
}
