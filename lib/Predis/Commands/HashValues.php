<?php

namespace Predis\Commands;

class HashValues extends Command {
    public function getCommandId() { return 'HVALS'; }
}
