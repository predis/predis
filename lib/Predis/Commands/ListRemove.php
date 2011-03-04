<?php

namespace Predis\Commands;

class ListRemove extends Command {
    public function getId() { return 'LREM'; }
}
